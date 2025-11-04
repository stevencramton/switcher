<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_restore')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['message_id'])) {
	$message_id = $_POST['message_id'];
	$message_ids = explode(',', $message_id);
 	$placeholders = implode(',', array_fill(0, count($message_ids), '?'));
	$query = "UPDATE messages SET trash = 0 WHERE id IN ($placeholders)";

    if ($stmt = mysqli_prepare($dbc, $query)) {
		$types = str_repeat('i', count($message_ids));
 	   	mysqli_stmt_bind_param($stmt, $types, ...$message_ids);
		
		if (mysqli_stmt_execute($stmt)) {
       	 	if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo 'Messages restored successfully.';
            } else {
                echo 'No messages were found with the specified IDs.';
            }
        } else {
            die('Error.');
        }
		mysqli_stmt_close($stmt);
    } else {
        die('Error.');
    }
	mysqli_close($dbc);
} else {
    echo 'Invalid input.';
}