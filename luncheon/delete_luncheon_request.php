<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['luncheon_id'])) {
	$query = "DELETE FROM luncheon WHERE luncheon_id = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_POST['luncheon_id']);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
 	   	http_response_code(500);
        die("Failed to prepare statement");
    }
}
mysqli_close($dbc);