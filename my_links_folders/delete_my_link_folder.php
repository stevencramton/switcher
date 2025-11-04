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

if (isset($_POST['my_folder_id'])) {
	$delete_my_folders = strip_tags($_SESSION['user']);
    $my_folder_id = strip_tags($_POST['my_folder_id']);
	$query = "DELETE FROM my_links_folders WHERE my_folder_created_by = ? AND my_folder_id = ? LIMIT 1";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'ss', $delete_my_folders, $my_folder_id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
		confirmQuery($result);
    } else {
        die('Error failed');
    }
}
mysqli_close($dbc);