<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('message_move_to_trash')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['message_id'])) {
	$message_ids = explode(',', mysqli_real_escape_string($dbc, strip_tags($_POST['message_id'])));
    $trash_my_message = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));

	$placeholders = implode(',', array_fill(0, count($message_ids), '?'));
    $query = "UPDATE messages SET trash = 1 WHERE recipient = ? AND id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
        die('Error.');
    }

	$types = str_repeat('i', count($message_ids));
    $params = array_merge([$trash_my_message], $message_ids);
    $types = 's' . $types;

	mysqli_stmt_bind_param($stmt, $types, ...$params);

	$execute_result = mysqli_stmt_execute($stmt);

    if ($execute_result === false) {
        die('Error.');
    }

	mysqli_stmt_close($stmt);
} else {
    http_response_code(400);
    die('Invalid request');
}

mysqli_close($dbc);