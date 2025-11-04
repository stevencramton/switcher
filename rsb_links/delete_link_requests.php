<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_links_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['link_request_id'])) {
    $link_request_id = strip_tags($_POST['link_request_id']);
	$query = "DELETE FROM links_request WHERE link_request_id = ? LIMIT 1";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $link_request_id);
        $result = mysqli_stmt_execute($stmt);
        confirmQuery($result);
        mysqli_stmt_close($stmt);
    } else {
      	confirmQuery(false);
    }
}

mysqli_close($dbc);
?>