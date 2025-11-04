<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['id']) && !empty($_POST['id'])) {
	$ids = $_POST['id'];
 	$idArray = explode(',', $ids);
	$placeholders = implode(',', array_fill(0, count($idArray), '?'));
	$query = "DELETE FROM user_xp WHERE id IN ($placeholders)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
   	 mysqli_stmt_bind_param($stmt, str_repeat('i', count($idArray)), ...$idArray);
		if (mysqli_stmt_execute($stmt)) {
            echo "Records deleted successfully";
        } else {
            exit("Error executing query.");
        }
		mysqli_stmt_close($stmt);
    } else {
        exit("Error preparing query.");
    }
}
mysqli_close($dbc);