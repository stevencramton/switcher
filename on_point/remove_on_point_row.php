<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    header("Location:../../index.php?msg1");
}

if (isset($_POST['on_point_id'])) {
	$on_point_id = strip_tags($_POST['on_point_id']);
	$query = "DELETE FROM on_point WHERE on_point_id = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
		mysqli_stmt_bind_param($stmt, 'i', $on_point_id);
		mysqli_stmt_execute($stmt);
		if (mysqli_stmt_affected_rows($stmt) > 0) {
            $result = true;
        } else {
            $result = false;
        }
		confirmQuery($result);
		mysqli_stmt_close($stmt);
    } else {
        die('Query Failed.');
    }
}
mysqli_close($dbc);