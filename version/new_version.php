<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('version_create')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['version'])) {
    $version = strip_tags($_POST['version']);
    $callout_select = strip_tags($_POST['callout_select']);
    $callout_text = strip_tags($_POST['callout_text']);
    $code = htmlspecialchars($_POST['code'], ENT_QUOTES, 'UTF-8');
    $notes = strip_tags($_POST['notes']);
    $classify = strip_tags($_POST['classify']);
    $priority = strip_tags($_POST['priority']);
	$date = date_default_timezone_set("America/New_York");
	$date = date('m-d-Y g:i A');
	$released_by = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

	$query = "INSERT INTO versions (version, callout_select, callout_text, code, notes, classify, priority, date, released_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'sssssssss', $version, $callout_select, $callout_text, $code, $notes, $classify, $priority, $date, $released_by);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }
    
 	$audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    $audit_date = date('m-d-Y g:i A', time());
    $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fas fa-code-branch"></i> Created Switchboard Version</span>';
    $audit_action = 'Create Switchboard Version release ' . $version;
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = '<span class="dark-gray fw-bold">Switchboard Version</span>: ' . $version . '<br>' . '<span class="dark-gray fw-bold">Version Notes</span>: ' . $notes;
    
    $notes_summary = (strlen($notes) > 30) ? substr($notes, 0, 30) . '...' : $notes;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbc, $audit_query)) {
        mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $version, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }

    addExp('1');
}

mysqli_close($dbc);
?>