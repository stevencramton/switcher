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
	$group_id = mysqli_real_escape_string($dbc, strip_tags($_POST['group_id']));
	$select_query = "SELECT group_name FROM service_groups WHERE group_id = ?";
    $select_stmt = mysqli_prepare($dbc, $select_query);
    mysqli_stmt_bind_param($select_stmt, "i", $group_id);
    mysqli_stmt_execute($select_stmt);
    $result = mysqli_stmt_get_result($select_stmt);
    $row = mysqli_fetch_array($result);
    $service_group_name = $row['group_name'];

	$delete_query = "DELETE FROM service_groups WHERE group_id = ? LIMIT 1";
    $delete_stmt = mysqli_prepare($dbc, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $group_id);
    $delete_success = mysqli_stmt_execute($delete_stmt);

    if (!$delete_success) {
        $response = "failure";
        exit();
    } else {
        $response = "success";
    }

    echo json_encode($response);

	$audit_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
    $audit_first_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['first_name']));
    $audit_last_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['last_name']));
    $audit_profile_pic = mysqli_real_escape_string($dbc, strip_tags($_SESSION['profile_pic']));
    $switch_id = mysqli_real_escape_string($dbc, strip_tags($_SESSION['switch_id']));
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Service Group </span>';
    $audit_action = 'Deleted Service Group';
    $audit_ip = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REMOTE_ADDR']));
    $audit_source = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REQUEST_URI']));
    $audit_domain = mysqli_real_escape_string($dbc, strip_tags($_SERVER['SERVER_NAME']));
    $audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Service Group</span>:' . ' ' . $service_group_name;
    $service_group_name = (strlen($service_group_name) > 30) ? substr($service_group_name, 0, 30).'...' : $service_group_name;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $audit_stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($audit_stmt, "ssssissssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $service_group_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $audit_success = mysqli_stmt_execute($audit_stmt);
    confirmQuery($audit_success);

    mysqli_stmt_close($select_stmt);
    mysqli_stmt_close($delete_stmt);
    mysqli_stmt_close($audit_stmt);
}

mysqli_close($dbc);