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

if (isset($_POST['role_type_id'])) {
	$role_type_id = mysqli_real_escape_string($dbc, strip_tags($_POST['role_type_id']));
	$type_query = "SELECT role_type_db_name FROM roles_type WHERE role_type_id = ?";
    
	if ($stmt_type = mysqli_prepare($dbc, $type_query)) {
		mysqli_stmt_bind_param($stmt_type, "i", $role_type_id);
   	 	mysqli_stmt_execute($stmt_type);
 	   	mysqli_stmt_store_result($stmt_type);
        
		if (mysqli_stmt_num_rows($stmt_type) > 0) {
      	  	mysqli_stmt_bind_result($stmt_type, $type_db);
        	mysqli_stmt_fetch($stmt_type);
        } else {
    		echo json_encode("error");
            mysqli_stmt_close($stmt_type);
            mysqli_close($dbc);
            exit();
        }
  	  	mysqli_stmt_close($stmt_type);
    } else {
        error_log("Error preparing type select statement.");
        http_response_code(500);
        echo json_encode("error");
        exit();
    }

	$role_query = "SELECT COUNT(*) FROM roles_dev WHERE $type_db = 1";
    
	if ($stmt_role = mysqli_prepare($dbc, $role_query)) {
 	   	mysqli_stmt_execute($stmt_role);
    	mysqli_stmt_bind_result($stmt_role, $role_count);
   	 	mysqli_stmt_fetch($stmt_role);
   	 	mysqli_stmt_close($stmt_role);
    } else {
        error_log("Error preparing role select statement.");
        http_response_code(500);
        echo json_encode("error");
        exit();
    }

    if ($role_count !== 0) {
        $response = "error";
    } else if ($role_count === 0) {
		$delete_query = "DELETE FROM roles_type WHERE role_type_id = ?";
        
     	if ($stmt_delete = mysqli_prepare($dbc, $delete_query)) {
        	mysqli_stmt_bind_param($stmt_delete, "i", $role_type_id);
            
           	if (mysqli_stmt_execute($stmt_delete)) {
             	$delete_col_query = "ALTER TABLE roles_dev DROP COLUMN `$type_db`";
               	if (mysqli_query($dbc, $delete_col_query)) {
                    $response = "success";
                } else {
                    error_log("Error executing DROP COLUMN query.");
                    $response = "error";
                }
            } else {
                error_log("Error executing delete statement.");
                $response = "error";
            }
			mysqli_stmt_close($stmt_delete);
        } else {
            error_log("Error preparing delete statement.");
            $response = "error";
        }
    }
	echo json_encode($response);
}
mysqli_close($dbc);