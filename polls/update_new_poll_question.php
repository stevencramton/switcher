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

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {
	$inquiry_id = strip_tags($_POST['inquiry_id']);
    $inquiry_name = strip_tags($_POST['inquiry_name']);
    $inquiry_info = strip_tags($_POST['inquiry_info']);
    $inquiry_question = strip_tags($_POST['inquiry_question']);
    $inquiry_status = strip_tags($_POST['inquiry_status']);
    $inquiry_image = strip_tags($_POST['inquiry_image']);

    $query = "UPDATE poll_inquiry SET inquiry_name = ?, inquiry_info = ?, inquiry_question = ?, inquiry_status = ?, inquiry_image = ? WHERE inquiry_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sssssi', $inquiry_name, $inquiry_info, $inquiry_question, $inquiry_status, $inquiry_image, $inquiry_id);

    if (!mysqli_stmt_execute($stmt)) {
        echo "Error description.";
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($dbc);