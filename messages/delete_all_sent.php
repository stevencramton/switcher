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
    $message_sent_id = $_POST['message_id'];
	$message_ids = explode(',', $message_sent_id);
	$placeholders = implode(',', array_fill(0, count($message_ids), '?'));
    $query_sent = "DELETE FROM messages_sent WHERE id IN ($placeholders)";
    $stmt_sent = mysqli_prepare($dbc, $query_sent);
    if ($stmt_sent === false) {
        die('Error.');
    }

    $types = str_repeat('i', count($message_ids));
    mysqli_stmt_bind_param($stmt_sent, $types, ...$message_ids);
    $execute_result_sent = mysqli_stmt_execute($stmt_sent);
    if ($execute_result_sent === false) {
        die('Error.');
    }

    mysqli_stmt_close($stmt_sent);
	$query_messages = "DELETE FROM messages WHERE id IN ($placeholders)";
    $stmt_messages = mysqli_prepare($dbc, $query_messages);
    if ($stmt_messages === false) {
        die('Error.');
    }

    mysqli_stmt_bind_param($stmt_messages, $types, ...$message_ids);
    $execute_result_messages = mysqli_stmt_execute($stmt_messages);
    if ($execute_result_messages === false) {
        die('Error.');
    }
    mysqli_stmt_close($stmt_messages);
} else {
}
mysqli_close($dbc);