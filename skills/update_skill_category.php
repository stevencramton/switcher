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

if (isset($_POST['cat_skill_id'])) {
	$cat_skill_id = strip_tags($_POST['cat_skill_id']);
	$cat_skill_query = "SELECT * FROM skill_categories WHERE cat_skill_id = ?";
	$stmt = mysqli_prepare($dbc, $cat_skill_query);
	mysqli_stmt_bind_param($stmt, 'i', $cat_skill_id);
	mysqli_stmt_execute($stmt);
	$cat_skill_result = mysqli_stmt_get_result($stmt);
	$cat_skill_row = mysqli_fetch_array($cat_skill_result);

	$old_cat_skill_title = strip_tags($cat_skill_row['cat_skill_title']);
	$old_cat_skill_color = strip_tags($cat_skill_row['cat_skill_color']);
	$old_cat_skill_icon = strip_tags($cat_skill_row['cat_skill_icon']);
	
	$cat_skill_color = strip_tags($_POST['cat_skill_color']);
	$cat_skill_title = strip_tags($_POST['cat_skill_title']);
    $cat_skill_icon = strip_tags($_POST['cat_skill_icon']);
	
    $query = "UPDATE skill_categories SET cat_skill_color = ?, cat_skill_title = ?, cat_skill_icon = ? WHERE cat_skill_id = ?";
	$stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, 'sssi', $cat_skill_color, $cat_skill_title, $cat_skill_icon, $cat_skill_id);
	mysqli_stmt_execute($stmt);
	confirmQuery(mysqli_stmt_affected_rows($stmt));

	$new_cat_skill_query = "SELECT * FROM skill_categories WHERE cat_skill_id = ?";
	$stmt = mysqli_prepare($dbc, $new_cat_skill_query);
	mysqli_stmt_bind_param($stmt, 'i', $cat_skill_id);
	mysqli_stmt_execute($stmt);
	$new_cat_skill_result = mysqli_stmt_get_result($stmt);
	$new_cat_skill_row = mysqli_fetch_array($new_cat_skill_result);

	$new_cat_skill_title = strip_tags($new_cat_skill_row['cat_skill_title']);
	$new_cat_skill_color = strip_tags($new_cat_skill_row['cat_skill_color']);
	$new_cat_skill_icon = strip_tags($new_cat_skill_row['cat_skill_icon']);
		
	$audit_user = strip_tags($_SESSION['user']);
	$audit_first_name = strip_tags($_SESSION['first_name']);
	$audit_last_name = strip_tags($_SESSION['last_name']);
	$audit_profile_pic = strip_tags($_SESSION['profile_pic']);
	$switch_id = strip_tags($_SESSION['switch_id']);
	date_default_timezone_set("America/New_York");
	$audit_date = date('m-d-Y g:i A');
	$audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fa-solid fa-bolt"></i> Updated Skill Category </span>';
	$audit_action = 'Updated Skill Category' . ' ' . $new_cat_skill_title;
	$audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
	$audit_source = strip_tags($_SERVER['REQUEST_URI']);
	$audit_domain = strip_tags($_SERVER['SERVER_NAME']);
	
	if (strcmp($old_cat_skill_title, $new_cat_skill_title) == 0) {
		$cat_skill_name_change = '';
	} else {
		$cat_skill_name_change = '<span class="dark-gray fw-bold">Skill Category Title</span>:' . ' ' . $old_cat_skill_title . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_cat_skill_title . '<br>';
	}
	
	if (strcmp($old_cat_skill_color, $new_cat_skill_color) == 0) {
		$cat_skill_color_change = '';
	} else {
		$cat_skill_color_change = '<span class="dark-gray fw-bold">Skill Category Color</span>:' . ' ' . $old_cat_skill_color . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_cat_skill_color . '<br>';
	}
	
	if (strcmp($old_cat_skill_icon, $new_cat_skill_icon) == 0) {
		$cat_skill_icon_change = '';
	} else {
		$cat_skill_icon_change = '<span class="dark-gray fw-bold">Skill Category Icon</span>:' . ' ' . '<span class=""><i class="' . $old_cat_skill_icon . '" style="color: ' . $old_cat_skill_color . ';"></i></span>' . ' ' . $old_cat_skill_icon . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . '<span class=""><i class="' . $new_cat_skill_icon . '" style="color: ' . $new_cat_skill_color . ';"></i></span>' . ' ' . $new_cat_skill_icon . '<br>';
	}
	
	$audit_detailed_action = $cat_skill_name_change . $cat_skill_color_change . $cat_skill_icon_change;
	
	if (empty($audit_detailed_action)) {
		$audit_detailed_action = '<i class="fa-solid fa-heart-pulse text-secondary bg-white shadow-sm rounded p-2 me-2"></i> No modifications were made.';
	} else {
		$audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);
	}
	
	$new_cat_skill_title = (strlen($new_cat_skill_title) > 30) ? substr($new_cat_skill_title, 0, 30).'...' : $new_cat_skill_title;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	$stmt = mysqli_prepare($dbc, $audit_query);
	mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $new_cat_skill_title, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
	mysqli_stmt_execute($stmt);
	confirmQuery(mysqli_stmt_affected_rows($stmt));
}

mysqli_close($dbc);