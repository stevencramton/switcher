<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['skill_name'])) {
	date_default_timezone_set("America/New_York");
    $skill_creation_date = date('Y-m-d h:i A');
	$skill_created_by = $_POST['skill_created_by'];
    $skill_category = $_POST['skill_category'];
    $skill_name = $_POST['skill_name'];
    $skill_objective = $_POST['skill_objective'];
    $skill_resource = $_POST['skill_resource'];
 	$last_display_order_query = "SELECT skill_display_order FROM skills ORDER BY skill_id DESC LIMIT 1";

    if ($last_display_order_stmt = mysqli_prepare($dbc, $last_display_order_query)) {
        mysqli_stmt_execute($last_display_order_stmt);
        mysqli_stmt_bind_result($last_display_order_stmt, $last_display_order);
        mysqli_stmt_fetch($last_display_order_stmt);
        mysqli_stmt_close($last_display_order_stmt);
    } else {
        exit();
    }

    $new_display_order = $last_display_order + 1;
	$insert_query = "INSERT INTO skills (skill_creation_date, skill_created_by, skill_category, skill_name, skill_objective, skill_resource, skill_display_order) VALUES (?, ?, ?, ?, ?, ?, ?)";

    if ($insert_stmt = mysqli_prepare($dbc, $insert_query)) {
        mysqli_stmt_bind_param($insert_stmt, 'ssssssi', $skill_creation_date, $skill_created_by, $skill_category, $skill_name, $skill_objective, $skill_resource, $new_display_order);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    } else {
        die('QUERY FAILED.');
    }

	$audit_user = $_SESSION['user'];
    $audit_first_name = $_SESSION['first_name'];
    $audit_last_name = $_SESSION['last_name'];
    $audit_profile_pic = $_SESSION['profile_pic'];
    $switch_id = $_SESSION['switch_id'];
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-solid fa-bolt"></i> Created Skill</span>';
    $audit_action = 'Created Skill';
    $audit_ip = $_SERVER['REMOTE_ADDR'];
    $audit_source = $_SERVER['REQUEST_URI'];
    $audit_domain = $_SERVER['SERVER_NAME'];
    $audit_detailed_action = '<span class="dark-gray fw-bold">Skill Name:</span> ' . $skill_name
        . '<br><span class="dark-gray fw-bold">Skill Category</span>: ' . $skill_category
        . '<br><span class="dark-gray fw-bold">Skill Objective</span>: ' . $skill_objective
        . '<br><span class="dark-gray fw-bold">Skill Resource</span>: ' . $skill_resource;
    
    $skill_name = (strlen($skill_name) > 30) ? substr($skill_name, 0, 30) . '...' : $skill_name;

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($audit_stmt = mysqli_prepare($dbc, $audit_query)) {
        mysqli_stmt_bind_param($audit_stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $skill_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        mysqli_stmt_execute($audit_stmt);
        mysqli_stmt_close($audit_stmt);
    } else {
        die('QUERY FAILED.');
    }
}
mysqli_close($dbc);