<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if(isset($_GET["sort_order_backup"])) {
    $id_ary = explode(",", $_GET["sort_order_backup"]);
	$query = "UPDATE on_point_backup SET on_point_backup_display_order = ? WHERE on_point_backup_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
        die('Query Failed.');
    }

	$display_order = 0;

    foreach ($id_ary as $backup_id) {
        $backup_id = intval($backup_id);
		mysqli_stmt_bind_param($stmt, "ii", $display_order, $backup_id);
		mysqli_stmt_execute($stmt) or die("Error.");
		$display_order++;
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);