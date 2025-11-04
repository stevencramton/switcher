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

if (isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$query = "UPDATE poll_inquiry SET inquiry_display_order = ? WHERE inquiry_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
        die("Database error.");
    }

    for ($i = 0; $i < count($id_ary); $i++) {
        $inquiry_display_order = $i;
        $inquiry_id = $id_ary[$i];

        mysqli_stmt_bind_param($stmt, 'ii', $inquiry_display_order, $inquiry_id);

        if (!mysqli_stmt_execute($stmt)) {
            die("Database error.");
        }
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);