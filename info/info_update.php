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

if (isset($_POST['info_id'])) {
	$info_id = strip_tags($_POST['info_id']);
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
	$info_updated_by = $first_name . ' ' . $last_name;
    date_default_timezone_set("America/New_York");
    $info_updated_date = date('m-d-Y g:i A');
	
	$query = "UPDATE info SET info_icon = ?, info_icon_color = ?, info_text_color = ?, info_background_color = ?, info_border_color = ?, info_button_text = ?, info_btn_color = ?, info_btn_bg_color = ?, info_btn_bord_color = ?, info_title = ?, info_subtitle = ?, info_message = ?, info_publish = ?, info_expire = ?, info_status = ?, info_updated_by = ?, info_updated_date = ? WHERE info_id = ?";
	
	$stmt = $dbc->prepare($query);
	$stmt->bind_param("ssssssssssssssssss", $info_icon, $info_icon_color, $info_text_color, $info_background_color, $info_border_color, $info_button_text, $info_btn_color, $info_btn_bg_color, $info_btn_bord_color, $info_title, $info_subtitle, $info_message, $info_publish, $info_expire, $info_status, $info_updated_by, $info_updated_date, $info_id);
	$stmt->execute();
	confirmQuery($stmt);
	
	$stmt->close();
}
mysqli_close($dbc);