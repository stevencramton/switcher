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

if (isset($_POST['id'])) {
	$delete_my_links = $_SESSION['user'];
    $id = $_POST['id'];
	$query = "DELETE FROM my_links WHERE my_link_created_by = ? AND my_link_id = ? LIMIT 1";
	$stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die('Error.');
    }
	mysqli_stmt_bind_param($stmt, "si", $delete_my_links, $id);
	$execute = mysqli_stmt_execute($stmt);
    if ($execute === false) {
        die('Error.');
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);