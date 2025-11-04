<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['selected_values'])) {
	$luncheon_ids = array_map('intval', explode(',', strip_tags($_POST['selected_values'])));
 	$placeholders = implode(',', array_fill(0, count($luncheon_ids), '?'));
 	$query = "DELETE FROM luncheon WHERE luncheon_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
     	mysqli_stmt_bind_param($stmt, str_repeat('i', count($luncheon_ids)), ...$luncheon_ids);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
       	http_response_code(500);
        die("Failed to prepare statement");
    }
}
mysqli_close($dbc);