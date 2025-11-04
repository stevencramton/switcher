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

if (isset($_POST['role_cat_name'])) {
	$role_cat_name = $_POST['role_cat_name'];
    $role_cat_icon = $_POST['role_cat_icon'];
    $role_cat_id = $_POST['role_cat_id'];
	$query = "UPDATE roles_categories SET role_cat_name = ?, role_cat_icon = ? WHERE role_cat_id = ?";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
    	mysqli_stmt_bind_param($stmt, "ssi", $role_cat_name, $role_cat_icon, $role_cat_id);
        
      	if (mysqli_stmt_execute($stmt)) {
            echo "Record updated successfully.";
        } else {
         	error_log("Error executing update statement.");
            http_response_code(500);
            echo "Oops! Something went wrong. Please try again later.";
        }
		mysqli_stmt_close($stmt);
    } else {
     	error_log("Error preparing update statement.");
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
    }
}
mysqli_close($dbc);