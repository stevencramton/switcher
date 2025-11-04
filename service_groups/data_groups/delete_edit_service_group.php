<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_groups_manage')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_POST['serve_id'])) {
	$serve_ids = array_map('strip_tags', explode(',', $_POST['serve_id']));
    $placeholders = implode(',', array_fill(0, count($serve_ids), '?'));
	$query = "DELETE FROM service_groups WHERE group_id IN ($placeholders)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
   	 	$types = str_repeat('i', count($serve_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$serve_ids);
        
		if (!mysqli_stmt_execute($stmt)) {
            exit();
        }
     	mysqli_stmt_close($stmt);
    } else {
        exit();
    }
}
mysqli_close($dbc);