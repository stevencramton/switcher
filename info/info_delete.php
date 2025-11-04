<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['display_info_id'])) {
	$display_info_id = strip_tags($_POST['display_info_id']);
 	$display_info_query = "SELECT * FROM info WHERE info_id = ?";
    $stmt = $dbc->prepare($display_info_query);
    $stmt->bind_param("s", $display_info_id);
    $stmt->execute();
    $display_info_result = $stmt->get_result();
    $row = $display_info_result->fetch_array(MYSQLI_ASSOC);
    
    if ($row) {
        $display_info_title = $row['info_title'];
        $display_info_icon = $row['info_icon'];
    } else {
        http_response_code(404);
        echo "Info record not found.";
        exit();
    }

    $ids = explode(',', $display_info_id);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "DELETE FROM info WHERE info_id IN ($placeholders)";
    $stmt = $dbc->prepare($query);

    $stmt->bind_param(str_repeat('s', count($ids)), ...$ids);
    $stmt->execute();
    confirmQuery($stmt);

    $audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="'. $display_info_icon . '"></i> Deleted Info Title</span>';
    $audit_action = 'Deleted Info Title: ' . $display_info_title;
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = 'On ' . $audit_date . ', ' . $audit_first_name . ' ' . $audit_last_name . ' deleted the info title "' . $display_info_title . '"';
    $audit_summary = (strlen($display_info_title) > 30) ? substr($display_info_title, 0, 30) . '...' : $display_info_title;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbc->prepare($audit_query);
    $stmt->bind_param("sssssssssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $audit_summary, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $stmt->execute();
    confirmQuery($stmt);
}

mysqli_close($dbc);