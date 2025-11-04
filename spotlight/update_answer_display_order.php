<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {
 	$sort_order = mysqli_real_escape_string($dbc, strip_tags($_GET["sort_order"]));
    $id_ary = explode(",", $sort_order);

 	$query = "UPDATE spotlight_response SET response_display_order = ? WHERE response_id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
      	foreach ($id_ary as $i => $id) {
        	$id = intval($id);
			mysqli_stmt_bind_param($stmt, 'ii', $i, $id);

         	if (!mysqli_stmt_execute($stmt)) {
                die('Query Failed.');
            }
        }

      	mysqli_stmt_close($stmt);
    } else {
        die('Query Prep Failed.');
    }

  	mysqli_close($dbc);
}