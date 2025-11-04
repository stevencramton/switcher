<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    header("Location:../../index.php?msg1");
}

if (isset($_POST['on_point_time']) && isset($_POST['on_point_monday'])) {
    $on_point_time = strip_tags($_POST['on_point_time']);
    $on_point_monday = strip_tags($_POST['on_point_monday']);
} else {
    $on_point_time = "";
    $on_point_monday = "";
}

$last_display_order_query = "SELECT * FROM on_point ORDER BY on_point_id DESC LIMIT 1";

if ($last_display_order_result = mysqli_query($dbc, $last_display_order_query)) {
    while ($last_display_order_row = mysqli_fetch_array($last_display_order_result)) {
        $last_display_order = $last_display_order_row['on_point_display_order'];
    }
}

$new_display_order = $last_display_order + 1;
$query = "INSERT INTO on_point (on_point_time, on_point_monday, on_point_display_order) VALUES (?, ?, ?)";

if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_bind_param($stmt, 'ssi', $on_point_time, $on_point_monday, $new_display_order);
    if (!mysqli_stmt_execute($stmt)) {
        die('Query Failed.');
    }
    mysqli_stmt_close($stmt);
} else {
    die('Query Failed.');
}
mysqli_close($dbc);