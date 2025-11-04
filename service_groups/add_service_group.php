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

if (isset($_POST['group_name'])) {

    $query = "INSERT INTO service_groups (group_name, group_icon_1, group_icon_2, group_icon_3, group_color, group_custom_color, group_tags, group_created_by, group_date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "sssssssss", $service_group_name, $group_icon_1, $group_icon_2, $group_icon_3, $group_color, $group_custom_color, $service_group_tags, $group_created_by, $group_date_created);

	$service_group_name = strip_tags($_POST['group_name']);
    $group_icon_1 = strip_tags($_POST['group_icon_1']);
    $group_icon_2 = strip_tags($_POST['group_icon_2']);
    $group_icon_3 = strip_tags($_POST['group_icon_3']);
    $group_color = strip_tags($_POST['group_color']);
    $group_custom_color = strip_tags($_POST['group_custom_color']);
    $service_group_tags = strip_tags($_POST['group_tags']);
    $group_created_by = $_SESSION['display_name'];
	date_default_timezone_set("America/New_York");
    $group_date_created = date('m-d-Y g:i A');

    mysqli_stmt_execute($stmt);

	if (mysqli_stmt_affected_rows($stmt) > 0) {
 	   	$audit_user = $_SESSION['user'];
        $audit_first_name = $_SESSION['first_name'];
        $audit_last_name = $_SESSION['last_name'];
        $audit_profile_pic = $_SESSION['profile_pic'];
        $switch_id = $_SESSION['switch_id'];
		date_default_timezone_set("America/New_York");
        $audit_date = date('m-d-Y g:i A');
        $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fab fa-connectdevelop"></i> Created Service Group </span>';
        $audit_action = 'Created Service Group';
        $audit_ip = $_SERVER['REMOTE_ADDR'];
        $audit_source = $_SERVER['REQUEST_URI'];
        $audit_domain = $_SERVER['SERVER_NAME'];

        if ($group_icon_1 == 1) {
            $service_group_icon_1 = 'fa-solid fa-building-columns';
        } else {
            $service_group_icon_1 = '';
        }

        if ($group_icon_2 == 1) {
            $service_group_icon_2 = 'fa-brands fa-unity';
        } else {
            $service_group_icon_2 = '';
        }

        if ($group_icon_3 == 1) {
            $service_group_icon_3 = 'fa-solid fa-scale-balanced';
        } else {
            $service_group_icon_3 = '';
        }

        $service_group_icon = $service_group_icon_1 . $service_group_icon_2 . $service_group_icon_3;

        if (!empty($service_group_icon)) {
            $service_group_icon_name = '<br><span class="dark-gray fw-bold">Service Group Icon</span>:';
        } else {
            $service_group_icon_name = '';
        }

        if ($group_color != 'text-null') {
            $service_group_color_select = $group_color;
            $service_group_color_custom = '';
        } else {
            $service_group_color_select = '';
        }

        if (!empty($group_custom_color && $group_color == 'text-null')) {
            $service_group_color_custom = $group_custom_color;
            $service_group_color_select = '';
        } else {
            $service_group_color_custom = '';
        }

        $service_group_color = $service_group_color_select . $service_group_color_custom;

        if (!empty($service_group_tags)) {
            $service_group_tags = '<br><span class="dark-gray fw-bold">Service Group Tags</span>:' . ' ' . $service_group_tags;
        } else {
            $service_group_tags = '';
        }

        $audit_detailed_action = '<span class="dark-gray fw-bold">Created Service Group</span>:' . ' ' . $service_group_name
            . '<br>' . '<span class="dark-gray fw-bold">Service Group Color</span>:' . ' ' . $service_group_color
            . $service_group_icon_name . ' ' . $service_group_icon . ' ' . '<span class="float-end"><i class="' . $service_group_icon . ' ' . $service_group_color_select . '" style="color: ' . $service_group_color_custom . ';"></i></span>'
            . $service_group_tags;

        $service_group_name = (strlen($service_group_name) > 30) ? substr($service_group_name, 0, 30).'...' : $service_group_name;

        $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_audit = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt_audit, "sssssssssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $service_group_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        mysqli_stmt_execute($stmt_audit);
		mysqli_stmt_close($stmt_audit);
		echo json_encode(['success' => true, 'message' => 'Service group created successfully.']);
    } else {
   	 	echo json_encode(['success' => false, 'error' => 'Failed to create service group.']);
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);