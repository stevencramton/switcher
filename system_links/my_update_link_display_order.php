<?php
session_start();
include '../../mysqli_connect.php';

if (isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$stmt = mysqli_prepare($dbc, "UPDATE links SET link_display_order = ? WHERE link_id = ?");

    if ($stmt === false) {
        die("Statement preparation failed.");
    }

    for ($i = 0; $i < count($id_ary); $i++) {
  	  	mysqli_stmt_bind_param($stmt, 'ii', $i, $id_ary[$i]);
		if (!mysqli_stmt_execute($stmt)) {
            die("Execution failed.");
        }
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);