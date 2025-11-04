<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {

    $id_ary = explode(",", $_GET["sort_order"]);
	$spotlight_user = strip_tags($_SESSION['user']);
	$stmt = mysqli_prepare($dbc, "UPDATE spotlight_assignment SET assignment_display_order = ? WHERE assignment_user = ? AND spotlight_id = ?");

    if ($stmt === false) {
        die('Statement Failed.');
    }

    for ($i = 0; $i < count($id_ary); $i++) {
     	mysqli_stmt_bind_param($stmt, 'isi', $i, $spotlight_user, $id_ary[$i]);

       	if (!mysqli_stmt_execute($stmt)) {
            die('Query Failed.');
        }
    }

  	mysqli_stmt_close($stmt);
}

mysqli_close($dbc);