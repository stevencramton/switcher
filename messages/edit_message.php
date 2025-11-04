<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_edit')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['id'])) {
    $message_id = htmlspecialchars(strip_tags($_POST['id']));
    $subject = htmlspecialchars(strip_tags($_POST['subject']));
    $message = htmlspecialchars(strip_tags($_POST['message']));
    $priority = htmlspecialchars(strip_tags($_POST['priority']));
    $active = htmlspecialchars(strip_tags($_POST['active']));
    $date = htmlspecialchars(strip_tags($_POST['date']));

	$query_messages = "UPDATE messages SET subject = ?, message = ?, priority = ?, active = ?, date = ?, message_read = 1 WHERE id = ?";
    $stmt_messages = mysqli_prepare($dbc, $query_messages);
    if ($stmt_messages === false) {
        die('Error.');
    }
    mysqli_stmt_bind_param($stmt_messages, 'sssssi', $subject, $message, $priority, $active, $date, $message_id);
    $execute_result_messages = mysqli_stmt_execute($stmt_messages);
    if ($execute_result_messages === false) {
        die('Error.');
    }
    mysqli_stmt_close($stmt_messages);

	$query_messages_sent = "UPDATE messages_sent SET subject = ?, message = ?, priority = ?, active = ?, date = ?, message_read = 1 WHERE id = ?";
    $stmt_messages_sent = mysqli_prepare($dbc, $query_messages_sent);
    if ($stmt_messages_sent === false) {
        die('Error.');
    }
    mysqli_stmt_bind_param($stmt_messages_sent, 'sssssi', $subject, $message, $priority, $active, $date, $message_id);
    $execute_result_messages_sent = mysqli_stmt_execute($stmt_messages_sent);
    if ($execute_result_messages_sent === false) {
        die('Error.');
    }
    mysqli_stmt_close($stmt_messages_sent);
}

mysqli_close($dbc);