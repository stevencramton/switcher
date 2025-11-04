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

if (isset($_POST['role_name'])) {
    $role_name = strip_tags($_POST['role_name']);
    $role_icon = strip_tags($_POST['role_icon']);
    $role_description = strip_tags($_POST['role_description']);
    
	$allowed_columns = [];
    $col_query = "SELECT role_type_db_name FROM roles_type";
    if ($col_result = mysqli_query($dbc, $col_query)) {
        while ($row = mysqli_fetch_assoc($col_result)) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $row['role_type_db_name'])) {
                $allowed_columns[] = $row['role_type_db_name'];
            }
        }
        mysqli_free_result($col_result);
    } else {
        error_log("Error fetching role types: " . mysqli_error($dbc));
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
        exit();
    }
    
    if (empty($allowed_columns)) {
        http_response_code(500);
        echo "No valid role types found.";
        exit();
    }
    
	$user_selected_set = array_flip(array_map('trim', explode(",", $_POST['role_db_names_checked'])));
    $user_unselected_set = isset($_POST['role_db_names_unchecked']) 
        ? array_flip(array_map('trim', explode(",", $_POST['role_db_names_unchecked']))) 
        : [];
    
	$checked_cols = [];
    $unchecked_cols = [];
    
    foreach ($allowed_columns as $safe_column_name) {
        if (isset($user_selected_set[$safe_column_name])) {
            $checked_cols[] = $safe_column_name;
        } elseif (isset($user_unselected_set[$safe_column_name])) {
            $unchecked_cols[] = $safe_column_name;
        }
    }
    
    if (empty($checked_cols) && empty($unchecked_cols)) {
        http_response_code(400);
        echo "No valid permissions selected.";
        exit();
    }
    
	$column_names = array_merge($checked_cols, $unchecked_cols);
    $column_list = [];
    $placeholders = [];
    
    foreach ($column_names as $safe_col) {
        $column_list[] = "`" . $safe_col . "`";
        $placeholders[] = "?";
    }
    
    $columns_str = implode(", ", $column_list);
    $placeholders_str = implode(", ", $placeholders);
    
 	$query = "INSERT INTO roles_dev (role_name, role_icon, role_description, " . $columns_str . ") 
              VALUES (?, ?, ?, " . $placeholders_str . ")";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
     	$param_values = [$role_name, $role_icon, $role_description];
        
        foreach ($checked_cols as $col) {
            $param_values[] = '1';
        }
        
        foreach ($unchecked_cols as $col) {
            $param_values[] = '0';
        }
        
        $types = str_repeat('s', count($param_values));
        mysqli_stmt_bind_param($stmt, $types, ...$param_values);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Role added successfully.";
        } else {
            error_log("Error executing prepared statement: " . mysqli_stmt_error($stmt));
            http_response_code(500);
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing statement: " . mysqli_error($dbc));
        http_response_code(500);
        echo "Oops! Something went wrong. Please try again later.";
    }
}

mysqli_close($dbc);