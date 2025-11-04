<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_POST['selected_values'])){
    $poll_ids = explode(',', strip_tags($_POST['selected_values']));
    $placeholders = implode(',', array_fill(0, count($poll_ids), '?'));
	$query = "UPDATE poll_inquiry SET inquiry_status = 'Closed' WHERE inquiry_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);
	$types = str_repeat('i', count($poll_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$poll_ids);
    mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);

} else {
    $poll_id = strip_tags($_POST['id']);
    $query = "UPDATE poll_inquiry SET inquiry_status = 'Closed' WHERE inquiry_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $poll_id);
    mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
}

mysqli_close($dbc);