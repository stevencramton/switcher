<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {
	$inquiry_id = strip_tags($_POST['inquiry_id']);
	$query_assign = "SELECT * FROM poll_inquiry WHERE inquiry_id = ?";
    $stmt_assign = mysqli_prepare($dbc, $query_assign);
    mysqli_stmt_bind_param($stmt_assign, "i", $inquiry_id);
    mysqli_stmt_execute($stmt_assign);
    $result_assign = mysqli_stmt_get_result($stmt_assign);

    $inquiry_name = '';
    if ($result_assign) {
        while ($row_assign = mysqli_fetch_assoc($result_assign)) {
            $inquiry_name = strip_tags($row_assign['inquiry_name']);
        }
    }
    mysqli_stmt_close($stmt_assign);

  	$poll_id = $inquiry_id;
    $poll_users = explode(',', $_POST['assignment_user']);
    $assignment_status = 'Yes';
	$existing_users = array();
    $placeholders = implode(',', array_fill(0, count($poll_users), '?'));
    $existing_users_query = "SELECT assignment_user FROM poll_assignment WHERE poll_id = ? AND assignment_user IN ($placeholders)";
    $stmt_existing = mysqli_prepare($dbc, $existing_users_query);

    $types = str_repeat('s', count($poll_users));
    mysqli_stmt_bind_param($stmt_existing, "i".$types, $poll_id, ...$poll_users);
    mysqli_stmt_execute($stmt_existing);
    $result_existing = mysqli_stmt_get_result($stmt_existing);

    if ($result_existing) {
        while ($row = mysqli_fetch_assoc($result_existing)) {
            $existing_users[] = $row['assignment_user'];
        }
    }
    mysqli_stmt_close($stmt_existing);

	$new_users = array_diff($poll_users, $existing_users);
	if (empty($new_users)) {
        echo json_encode(array('status' => 'info', 'message' => 'The selected user(s) are already enrolled in the poll.'));
        exit();
    }

	$query = "INSERT INTO poll_assignment (poll_id, assignment_user, assignment_status, poll_name) VALUES ";
    $values = array();
    foreach ($new_users as $user) {
        $values[] = "(?, ?, ?, ?)";
    }
    
	$query .= implode(', ', $values);
	$stmt_insert = mysqli_prepare($dbc, $query);

    $insert_values = [];
    foreach ($new_users as $user) {
        $insert_values[] = $poll_id;
        $insert_values[] = $user;
        $insert_values[] = $assignment_status;
        $insert_values[] = $inquiry_name;
    }

    $types = str_repeat('isss', count($new_users));
    mysqli_stmt_bind_param($stmt_insert, $types, ...$insert_values);
    if (!mysqli_stmt_execute($stmt_insert)) {
        $error_message = mysqli_stmt_error($stmt_insert);
        echo json_encode(array('status' => 'error', 'message' => $error_message));
        mysqli_stmt_close($stmt_insert);
        mysqli_close($dbc);
        exit();
    }
    mysqli_stmt_close($stmt_insert);

    echo json_encode(array('status' => 'success', 'message' => 'User(s) have been added to the poll.'));
    mysqli_close($dbc);
    exit();
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request.'));
mysqli_close($dbc);
exit();