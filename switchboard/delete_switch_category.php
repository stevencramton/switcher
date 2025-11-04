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

if(isset($_POST['id'])){
	$cat_id = strip_tags($_POST['id']);
	$check_query = "SELECT * FROM switchboard_contacts WHERE switchboard_cat_id = ?";
	$check_stmt = mysqli_prepare($dbc, $check_query);
	mysqli_stmt_bind_param($check_stmt, 'i', $cat_id);
	mysqli_stmt_execute($check_stmt);
	$check_result = mysqli_stmt_get_result($check_stmt);
	$count = mysqli_num_rows($check_result);
	mysqli_stmt_close($check_stmt);

	if($count != 0){
		$contact_query = "UPDATE switchboard_contacts SET switchboard_cat_id = '0' WHERE switchboard_cat_id = ?";
		$contact_stmt = mysqli_prepare($dbc, $contact_query);
		mysqli_stmt_bind_param($contact_stmt, 'i', $cat_id);
		mysqli_stmt_execute($contact_stmt);
		mysqli_stmt_close($contact_stmt);
  	}
		
	$switch_cat_query = "SELECT switchboard_cat_name FROM switchboard_categories WHERE switchboard_cat_id = ?";
	$switch_cat_stmt = mysqli_prepare($dbc, $switch_cat_query);
	mysqli_stmt_bind_param($switch_cat_stmt, 'i', $cat_id);
	mysqli_stmt_execute($switch_cat_stmt);
	$switch_cat_result = mysqli_stmt_get_result($switch_cat_stmt);
	$switch_cat_row = mysqli_fetch_array($switch_cat_result);
	mysqli_stmt_close($switch_cat_stmt);

	$switchboard_cat_name = strip_tags($switch_cat_row['switchboard_cat_name']);
	$delete_query = "DELETE FROM switchboard_categories WHERE switchboard_cat_id = ?";
	$delete_stmt = mysqli_prepare($dbc, $delete_query);
	mysqli_stmt_bind_param($delete_stmt, 'i', $cat_id);
	if (!mysqli_stmt_execute($delete_stmt)) {
      	echo("Error description.");
    }
	mysqli_stmt_close($delete_stmt);

	$audit_user = strip_tags($_SESSION['user']);
	$audit_first_name = strip_tags($_SESSION['first_name']);
	$audit_last_name = strip_tags($_SESSION['last_name']);
	$audit_profile_pic = strip_tags($_SESSION['profile_pic']);
	$switch_id = strip_tags($_SESSION['switch_id']);
	date_default_timezone_set("America/New_York");
	$audit_date = date('m-d-Y g:i A');
	$audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Switchboard Category </span>';
	$audit_action = 'Deleted Switchboard Category';
	$audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
	$audit_source = strip_tags($_SERVER['REQUEST_URI']);
	$audit_domain = strip_tags($_SERVER['SERVER_NAME']);
	$audit_detailed_action = '<span class="dark-gray fw-bold">Deleted Switchboard Category</span>:' . ' ' . $switchboard_cat_name;
	$switchboard_cat_name = (strlen($switchboard_cat_name) > 30) ? substr($switchboard_cat_name, 0, 30).'...' : $switchboard_cat_name;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	$audit_stmt = mysqli_prepare($dbc, $audit_query);
	mysqli_stmt_bind_param($audit_stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $switchboard_cat_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
	mysqli_stmt_execute($audit_stmt);
	confirmQuery($audit_stmt);
	mysqli_stmt_close($audit_stmt);
}

mysqli_close($dbc);