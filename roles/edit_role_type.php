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

if (isset($_POST['role_type_name'], $_POST['role_type_cat'], $_POST['role_type_db'], $_POST['role_type_id'], $_POST['role_description'])) {
	$role_type_name = strip_tags($_POST['role_type_name']);
    $role_type_cat = strip_tags($_POST['role_type_cat']);
    $role_type_db = strip_tags($_POST['role_type_db']);
    $role_type_id = (int)$_POST['role_type_id'];
    $role_type_description = strip_tags($_POST['role_description']);

    if (!empty($_POST['current_role_id'])) {
        $current_role_id = (int)$_POST['current_role_id'];
        generateRoleArrays($current_role_id);
    }

	$existing_query = "SELECT role_type_db_name FROM roles_type WHERE role_type_id = ?";
    if ($stmt_existing = mysqli_prepare($dbc, $existing_query)) {
   	 	mysqli_stmt_bind_param($stmt_existing, "i", $role_type_id);
   	 	mysqli_stmt_execute($stmt_existing);
        mysqli_stmt_bind_result($stmt_existing, $existing_db_name);
        mysqli_stmt_fetch($stmt_existing);
        mysqli_stmt_close($stmt_existing);
        
		if (preg_match('/^[a-zA-Z0-9_]+$/', $existing_db_name) && preg_match('/^[a-zA-Z0-9_]+$/', $role_type_db)) {
            if ($role_type_db !== $existing_db_name) {
               	$update_column_query = "ALTER TABLE roles_dev CHANGE `" . mysqli_real_escape_string($dbc, $existing_db_name) . "` `" . mysqli_real_escape_string($dbc, $role_type_db) . "` int(3)";
                if ($stmt_update_column = mysqli_prepare($dbc, $update_column_query)) {
                    if (!mysqli_stmt_execute($stmt_update_column)) {
                        echo("Error description.");
                    }
                    mysqli_stmt_close($stmt_update_column);
                } else {
                    echo("Error preparing update column statement.");
                }
            }
        } else {
            echo "Invalid column names provided.";
            exit();
        }
    } else {
        echo("Error preparing existing role query.");
    }

	$query = "UPDATE roles_type SET role_type_name = ?, role_type_db_name = ?, role_type_category = ?, role_type_description = ? WHERE role_type_id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
   	 	mysqli_stmt_bind_param($stmt, "ssssi", $role_type_name, $role_type_db, $role_type_cat, $role_type_description, $role_type_id);
		if (!mysqli_stmt_execute($stmt)) {
            echo("Error description.");
        } else {
            echo "Update successful.";
        }
		mysqli_stmt_close($stmt);
    } else {
        echo("Error preparing update statement.");
    }
    setRoleSessionVariables();
}

mysqli_close($dbc);