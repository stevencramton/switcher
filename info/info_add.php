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

if (isset($_POST['info_title'])) { 
    $info_icon = strip_tags($_POST['info_icon']);
    $info_icon_color = strip_tags($_POST['info_icon_color']);
	$info_text_color = strip_tags($_POST['info_text_color']);
	$info_background_color = strip_tags($_POST['info_background_color']);
	$info_border_color = strip_tags($_POST['info_border_color']);
	$info_button_text = strip_tags($_POST['info_button_text']);
	$info_btn_color = strip_tags($_POST['info_btn_color']);
	$info_btn_bg_color = strip_tags($_POST['info_btn_bg_color']);
	$info_btn_bord_color = strip_tags($_POST['info_btn_bord_color']);
	$info_title = strip_tags($_POST['info_title']);
	$info_subtitle = strip_tags($_POST['info_subtitle']);
    
	if (isset($_POST['info_message'])) {
	    $info_message = trim($_POST['info_message']);
	    if (strpos($info_message, '<br>') === false) {
	        $info_message = nl2br($info_message);
	    } else {
	        $info_message;
	    }
	}

	date_default_timezone_set("America/New_York");

	$info_publish = !empty($_POST['info_publish']) ? 
		date('Y-m-d H:i:s', strtotime(strip_tags($_POST['info_publish']))) : null;

	$info_expire = !empty($_POST['info_expire']) ? 
		date('Y-m-d H:i:s', strtotime(strip_tags($_POST['info_expire']))) : null;
	
    $info_status = strip_tags($_POST['info_status']); 
	$first_name = strip_tags($_SESSION['first_name']);
	$last_name = strip_tags($_SESSION['last_name']);
	$info_created_by = $first_name . ' ' . $last_name;
    date_default_timezone_set("America/New_York");
    $info_date = date('m-d-Y g:i A');

    $query = "INSERT INTO info (info_icon, info_icon_color, info_text_color, info_background_color, info_border_color, info_button_text, info_btn_color, info_btn_bg_color, info_btn_bord_color, info_title, info_subtitle, info_message, info_publish, info_expire, info_status, info_created_by, info_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("sssssssssssssssss", $info_icon, $info_icon_color, $info_text_color, $info_background_color, $info_border_color, $info_button_text, $info_btn_color, $info_btn_bg_color, $info_btn_bord_color, $info_title, $info_subtitle, $info_message, $info_publish, $info_expire, $info_status, $info_created_by, $info_date); 
    $stmt->execute();

    if ($stmt->error) {
        die('QUERY FAILED: ' . $stmt->error);
    }

    $stmt->close();

    $audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-regular fa-address-card"></i> Created Info</span>';
    $audit_action = 'Created Info';
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    
    $audit_detailed_action = '<span class="dark-gray fw-bold">Title</span>: ' . $info_title . '<br>' .
			 				 '<span class="dark-gray fw-bold">Subtitle</span>: ' . $info_subtitle . '<br>' . 
                             '<span class="dark-gray fw-bold">Icon</span>: ' . $info_icon . 
                             '<span class="float-end"><i class="' . $info_icon . '" style="color:' . $info_icon_color . ';"></i></span>';

    $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbc->prepare($audit_query);
    $stmt->bind_param("sssssssssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $info_title, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    $stmt->execute();
    confirmQuery($stmt);

    $stmt->close();
}

mysqli_close($dbc);