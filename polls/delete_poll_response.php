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

if (isset($_SESSION['id']) && isset($_POST['response_id'])) {
	$response_id = strip_tags($_POST['response_id']);
 	$query = "DELETE FROM poll_response WHERE response_id = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "i", $response_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $response = "success";
    } else {
        $response = "failure";
    }

    mysqli_stmt_close($stmt);
	echo json_encode($response);
}
mysqli_close($dbc);