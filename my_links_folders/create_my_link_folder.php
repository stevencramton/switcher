<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['my_folder_name'])) {
	$my_folder_name = strip_tags($_POST['my_folder_name']);
    $my_folder_description = strip_tags($_POST['my_folder_description']);
    $my_folder_created_by = strip_tags($_SESSION['user']);
    $switch_id = strip_tags($_SESSION['switch_id']);

    $query = "INSERT INTO my_links_folders (my_folder_name, my_folder_description, my_folder_created_by, switch_id) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'ssss', $my_folder_name, $my_folder_description, $my_folder_created_by, $switch_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        confirmQuery($result);
    } else {
        die('Error failed');
    }
}
mysqli_close($dbc);