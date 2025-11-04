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

if (isset($_POST['id'])) {
	$user_id = strip_tags($_POST['id']);
    $role_id = strip_tags($_POST['role_id']);
	$query = "UPDATE users SET role_id = ? WHERE id = ?";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, 'ii', $role_id, $user_id);
		
		if (!mysqli_stmt_execute($stmt)) {
            echo "Error description.";
        }
		mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }

    if ($_SESSION['id'] == $user_id) {
        generateRoleArrays($role_id);
    }
}
mysqli_close($dbc);