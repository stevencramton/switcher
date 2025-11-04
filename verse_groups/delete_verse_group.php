<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_group_delete')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'], $_POST['verse_group_id'])) {
	$verse_group_id = strip_tags($_POST['verse_group_id']);
	$verse_group_query = "SELECT verse_group_name FROM verse_groups WHERE verse_group_id = ?";
    $stmt = mysqli_prepare($dbc, $verse_group_query);
    mysqli_stmt_bind_param($stmt, 's', $verse_group_id);
    mysqli_stmt_execute($stmt);
    $verse_group_result = mysqli_stmt_get_result($stmt);
    $verse_group_row = mysqli_fetch_array($verse_group_result);

    if (!$verse_group_row) {
		http_response_code(404);
        die("Verse group not found.");
    }

    $verse_group_name = $verse_group_row['verse_group_name'];
	$query = "DELETE FROM verse_groups WHERE verse_group_id = ? LIMIT 1";
    $stmt_delete = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt_delete, 's', $verse_group_id);
    $result = mysqli_stmt_execute($stmt_delete);

	if ($result) {
        $response = "success";
		$audit_user = strip_tags($_SESSION['user']);
        $audit_first_name = strip_tags($_SESSION['first_name']);
        $audit_last_name = strip_tags($_SESSION['last_name']);
        $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
        $switch_id = strip_tags($_SESSION['switch_id']);
        $audit_date = date('m-d-Y g:i A');
        $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Verse Group</span>';
        $audit_action = 'Deleted Verse Group';
        $audit_ip = $_SERVER['REMOTE_ADDR'];
        $audit_source = $_SERVER['REQUEST_URI'];
        $audit_domain = $_SERVER['SERVER_NAME'];
        $audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Verse Group</span>:' . ' ' . htmlspecialchars($verse_group_name);
        $verse_group_name_short = (strlen($verse_group_name) > 30) ? substr($verse_group_name, 0, 30).'...' : $verse_group_name;

		$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_audit = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt_audit, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $verse_group_name_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        $audit_result = mysqli_stmt_execute($stmt_audit);
        confirmQuery($audit_result);
    } else {
        $response = "failure";
        exit();
    }
	echo json_encode($response);
	mysqli_stmt_close($stmt);
    mysqli_stmt_close($stmt_delete);
    mysqli_close($dbc);
}