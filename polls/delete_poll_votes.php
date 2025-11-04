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

if (isset($_SESSION['id']) && isset($_POST['id'])) {
	$poll_id = strip_tags($_POST['id']);
    $poll_ids = explode(',', $poll_id);
	$placeholders = implode(',', array_fill(0, count($poll_ids), '?'));
	$query = "DELETE FROM poll_ballot WHERE question_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);
	$types = str_repeat('i', count($poll_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$poll_ids);
	mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
	$query_two = "UPDATE poll_assignment SET assignment_read = 1 WHERE poll_id IN ($placeholders)";
    $stmt_two = mysqli_prepare($dbc, $query_two);
	mysqli_stmt_bind_param($stmt_two, $types, ...$poll_ids);
	mysqli_stmt_execute($stmt_two);
    mysqli_stmt_close($stmt_two);
}
mysqli_close($dbc);