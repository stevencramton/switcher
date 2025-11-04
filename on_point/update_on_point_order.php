<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    http_response_code(403);
    die("Unauthorized");
}

if(isset($_GET["sort_order"])) {
    $id_ary = explode(",", $_GET["sort_order"]);
	$query = "UPDATE on_point SET on_point_display_order=? WHERE on_point_id=?";
	$stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
		for($i = 0; $i < count($id_ary); $i++) {
			mysqli_stmt_bind_param($stmt, "ii", $i, $id_ary[$i]);
			mysqli_stmt_execute($stmt);

			if(mysqli_stmt_error($stmt)) {
                http_response_code(500);
                die('Query Failed.');
            }
        }
		mysqli_stmt_close($stmt);
    } else {
        http_response_code(500);
        die("Failed to prepare statement");
    }
}
mysqli_close($dbc);