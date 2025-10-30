<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_group_delete')) {
    header("Location: ../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'], $_POST['vgrp_id'])) {
	$vgrp_id = $_POST['vgrp_id'];
	$query = "DELETE FROM verse_groups WHERE verse_group_id = ?";
	$ids = explode(",", $vgrp_id);
    
	foreach ($ids as $id) {
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, 's', $id);
    	mysqli_stmt_execute($stmt);
    	mysqli_stmt_close($stmt);
    }
	if (!$stmt) {
        exit();
    }
}
mysqli_close($dbc);