<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_create_view')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'], $_POST['verse_note_title'], $_POST['verse_note_info'], $_POST['verse_note_group'])) {
	$verse_note_title = strip_tags($_POST['verse_note_title']);
    $verse_note_info = strip_tags($_POST['verse_note_info']);
    $verse_note_group = strip_tags($_POST['verse_note_group']);

	if (empty($verse_note_title) || empty($verse_note_info) || empty($verse_note_group)) {
        http_response_code(400);
        die("Invalid input.");
    }

	$verse_group_query = "SELECT verse_group_name FROM verse_groups WHERE verse_group_id = ?";
    $stmt = mysqli_prepare($dbc, $verse_group_query);
    if (!$stmt) {
        http_response_code(500);
        die("Database error.");
    }
    mysqli_stmt_bind_param($stmt, 's', $verse_note_group);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $verse_group_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

	if (!$verse_group_name) {
        http_response_code(404);
        die("Verse group not found.");
    }

	$verse_group_name = strip_tags($verse_group_name);

	$query = "INSERT INTO verse_notes (verse_note_title, verse_note_info, verse_note_group) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    if (!$stmt) {
        http_response_code(500);
        die("Database error.");
    }
    mysqli_stmt_bind_param($stmt, 'sss', $verse_note_title, $verse_note_info, $verse_note_group);
    $result = mysqli_stmt_execute($stmt);

	if ($result) {
        
        $audit_user = strip_tags($_SESSION['user']);
        $audit_first_name = strip_tags($_SESSION['first_name']);
        $audit_last_name = strip_tags($_SESSION['last_name']);
        $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
        $switch_id = strip_tags($_SESSION['switch_id']);
        $audit_date = date('m-d-Y g:i A');
        $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fas fa-feather-alt"></i> Added Verse Note</span>';
        $audit_action = 'Added Verse Note';
        $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
        $audit_source = strip_tags($_SERVER['REQUEST_URI']);
        $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
        $audit_detailed_action = '<span class="dark-gray fw-bold">Created Verse Note</span>:' . ' ' . $verse_note_title . '<br>' . '<span class="dark-gray fw-bold">Assigned to Verse Group</span>:' . ' ' . $verse_group_name;

  	  	$verse_note_title_short = (strlen($verse_note_title) > 30) ? substr($verse_note_title, 0, 30).'...' : $verse_note_title;

		$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_audit = mysqli_prepare($dbc, $audit_query);
        if (!$stmt_audit) {
            http_response_code(500);
            die("Database error.");
        }
        mysqli_stmt_bind_param($stmt_audit, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $verse_note_title_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        $audit_result = mysqli_stmt_execute($stmt_audit);

        if (!$audit_result) {
            http_response_code(500);
            die("Audit trail error.");
        }

        mysqli_stmt_close($stmt_audit);
    } else {
        http_response_code(500);
        die("Insert error.");
    }

    mysqli_stmt_close($stmt);
    mysqli_close($dbc);
}