<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_group_edit')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['verse_group_id'])) {
	$verse_group_id = mysqli_real_escape_string($dbc, strip_tags($_POST['verse_group_id']));
    $verse_group_name = mysqli_real_escape_string($dbc, strip_tags($_POST['verse_group_name']));

	$old_verse_group_query = "SELECT * FROM verse_groups WHERE verse_group_id = ?";
    $stmt_old = mysqli_prepare($dbc, $old_verse_group_query);
    mysqli_stmt_bind_param($stmt_old, "i", $verse_group_id);
    mysqli_stmt_execute($stmt_old);
    $old_verse_group_result = mysqli_stmt_get_result($stmt_old);
    $old_verse_group_row = mysqli_fetch_array($old_verse_group_result);
    $old_verse_group_name = mysqli_real_escape_string($dbc, strip_tags($old_verse_group_row['verse_group_name']));

	$update_query = "UPDATE verse_groups SET verse_group_name = ? WHERE verse_group_id = ?";
    $stmt_update = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt_update, "si", $verse_group_name, $verse_group_id);
    $update_success = mysqli_stmt_execute($stmt_update);

    if (!$update_success) {
        $response = "failure";
        exit();
    } else {
        $response = "success";
    }

    echo json_encode($response);

	$new_verse_group_query = "SELECT * FROM verse_groups WHERE verse_group_id = ?";
    $stmt_new = mysqli_prepare($dbc, $new_verse_group_query);
    mysqli_stmt_bind_param($stmt_new, "i", $verse_group_id);
    mysqli_stmt_execute($stmt_new);
    $new_verse_group_result = mysqli_stmt_get_result($stmt_new);
    $new_verse_group_row = mysqli_fetch_array($new_verse_group_result);
    $new_verse_group_name = mysqli_real_escape_string($dbc, strip_tags($new_verse_group_row['verse_group_name']));

	$audit_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
    $audit_first_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['first_name']));
    $audit_last_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['last_name']));
    $audit_profile_pic = mysqli_real_escape_string($dbc, strip_tags($_SESSION['profile_pic']));
    $switch_id = mysqli_real_escape_string($dbc, strip_tags($_SESSION['switch_id']));
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-feather-alt"></i> Updated Verse Group</span>';
    $audit_action = 'Updated Verse Group' . ' ' . $new_verse_group_name;
    $audit_ip = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REMOTE_ADDR']));
    $audit_source = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REQUEST_URI']));
    $audit_domain = mysqli_real_escape_string($dbc, strip_tags($_SERVER['SERVER_NAME']));

	if (strcmp($old_verse_group_name, $new_verse_group_name) == 0) {
        $verse_group_name_change = '';
    } else {
        $verse_group_name_change = '<span class="dark-gray fw-bold">Updated Verse Group</span>:' . ' ' . $old_verse_group_name . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_verse_group_name . '<br>';
    }

    $audit_detailed_action = $verse_group_name_change;

    if (empty($audit_detailed_action)) {
        $audit_detailed_action = 'No modifications were made.';
    } else {
        $audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);
    }

	$new_verse_group_name_short = (strlen($new_verse_group_name) > 30) ? substr($new_verse_group_name, 0, 30).'...' : $new_verse_group_name;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_audit = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt_audit, "ssssissssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $new_verse_group_name_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $audit_success = mysqli_stmt_execute($stmt_audit);

    if (!$audit_success) {
        die('Audit trail insertion failed.');
    }

    mysqli_stmt_close($stmt_old);
    mysqli_stmt_close($stmt_update);
    mysqli_stmt_close($stmt_new);
    mysqli_stmt_close($stmt_audit);
}

mysqli_close($dbc);