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

if (isset($_POST['role_type_name'])) {
	$role_type_name = strip_tags($_POST['role_type_name']);
    $role_type_cat = strip_tags($_POST['role_type_cat']);
    $role_type_db = strip_tags($_POST['role_type_db']);
    $role_type_description = strip_tags($_POST['role_description']);

	if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $role_type_db)) {
        die('Invalid column name');
    }
    
	$query = "INSERT INTO roles_type (role_type_name, role_type_db_name, role_type_category, role_type_description) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'ssss', $role_type_name, $role_type_db, $role_type_cat, $role_type_description);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Record Added!";
        } else {
            echo "Error: Unable to add the record.";
        }
 	   	mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }

	if (isset($_POST['current_role_id'])) {
        $current_role_id = strip_tags($_POST['current_role_id']);
    }

	$add_column_query = "ALTER TABLE roles_dev ADD `" . mysqli_real_escape_string($dbc, $role_type_db) . "` INT(3) NOT NULL DEFAULT 0";
    if (!$add_column_result = mysqli_query($dbc, $add_column_query)) {
        echo "Error: Unable to add the column.";
    }

    generateRoleArrays($current_role_id);
    setRoleSessionVariables();
}

mysqli_close($dbc);