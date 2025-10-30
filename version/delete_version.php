<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('version_delete')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['id'])) {
    $id = strip_tags($_POST['id']);
    
	$version_query = "SELECT * FROM versions WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $version_query)) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $version_result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_array($version_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }
    
    $version = strip_tags($row['version']);
    $notes = strip_tags($row['notes']);
    $released_by = strip_tags($row['released_by']);

	$query = "DELETE FROM versions WHERE id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
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
    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fas fa-code-branch"></i> Deleted Switchboard Version</span>';
    $audit_action = 'Deleted Switchboard Version ' . $version;
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Switchboard Version</span>: ' . $version . '<br><span class="dark-gray fw-bold">Version Notes</span>: ' . $notes;
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
}

mysqli_close($dbc);
?>
