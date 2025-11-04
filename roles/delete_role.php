<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!isset($_SESSION['user'])) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!checkRole('user_role')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['role_id'])) {
	$role_id = $_POST['role_id'];
	$user_query = "SELECT * FROM users WHERE role_id = ?";
 	if ($stmt_user = mysqli_prepare($dbc, $user_query)) {
  	  	mysqli_stmt_bind_param($stmt_user, "i", $role_id);
     	mysqli_stmt_execute($stmt_user);
     	mysqli_stmt_store_result($stmt_user);
      	$user_count = mysqli_stmt_num_rows($stmt_user);
    	mysqli_stmt_close($stmt_user);
    } else {
        error_log("Error preparing user select statement.");
        http_response_code(500);
        echo json_encode("error");
        exit();
    }

    if ($user_count !== 0) {
        $response = "user";
    } else if ($user_count === 0) {
		$delete_query = "DELETE FROM roles_dev WHERE role_id = ?";
 	   	if ($stmt_delete = mysqli_prepare($dbc, $delete_query)) {
     	   	mysqli_stmt_bind_param($stmt_delete, "i", $role_id);
  		  	if (mysqli_stmt_execute($stmt_delete)) {
                $response = "success";
            } else {
                error_log("Error executing delete statement.");
                $response = "error";
            }
       	 	mysqli_stmt_close($stmt_delete);
        } else {
            error_log("Error preparing delete statement.");
            http_response_code(500);
            echo json_encode("error");
            exit();
        }
    }
	echo json_encode($response);
}
mysqli_close($dbc);