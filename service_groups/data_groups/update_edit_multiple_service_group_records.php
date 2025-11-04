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

if (isset($_POST['serve_id'], $_POST['group_name'], $_POST['group_tags'])) {
	$serve_id_array = array_map('strip_tags', explode(',', $_POST['serve_id']));
    $group_name = strip_tags($_POST['group_name'] ?? '');
    $group_tags = strip_tags($_POST['group_tags'] ?? '');
	$placeholders = implode(',', array_fill(0, count($serve_id_array), '?'));
  	$query = "UPDATE service_groups SET group_name = ?, group_tags = ? WHERE group_id IN ($placeholders)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        $types = 'ss' . str_repeat('i', count($serve_id_array));
        $params = array_merge([$group_name, $group_tags], $serve_id_array);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (!mysqli_stmt_execute($stmt)) {
            $response = "failure";
            exit();
        } else {
            $response = "success";
        }
        mysqli_stmt_close($stmt);
    } else {
        $response = "failure";
        exit();
    }
	echo json_encode($response);
}
mysqli_close($dbc);