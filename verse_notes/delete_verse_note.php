<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_delete_view')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'], $_POST['verse_note_id'])) {
    $verse_note_id = $_POST['verse_note_id'];

    $select_query = "SELECT vn.verse_note_title, vn.verse_note_info, vn.verse_note_group, vg.verse_group_name 
                     FROM verse_notes vn
                     LEFT JOIN verse_groups vg ON vn.verse_note_group = vg.verse_group_id
                     WHERE vn.verse_note_id = ?";
    
    $stmt_select = mysqli_prepare($dbc, $select_query);
    mysqli_stmt_bind_param($stmt_select, 'i', $verse_note_id);
    mysqli_stmt_execute($stmt_select);
    $result_select = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($result_select);

    if (!$row) {
        http_response_code(404);
        die("Verse note not found");
    }

    $verse_note_title = $row['verse_note_title'];
    $verse_note_info = $row['verse_note_info'];
    $verse_note_group = $row['verse_note_group'];
    $verse_group_name = $row['verse_group_name'];

	$delete_query = "DELETE FROM verse_notes WHERE verse_note_id = ?";
    $stmt_delete = mysqli_prepare($dbc, $delete_query);
    mysqli_stmt_bind_param($stmt_delete, 'i', $verse_note_id);
    $result_delete = mysqli_stmt_execute($stmt_delete);

    if (!$result_delete) {
        $response = "failure";
        exit();
    } else {
        $response = "success";
    }

    echo json_encode($response);

	$audit_user = $_SESSION['user'];
    $audit_first_name = $_SESSION['first_name'];
    $audit_last_name = $_SESSION['last_name'];
    $audit_profile_pic = $_SESSION['profile_pic'];
    $switch_id = $_SESSION['switch_id'];
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Verse Note</span>';
    $audit_action = 'Deleted Verse Note';
    $audit_ip = $_SERVER['REMOTE_ADDR'];
    $audit_source = $_SERVER['REQUEST_URI'];
    $audit_domain = $_SERVER['SERVER_NAME'];
    $audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Verse Note</span>: ' . $verse_note_title . '<br>' . '<span class="dark-gray fw-bold">From Verse Group:</span> ' . $verse_group_name;

	$verse_note_title_short = (strlen($verse_note_title) > 30) ? substr($verse_note_title, 0, 30).'...' : $verse_note_title;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_audit = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt_audit, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $verse_note_title_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $audit_result = mysqli_stmt_execute($stmt_audit);
    confirmQuery($audit_result);
}

mysqli_close($dbc);