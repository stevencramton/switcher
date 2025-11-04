<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_groups_manage')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['group_id'])) {

	$service_group_query = "SELECT * FROM service_groups WHERE group_id = ?";
    $stmt = mysqli_prepare($dbc, $service_group_query);
    mysqli_stmt_bind_param($stmt, 'i', $_POST['group_id']);
    mysqli_stmt_execute($stmt);
    $service_group_result = mysqli_stmt_get_result($stmt);
    $row_old = mysqli_fetch_array($service_group_result);
    mysqli_stmt_close($stmt);

    $old_service_group_name = strip_tags($row_old['group_name']);
    
    $group_name = strip_tags($_POST['group_name']);
    $group_color = strip_tags($_POST['group_color']);
    $group_custom_color = strip_tags($_POST['group_custom_color']);
    $group_icon_1 = strip_tags($_POST['group_icon_1']);
    $group_icon_2 = strip_tags($_POST['group_icon_2']);    
    $group_icon_3 = strip_tags($_POST['group_icon_3']);    
    $group_tags = strip_tags($_POST['group_tags']);
    $group_edited_by = strip_tags($_SESSION['display_name']);
    date_default_timezone_set("America/New_York");
    $group_date_edited = date('m-d-Y g:i A');

    $query = "UPDATE service_groups SET group_name = ?, group_color = ?, group_custom_color = ?, group_icon_1 = ?, group_icon_2 = ?, group_icon_3 = ?, group_tags = ?, group_edited_by = ?, group_date_edited = ? WHERE group_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sssssssssi', $group_name, $group_color, $group_custom_color, $group_icon_1, $group_icon_2, $group_icon_3, $group_tags, $group_edited_by, $group_date_edited, $_POST['group_id']);

    if (!mysqli_stmt_execute($stmt)) {
        $response = "failure";
        exit();
    } else {
        $response = "success";
    }
    
    mysqli_stmt_close($stmt);
	echo json_encode($response);

	$audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Updated Service Group </span>';
    $audit_action = 'Updated Service Group';
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = 'On ' . $audit_date . ' ' . $audit_first_name . ' ' . $audit_last_name . ' updated Service Group from: ' . $old_service_group_name . ' to: ' . $group_name;
    $group_name_short = (strlen($group_name) > 30) ? substr($group_name, 0, 30) . '...' : $group_name;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $group_name_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($dbc);