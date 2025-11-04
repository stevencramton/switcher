<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {
	$poll_id = strip_tags($_POST['inquiry_id']);
    $poll_users = explode(',', $_POST['assignment_user']);
	$placeholders = implode(',', array_fill(0, count($poll_users), '?'));
  	$query_two = "DELETE FROM poll_assignment WHERE poll_id = ? AND assignment_user IN ($placeholders)";

    $stmt = mysqli_prepare($dbc, $query_two);
    if ($stmt === false) {
  	  	echo json_encode(array('status' => 'error', 'message'));
        exit();
    }

	$types = str_repeat('s', count($poll_users));
    mysqli_stmt_bind_param($stmt, 'i' . $types, $poll_id, ...$poll_users);

    if (!mysqli_stmt_execute($stmt)) {
    	echo json_encode(array('status' => 'error', 'message'));
        exit();
    }

    mysqli_stmt_close($stmt);

    echo json_encode(array('status' => 'success', 'message' => 'User(s) have been removed.'));
    exit();
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request.'));
exit();

mysqli_close($dbc);