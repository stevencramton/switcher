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

if (!checkRole('user_role')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['role_cat_name'], $_POST['role_cat_icon'])) {
	$role_cat_name = strip_tags($_POST['role_cat_name']);
    $role_cat_icon = strip_tags($_POST['role_cat_icon']);
	$query = "INSERT INTO roles_categories (role_cat_name, role_cat_icon) VALUES (?, ?)";

	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, "ss", $role_cat_name, $role_cat_icon);

		if (mysqli_stmt_execute($stmt)) {
          	echo "Role category added successfully.";
        } else {
        	error_log("Error executing prepared statement.");
            http_response_code(500);
            echo "Oops! Something went wrong. Please try again later.";
        }
		mysqli_stmt_close($stmt);
    } else {
   	 	error_log("Error preparing statement.");
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
    }
}
mysqli_close($dbc);