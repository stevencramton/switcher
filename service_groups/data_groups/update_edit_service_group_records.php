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
	$serve_id = strip_tags($_POST['serve_id']);
    $group_name = strip_tags($_POST['group_name']);
    $group_tags = strip_tags($_POST['group_tags']);
	$query = "UPDATE service_groups SET group_name = ?, group_tags = ? WHERE group_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
  	  mysqli_stmt_bind_param($stmt, "ssi", $group_name, $group_tags, $serve_id);
    	if (mysqli_stmt_execute($stmt)) {
            $response = "success";
        } else {
            $response = "failure";
        }
    	mysqli_stmt_close($stmt);
    } else {
        $response = "failure";
    }
 	echo json_encode($response);
}
mysqli_close($dbc);