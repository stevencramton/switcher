<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_group_delete')){
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['verse_id'])) {
	$verse_id = strip_tags($_POST['verse_id']);
	$verse_ids = explode(',', $verse_id);
	$placeholders = implode(',', array_fill(0, count($verse_ids), '?'));
    $query = "DELETE FROM verse_notes WHERE verse_note_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);
	$types = str_repeat('i', count($verse_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$verse_ids);

	if (!mysqli_stmt_execute($stmt)) {
        exit();
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);