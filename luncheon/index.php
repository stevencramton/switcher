<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('luncheon_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (!isset($_SESSION['id'])){
	header("Location:../../index.php?msg1");
	exit();
}
mysqli_close($dbc);
?>