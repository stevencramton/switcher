<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {
	$inquiry_id = strip_tags($_POST['inquiry_id']);

	$stmt = mysqli_prepare($dbc, "SELECT inquiry_name FROM spotlight_inquiry WHERE inquiry_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $inquiry_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$inquiry_name) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid inquiry ID.'));
        mysqli_close($dbc);
        exit();
    }

	$spotlight_id = $inquiry_id;
    $spotlight_users = explode(',', $_POST['assignment_user']);
	$assignment_status = 'Yes';

	$placeholders = implode(',', array_fill(0, count($spotlight_users), '?'));
    $types = str_repeat('s', count($spotlight_users));
    $stmt = mysqli_prepare($dbc, "SELECT assignment_user FROM spotlight_assignment WHERE spotlight_id = ? AND assignment_user IN ($placeholders)");

	mysqli_stmt_bind_param($stmt, 'i' . $types, $spotlight_id, ...$spotlight_users);
    mysqli_stmt_execute($stmt);
    $existing_users_result = mysqli_stmt_get_result($stmt);
    $existing_users = [];
    while ($row = mysqli_fetch_assoc($existing_users_result)) {
        $existing_users[] = $row['assignment_user'];
    }
    mysqli_stmt_close($stmt);

	$new_users = array_diff($spotlight_users, $existing_users);

 	if (empty($new_users)) {
        echo json_encode(array('status' => 'info', 'message' => 'The selected user(s) are already enrolled in the spotlight.'));
        mysqli_close($dbc);
        exit();
    }

 	$stmt = mysqli_prepare($dbc, "INSERT INTO spotlight_assignment (spotlight_id, assignment_user, assignment_status, spotlight_name) VALUES (?, ?, ?, ?)");
    foreach ($new_users as $user) {
        mysqli_stmt_bind_param($stmt, 'isss', $spotlight_id, $user, $assignment_status, $inquiry_name);
        if (!mysqli_stmt_execute($stmt)) {
            $error_message = mysqli_stmt_error($stmt);
            echo json_encode(array('status' => 'error', 'message' => $error_message));
            mysqli_stmt_close($stmt);
            mysqli_close($dbc);
            exit();
        }
    }
    mysqli_stmt_close($stmt);

    echo json_encode(array('status' => 'success', 'message' => 'User(s) have been added to the spotlight.'));
    mysqli_close($dbc);
    exit();
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request.'));
mysqli_close($dbc);
exit();