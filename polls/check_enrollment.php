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

if (isset($_SESSION['id']) && isset($_POST['inquiry_id']) && isset($_POST['assignment_user'])) {
    $poll_id = $_POST['inquiry_id'];
    $poll_users = explode(',', $_POST['assignment_user']);
 	$check_query = "SELECT assignment_user FROM poll_assignment WHERE poll_id = ? AND assignment_user IN (" . implode(',', array_fill(0, count($poll_users), '?')) . ")";
    
    if ($check_stmt = mysqli_prepare($dbc, $check_query)) {
      	mysqli_stmt_bind_param($check_stmt, str_repeat('s', count($poll_users)), ...$poll_users);
      	mysqli_stmt_execute($check_stmt);
      	mysqli_stmt_store_result($check_stmt);
      	mysqli_stmt_bind_result($check_stmt, $enrolled_user);
        
        $enrolled_users = array();
        while (mysqli_stmt_fetch($check_stmt)) {
            $enrolled_users[] = $enrolled_user;
        }
        mysqli_stmt_close($check_stmt);
        
        $not_enrolled_users = array_diff($poll_users, $enrolled_users);

        if (!empty($enrolled_users)) {
            echo json_encode(array('status' => 'enrolled', 'message' => 'The following user(s) are already enrolled in the Poll: ' . implode(', ', $enrolled_users)));
            exit();
        }

        $query_assign = "SELECT inquiry_name FROM poll_inquiry WHERE inquiry_id = ?";
        if ($stmt = mysqli_prepare($dbc, $query_assign)) {
          	mysqli_stmt_bind_param($stmt, 'i', $poll_id);
          	mysqli_stmt_execute($stmt);
          	mysqli_stmt_store_result($stmt);
         	mysqli_stmt_bind_result($stmt, $inquiry_name);
            $inquiry_name = '';
            while (mysqli_stmt_fetch($stmt)) {
                $inquiry_name = htmlspecialchars($inquiry_name);
            }
            mysqli_stmt_close($stmt);
        } else {
            die('Query Failed.');
        }

        $assignment_status = 'Yes';

		$insert_query = "INSERT INTO poll_assignment (poll_id, assignment_user, assignment_status, poll_name) VALUES (?, ?, ?, ?)";
        if ($insert_stmt = mysqli_prepare($dbc, $insert_query)) {
         	foreach ($not_enrolled_users as $user) {
                mysqli_stmt_bind_param($insert_stmt, 'isss', $poll_id, $user, $assignment_status, $inquiry_name);
              	if (!mysqli_stmt_execute($insert_stmt)) {
                    echo json_encode(array('status' => 'error', 'message' => 'INSERT QUERY FAILED: ' . mysqli_stmt_error($insert_stmt))); 
                    exit();
                }
            }
            mysqli_stmt_close($insert_stmt);
            echo json_encode(array('status' => 'success', 'message' => 'Users have been enrolled.'));
            exit();
        } else {
            die('Query Failed.');
        }
    } else {
        die('Query Failed.');
    }
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request.'));
exit();
?>