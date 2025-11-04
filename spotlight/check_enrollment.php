<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('spotlight_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id']) && isset($_POST['assignment_user'])) {
	$spotlight_id = strip_tags($_POST['inquiry_id']);
    $spotlight_users = explode(',', $_POST['assignment_user']);
	$userConditions = array();
    foreach ($spotlight_users as $user) {
        $userConditions[] = "assignment_user = ?";
    }
    $userConditionStr = implode(' OR ', $userConditions);

	$query = "SELECT assignment_user FROM spotlight_assignment WHERE spotlight_id = ? AND ($userConditionStr)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, str_repeat("s", count($spotlight_users) + 1), $spotlight_id, ...$spotlight_users);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

		if (!$result) {
		    echo json_encode(array('status' => 'error', 'message'));
		    exit();
		}

        $enrolled_users = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $enrolled_users[] = $row['assignment_user'];
        }
        mysqli_stmt_close($stmt);

        $not_enrolled_users = array_diff($spotlight_users, $enrolled_users);

        if (!empty($enrolled_users)) {
            echo json_encode(array('status' => 'enrolled', 'message' => 'The following user(s) are already enrolled in the spotlight: ' . implode(', ', $enrolled_users)));
            exit();
        }
    }

    $inquiry_id = strip_tags($_POST['inquiry_id']);
	$query_assign = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query_assign)) {
        mysqli_stmt_bind_param($stmt, "s", $inquiry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row_assign = mysqli_fetch_assoc($result)) {
            $inquiry_name = strip_tags($row_assign['inquiry_name']);
        }
        mysqli_stmt_close($stmt);
    }
    
    $assignment_status = 'Yes';
	$insert_query = "INSERT INTO spotlight_assignment (spotlight_id, assignment_user, assignment_status, spotlight_name) VALUES (?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($dbc, $insert_query)) {
        foreach ($not_enrolled_users as $user) {
            mysqli_stmt_bind_param($stmt, "ssss", $spotlight_id, $user, $assignment_status, $inquiry_name);
            $insert_result = mysqli_stmt_execute($stmt);
            if (!$insert_result) {
                
                echo json_encode(array('status' => 'error', 'message'));
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }

    echo json_encode(array('status' => 'success', 'message' => 'Users have been enrolled.'));
    exit();
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request.'));
exit();

mysqli_close($dbc);