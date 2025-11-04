<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['gem_display'])) {
	$gem_display = strip_tags($_POST['gem_display']);
    $switch_id = strip_tags($_POST['switch_id']);

    if ($gem_display == 1) {
        $gem_display_query = "UPDATE user_settings SET gem_display = ? WHERE user_settings_switch_id = ?";
        $stmt = mysqli_prepare($dbc, $gem_display_query);
        mysqli_stmt_bind_param($stmt, 'ii', $gem_display, $switch_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            exit();
        }
        
        mysqli_stmt_close($stmt);
        $response = "success";

    } else {
        $gem_display = 0;
        $gem_display_query = "UPDATE user_settings SET gem_display = ? WHERE user_settings_switch_id = ?";
        $stmt = mysqli_prepare($dbc, $gem_display_query);
        mysqli_stmt_bind_param($stmt, 'ii', $gem_display, $switch_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            exit();
        }
		mysqli_stmt_close($stmt);
        $response = "success";
    }
	echo json_encode($response);
}
mysqli_close($dbc);