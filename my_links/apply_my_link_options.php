<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location: ../../index.php?msg1");
    exit;
}

if (isset($_POST['emp_id'])) {
	$switch_id = $_SESSION['switch_id'];
    $emp_id = $_POST['emp_id'];
	$query = "UPDATE user_settings SET my_link_select = ? WHERE user_settings_switch_id = ?";
	$stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die('Error.');
    }
	mysqli_stmt_bind_param($stmt, "ss", $emp_id, $switch_id);
	$execute = mysqli_stmt_execute($stmt);
    if ($execute === false) {
        die('Error.');
    }
	mysqli_stmt_close($stmt);
	mysqli_close($dbc);   
}