<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$query = "UPDATE skills SET skill_display_order = ? WHERE skill_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        for ($i = 0; $i < count($id_ary); $i++) {
            mysqli_stmt_bind_param($stmt, 'ii', $i, $id_ary[$i]);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        die("database error.");
    }
}
mysqli_close($dbc);