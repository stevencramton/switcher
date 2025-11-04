<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_group_edit')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$stmt = mysqli_prepare($dbc, "UPDATE verse_groups SET verse_group_display_order = ? WHERE verse_group_id = ?");

    if ($stmt === false) {
        die("database error.");
    }

    for ($i = 0; $i < count($id_ary); $i++) {
		mysqli_stmt_bind_param($stmt, "is", $i, $id_ary[$i]);
		if (!mysqli_stmt_execute($stmt)) {
            die("database error.");
        }
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);