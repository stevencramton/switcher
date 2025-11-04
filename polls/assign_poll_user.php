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
	$inquiry_id = $_POST['inquiry_id'];
	$query_assign = "SELECT inquiry_name FROM poll_inquiry WHERE inquiry_id = ?";
    
	if ($stmt = mysqli_prepare($dbc, $query_assign)) {
     	mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
      	mysqli_stmt_execute($stmt);
     	mysqli_stmt_store_result($stmt);
      	mysqli_stmt_bind_result($stmt, $inquiry_name);
        $inquiry_name = '';
        while (mysqli_stmt_fetch($stmt)) {
            $inquiry_name = htmlspecialchars($inquiry_name);
        }
     	mysqli_stmt_close($stmt);
    } else {
        die('Statement Preperation Failed.');
    }

    $poll_user_assignment = json_decode(strip_tags($_POST['assignment_user']));
	$check_query = "SELECT * FROM poll_assignment WHERE assignment_user = ? AND poll_id = ?";
    
	if ($check_stmt = mysqli_prepare($dbc, $check_query)) {
        $response = array();
        $user_arr = array();
        $user_count = 0;

        foreach ($poll_user_assignment as $item) {
          	mysqli_stmt_bind_param($check_stmt, 'si', $item, $inquiry_id);
          	mysqli_stmt_execute($check_stmt);
          	mysqli_stmt_store_result($check_stmt);
         	
			if (mysqli_stmt_num_rows($check_stmt) == 0) {
                $assignment_status = 'Yes'; 
				$insert_query = "INSERT INTO poll_assignment (assignment_user, assignment_status, poll_id, poll_name) VALUES (?, ?, ?, ?)";
                if ($insert_stmt = mysqli_prepare($dbc, $insert_query)) {
                 	mysqli_stmt_bind_param($insert_stmt, 'ssis', $item, $assignment_status, $inquiry_id, $inquiry_name);
                  	if (mysqli_stmt_execute($insert_stmt)) {
                        $user_count++;
                    } else {
                        die('Query Failed.');
                    }
                    mysqli_stmt_close($insert_stmt);
                } else {
                    die('Query Failed.');
                }
            } else {
                $user_arr[] = $item;
            }
        }
        mysqli_stmt_close($check_stmt);
        
        if ($user_count > 0) {
            $response = array(
                "status" => "success",
                "user_count" => $user_count
            );
            echo json_encode($response);
        } else {
            echo json_encode(array("existing_users" => $user_arr));
        }
    } else {
        die('Query Failed.');
    }
}
mysqli_close($dbc);