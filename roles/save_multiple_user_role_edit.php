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

if (isset($_POST['selected_values'])) {
	$user_ids = explode(",", $_POST['selected_values']);
    $role_id = strip_tags($_POST['role']);
	$placeholders = implode(',', array_fill(0, count($user_ids), '?'));
	$query = "UPDATE users SET role_id = ? WHERE id IN ($placeholders)";
    if ($stmt = mysqli_prepare($dbc, $query)) {
		$types = str_repeat('i', count($user_ids) + 1);
        $params = array_merge([$role_id], $user_ids);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
 	   	if (!mysqli_stmt_execute($stmt)) {
            echo "Error description.";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
    
    foreach ($user_ids as $user_id) {
        if ($_SESSION['id'] == $user_id) {
            generateRoleArrays($role_id);
            setRoleSessionVariables();
        }
    }
}
mysqli_close($dbc);