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

if (isset($_POST['on_point_backup_id'])) {
    $on_point_backup_id = $_POST['on_point_backup_id'];
	$query = "DELETE FROM on_point_backup WHERE on_point_backup_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
        die('Query Failed.');
    }

	mysqli_stmt_bind_param($stmt, "i", $on_point_backup_id);
	mysqli_stmt_execute($stmt);

	$affected_rows = mysqli_stmt_affected_rows($stmt);
    if ($affected_rows == 1) {
 	} else {
	}
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);