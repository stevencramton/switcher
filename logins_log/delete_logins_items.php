<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_login_log')){
	header("Location:../../index.php?msg1");
	exit();
}
	
if (isset($_SESSION['id']) && isset($_POST['id'])) {
	$logins_ids = array_map(function($id) use ($dbc) {
        return mysqli_real_escape_string($dbc, strip_tags($id));
    }, explode(',', $_POST['id']));
	$logins_ids_placeholder = implode(',', array_fill(0, count($logins_ids), '?'));
	$logins_log_query = "SELECT * FROM log_ins WHERE id IN ($logins_ids_placeholder)";
    $stmt = mysqli_prepare($dbc, $logins_log_query);
    if (!$stmt) {
        die('Query preparation failed.');
    }
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($logins_ids)), ...$logins_ids);
    mysqli_stmt_execute($stmt);
    $logins_log_result = mysqli_stmt_get_result($stmt);
    $logins_log_rows = [];
    while ($row = mysqli_fetch_array($logins_log_result)) {
        $logins_log_rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    foreach ($logins_log_rows as $logins_log_row) {
        $logins_log_user = strip_tags($logins_log_row['user']);
        $logins_log_first_name = strip_tags($logins_log_row['first_name']);
        $logins_log_last_name = strip_tags($logins_log_row['last_name']);
    }

    $query = "DELETE FROM log_ins WHERE id IN ($logins_ids_placeholder)";
    $stmt = mysqli_prepare($dbc, $query);
    if (!$stmt) {
        die('Query preparation failed.');
    }
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($logins_ids)), ...$logins_ids);
    if (!mysqli_stmt_execute($stmt)) {
        exit();
    }
    mysqli_stmt_close($stmt);

    $audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Logins Log</span>';
    $audit_action = 'Deleted Logins Log';
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Logins Log</span>:' . ' ' . implode(',', $logins_ids);

    $logins_id_short = (strlen(implode(',', $logins_ids)) > 30) ? substr(implode(',', $logins_ids), 0, 30).'...' : implode(',', $logins_ids);

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $audit_query);
    if (!$stmt) {
        die('Query preparation failed.');
    }
    mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $logins_id_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $audit_result = mysqli_stmt_execute($stmt);
    confirmQuery($audit_result);
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);