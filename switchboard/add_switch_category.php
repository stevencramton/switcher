<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_categories')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['switch_cat_title'])) {
	$switch_cat_title = strip_tags($_POST['switch_cat_title']);
    $switch_cat_color = strip_tags($_POST['switch_cat_color']);
    $switch_cat_icon = strip_tags($_POST['switch_cat_icon']);
	$last_display_order_query = "SELECT * FROM switchboard_categories ORDER BY switchboard_cat_display_order DESC LIMIT 1";
    $last_display_order_result = mysqli_query($dbc, $last_display_order_query);
    $last_display_order_row = mysqli_fetch_array($last_display_order_result);
	$last_display_order = $last_display_order_row['switchboard_cat_display_order'];
	$new_display_order = $last_display_order + 1;

    $query = "INSERT INTO switchboard_categories (switchboard_cat_display_order, switchboard_cat_name, switchboard_cat_icon, switchboard_cat_color) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'isss', $new_display_order, $switch_cat_title, $switch_cat_icon, $switch_cat_color);

    if (!mysqli_stmt_execute($stmt)) {
        echo("Error description.");
    }
    mysqli_stmt_close($stmt);

 	$audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-solid fa-circle-check"></i> Created Switchboard Category </span>';
    $audit_action = 'Created Switchboard Category';
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = '<span class="dark-gray fw-bold">Switchboard Category</span>:' . ' ' . $switch_cat_title 
    . '<br>' . '<span class="dark-gray fw-bold">Category Color</span>:' . ' ' . $switch_cat_color
    . '<br>' . '<span class="dark-gray fw-bold">Category Icon</span>:' . ' ' . $switch_cat_icon . ' ' . '<span class="float-end"><i class="' . $switch_cat_icon . '" style="color: ' . $switch_cat_color . ';"></i></span>';
    
    $switch_cat_title = (strlen($switch_cat_title) > 30) ? substr($switch_cat_title, 0, 30).'...' : $switch_cat_title;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $switch_cat_title, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $audit_result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    confirmQuery($audit_result);
}

mysqli_close($dbc);