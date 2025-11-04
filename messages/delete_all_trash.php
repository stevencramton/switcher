<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_delete')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['message_id'])) {
    $msg_user_first = htmlspecialchars(strip_tags($_SESSION['first_name']));
    $msg_user_last = htmlspecialchars(strip_tags($_SESSION['last_name']));
    $msg_user_full = $msg_user_first . ' ' . $msg_user_last;
	$delete_my_message = htmlspecialchars(strip_tags($_SESSION['user']));
    $message_id = $_POST['message_id'];
	$message_ids = explode(',', $message_id);
	$placeholders = implode(',', array_fill(0, count($message_ids), '?'));

    $query = "DELETE FROM messages WHERE (recipient = ? OR sender = ?) AND id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die('Error.');
    }

	$types = 'ss' . str_repeat('i', count($message_ids));
	$params = array_merge([$types, $delete_my_message, $msg_user_full], $message_ids);

	mysqli_stmt_bind_param($stmt, ...$params);
    $execute_result = mysqli_stmt_execute($stmt);
    if ($execute_result === false) {
        die('Error.');
    }

    mysqli_stmt_close($stmt);
} else {}
mysqli_close($dbc);