<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("Forbidden");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['info_confirm_id'])) {
	$confirm_ids = explode(',', $_POST['info_confirm_id']);
	$sanitized_ids = array_map('intval', $confirm_ids);
	$placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    $query = "DELETE FROM info_confirm WHERE info_confirm_id IN ($placeholders)";
	$stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        http_response_code(500);
        die("Database error.");
    }
	$param_types = str_repeat("i", count($sanitized_ids));
    mysqli_stmt_bind_param($stmt, $param_types, ...$sanitized_ids);
	if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        die("Error");
    }
	mysqli_stmt_close($stmt);
	http_response_code(200);
    echo "Info reports deleted successfully";
} else {
	http_response_code(400);
    die("Invalid request: No valid info IDs provided");
}
mysqli_close($dbc);