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

if(isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$stmt = mysqli_prepare($dbc, "UPDATE links SET link_display_order = ? WHERE link_id = ?");

    for($i = 0; $i < count($id_ary); $i++) {
        $order = $i;
        $link_id = $id_ary[$i];

		mysqli_stmt_bind_param($stmt, "ii", $order, $link_id);
		mysqli_stmt_execute($stmt);
    }

	mysqli_stmt_close($stmt);
}

mysqli_close($dbc);