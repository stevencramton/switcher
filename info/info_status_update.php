<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('info_admin')) {
 	 header("Location:../../index.php?msg1");
	 exit();
}

if (isset($_POST['display_info_id']) && isset($_POST['status'])) {
    $info_ids = explode(',', $_POST['display_info_id']);
    $status = intval($_POST['status']);
	$placeholders = implode(',', array_fill(0, count($info_ids), '?'));
	$sql = "UPDATE info SET info_status = ? WHERE info_id IN ($placeholders)";
    $stmt = $dbc->prepare($sql);
	$types = str_repeat('i', count($info_ids) + 1);
    $params = array_merge([$status], $info_ids);
 	$stmt->bind_param($types, ...$params);
    $stmt->execute();
	
	if ($dbc->affected_rows > 0) {
        echo 'success';
    } else {
        echo 'error';
    }
}
mysqli_close($dbc);