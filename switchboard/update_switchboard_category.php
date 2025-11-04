<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_categories')) {
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_POST['cat_id'])){
	$cat_id = strip_tags($_POST['cat_id']);
	$cat_query = "SELECT * FROM switchboard_categories WHERE switchboard_cat_id = ?";
	$cat_stmt = mysqli_prepare($dbc, $cat_query);
	mysqli_stmt_bind_param($cat_stmt, 'i', $cat_id);
	mysqli_stmt_execute($cat_stmt);
	$cat_result = mysqli_stmt_get_result($cat_stmt);
	$cat_row = mysqli_fetch_array($cat_result);
	mysqli_stmt_close($cat_stmt);

	$old_switch_cat_name = strip_tags($cat_row['switchboard_cat_name']);
	$old_switch_cat_color = strip_tags($cat_row['switchboard_cat_color']);
	$old_switch_cat_icon = strip_tags($cat_row['switchboard_cat_icon']);
		
	$switch_cat_title = strip_tags($_POST['switch_cat_title']);
	$switch_cat_color = strip_tags($_POST['switch_cat_color']);
	$switch_cat_icon = strip_tags($_POST['switch_cat_icon']);
    	
	$query = "UPDATE switchboard_categories SET switchboard_cat_name = ?, switchboard_cat_icon = ?, switchboard_cat_color = ? WHERE switchboard_cat_id = ?";
	$stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, 'sssi', $switch_cat_title, $switch_cat_icon, $switch_cat_color, $cat_id);
	if (!mysqli_stmt_execute($stmt)) {
 	   echo("Error description.");
	}
	mysqli_stmt_close($stmt);
	
	$new_cat_query = "SELECT * FROM switchboard_categories WHERE switchboard_cat_id = ?";
	$new_cat_stmt = mysqli_prepare($dbc, $new_cat_query);
	mysqli_stmt_bind_param($new_cat_stmt, 'i', $cat_id);
	mysqli_stmt_execute($new_cat_stmt);
	$new_cat_result = mysqli_stmt_get_result($new_cat_stmt);
	$new_cat_row = mysqli_fetch_array($new_cat_result);
	mysqli_stmt_close($new_cat_stmt);

	$new_switch_cat_name = strip_tags($new_cat_row['switchboard_cat_name']);
	$new_switch_cat_color = strip_tags($new_cat_row['switchboard_cat_color']);
	$new_switch_cat_icon = strip_tags($new_cat_row['switchboard_cat_icon']);
	
	$audit_user = strip_tags($_SESSION['user']);
	$audit_first_name = strip_tags($_SESSION['first_name']);
	$audit_last_name = strip_tags($_SESSION['last_name']);
	$audit_profile_pic = strip_tags($_SESSION['profile_pic']);
	$switch_id = strip_tags($_SESSION['switch_id']);
	date_default_timezone_set("America/New_York");
	$audit_date = date('m-d-Y g:i A');
	$audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-folder-plus"></i> Updated Switchboard Category </span>';
	$audit_action = 'Updated Switchboard Category' . ' ' . $new_switch_cat_name;
	$audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
	$audit_source = strip_tags($_SERVER['REQUEST_URI']);
	$audit_domain = strip_tags($_SERVER['SERVER_NAME']);
	
	if (strcmp($old_switch_cat_name, $new_switch_cat_name) == 0) {
		$switch_name_change = '';
	} else {
		$switch_name_change = '<span class="dark-gray fw-bold">Switchboard Category Name</span>:' . ' ' . $old_switch_cat_name . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_switch_cat_name . '<br>';
	}
	
	if (strcmp($old_switch_cat_color, $new_switch_cat_color) == 0) {
		$switch_color_change = '';
	} else {
		$switch_color_change = '<span class="dark-gray fw-bold">Switchboard Category Color</span>:' . ' ' . $old_switch_cat_color . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_switch_cat_color . '<br>';
	}
	
	if (strcmp($old_switch_cat_icon, $new_switch_cat_icon) == 0) {
		$switch_icon_change = '';
	} else {
		$switch_icon_change = '<span class="dark-gray fw-bold">Switchboard Category Icon</span>:' . ' ' . '<span class=""><i class="' . $old_switch_cat_icon . '" style="color: ' . $old_switch_cat_color . ';"></i></span>' . ' ' . $old_switch_cat_icon . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . '<span class=""><i class="' . $new_switch_cat_icon . '" style="color: ' . $new_switch_cat_color . ';"></i></span>' . ' ' . $new_switch_cat_icon . '<br>';
	}
	
	$audit_detailed_action = $switch_name_change . $switch_color_change . $switch_icon_change;
	
	if (empty($audit_detailed_action)) {
	  $audit_detailed_action = '<i class="fa-solid fa-heart-pulse text-secondary bg-white shadow-sm rounded p-2 me-2"></i> No modifications were made.';
	} else {
	
	$audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);

	}
	
	$new_switch_cat_name = (strlen($new_switch_cat_name) > 30) ? substr($new_switch_cat_name, 0, 30).'...' : $new_switch_cat_name;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	$audit_stmt = mysqli_prepare($dbc, $audit_query);
	mysqli_stmt_bind_param($audit_stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $new_switch_cat_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
	mysqli_stmt_execute($audit_stmt);
	confirmQuery($audit_stmt);
	mysqli_stmt_close($audit_stmt);
}

mysqli_close($dbc);