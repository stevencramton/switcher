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

$on_point_backup_time = "";
$on_point_backup_monday = "";

if (isset($_POST['on_point_backup_time'])) {
    $on_point_backup_time = strip_tags($_POST['on_point_backup_time']);
}

if (isset($_POST['on_point_backup_monday'])) {
    $on_point_backup_monday = strip_tags($_POST['on_point_backup_monday']);
}

$last_display_order_query = "SELECT on_point_backup_display_order FROM on_point_backup ORDER BY on_point_backup_id DESC LIMIT 1";

if ($last_display_order_result = mysqli_query($dbc, $last_display_order_query)) {
    $last_display_order_row = mysqli_fetch_array($last_display_order_result);
    $last_display_order = $last_display_order_row ? $last_display_order_row['on_point_backup_display_order'] : 0;
    $new_display_order = $last_display_order + 1;
	$query = "INSERT INTO on_point_backup (on_point_backup_time, on_point_backup_monday, on_point_backup_display_order) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssi', $on_point_backup_time, $on_point_backup_monday, $new_display_order);
        if (mysqli_stmt_execute($stmt)) {

        } else {
            die('Query Failed.');
        }
        mysqli_stmt_close($stmt);
    } else {
        die('Query Failed.');
    }
} else {
    die('Query Failed.');
}

mysqli_close($dbc);