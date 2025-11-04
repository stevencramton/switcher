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

if (isset($_POST['role_cat_id'])) {
	$role_cat_id = mysqli_real_escape_string($dbc, strip_tags($_POST['role_cat_id']));
	$delete_query = "DELETE FROM roles_categories WHERE role_cat_id = ?";
    
	if ($stmt_delete = mysqli_prepare($dbc, $delete_query)) {
		mysqli_stmt_bind_param($stmt_delete, "i", $role_cat_id);
 	   	if (mysqli_stmt_execute($stmt_delete)) {
            echo "Role category deleted successfully.";
        } else {
			error_log("Error executing delete statement.");
            http_response_code(500);
            echo "Oops! Something went wrong. Please try again later.";
        }
		mysqli_stmt_close($stmt_delete);
    } else {
		error_log("Error preparing delete statement.");
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
    }

	$update_query = "UPDATE roles_type SET role_type_category = 6 WHERE role_type_category = ?";
	if ($stmt_update = mysqli_prepare($dbc, $update_query)) {
		mysqli_stmt_bind_param($stmt_update, "i", $role_cat_id);
  	  	if (mysqli_stmt_execute($stmt_update)) {
            echo "Roles updated successfully.";
        } else {
       	 	error_log("Error executing update statement.");
            http_response_code(500);
            echo "Oops! Something went wrong. Please try again later.";
        }
		mysqli_stmt_close($stmt_update);
    } else {
  	  	error_log("Error preparing update statement.");
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
    }
}
mysqli_close($dbc);