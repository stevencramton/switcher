<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('version_edit')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['id'])) {
    $id = strip_tags($_POST['id']);
    
   	$old_version_query = "SELECT * FROM versions WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $old_version_query)) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $old_version_result = mysqli_stmt_get_result($stmt);
        $old_version_row = mysqli_fetch_array($old_version_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }
    
    $old_version = strip_tags($old_version_row['version']);
    $old_callout_select = strip_tags($old_version_row['callout_select']);
    $old_callout_text = strip_tags($old_version_row['callout_text']);
    $old_code = strip_tags($old_version_row['code']);
    $old_notes = strip_tags($old_version_row['notes']);
    $old_classify = strip_tags($old_version_row['classify']);
    $old_priority = strip_tags($old_version_row['priority']);

    $version = strip_tags($_POST['version']);
    $callout_select = strip_tags($_POST['callout_select']);
    $callout_text = strip_tags($_POST['callout_text']);
    $code = htmlspecialchars($_POST['code'], ENT_QUOTES, 'UTF-8');
	$code = strip_tags($_POST['code']);
    $notes = strip_tags($_POST['notes']);
    $classify = strip_tags($_POST['classify']);
    $priority = strip_tags($_POST['priority']);

	$query = "UPDATE versions SET version = ?, callout_select = ?, callout_text = ?, code = ?, notes = ?, classify = ?, priority = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'sssssssi', $version, $callout_select, $callout_text, $code, $notes, $classify, $priority, $id);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }

	$new_version_query = "SELECT * FROM versions WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $new_version_query)) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $new_version_result = mysqli_stmt_get_result($stmt);
        $new_version_row = mysqli_fetch_array($new_version_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }

    $new_version = strip_tags($new_version_row['version']);
    $new_callout_select = strip_tags($new_version_row['callout_select']);
    $new_callout_text = strip_tags($new_version_row['callout_text']);
    $new_code = strip_tags($new_version_row['code']);
    $new_notes = strip_tags($new_version_row['notes']);
    $new_classify = strip_tags($new_version_row['classify']);
    $new_priority = strip_tags($new_version_row['priority']);

	$audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    $audit_date = date('m-d-Y g:i A', time());
    $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-code-branch"></i> Updated Switchboard Version</span>';
    $audit_action = 'Updated Switchboard Version ' . $version;
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $notes_summary = (strlen($notes) > 30) ? substr($notes, 0, 30) . '...' : $notes;

	$version_name_change = (strcmp($old_version, $new_version) == 0) ? '' : '<span class="dark-gray fw-bold">Updated Version</span>: ' . $old_version . ' <span class="dark-gray fw-bold">to</span>: ' . $new_version . '<br>';

	$version_note_change = (strcmp($old_notes, $new_notes) == 0) ? '' : '<span class="badge bg-info">New</span> <span class="dark-gray fw-bold">Version Note</span>: ' . $new_notes . ' <br><span class="badge bg-secondary">Prior</span> <span class="dark-gray fw-bold">Version Note</span>: ' . $old_notes . '<br>';

    $audit_detailed_action = $version_name_change . $version_note_change;

    if (empty($audit_detailed_action)) {
        $audit_detailed_action = 'No modifications were made.';
    } else {
        $audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);
    }

    $new_version_summary = (strlen($new_version) > 30) ? substr($new_version, 0, 30) . '...' : $new_version;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($dbc, $audit_query)) {
        mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $new_version_summary, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        mysqli_stmt_execute($stmt);
        confirmQuery($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed.");
    }
}

mysqli_close($dbc);
?>
