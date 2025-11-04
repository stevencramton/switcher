<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {
	
	$id_ary = explode(",", $_GET["sort_order"]);
	$stmt = mysqli_prepare($dbc, "UPDATE spotlight_inquiry SET inquiry_display_order = ? WHERE inquiry_id = ?");

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