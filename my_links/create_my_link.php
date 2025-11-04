<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_POST['my_link_new_tab'])) {
	$my_link_new_tab = strip_tags($_POST['my_link_new_tab']);
	$my_link_favorite = strip_tags($_POST['my_link_favorite']);
	$my_link_name = strip_tags($_POST['my_link_name']);
	$my_link_description = strip_tags($_POST['my_link_description']);
	$my_link_url = strip_tags($_POST['my_link_url']);
	$my_link_image = strip_tags($_POST['my_link_image']);
	$my_link_protocol = strip_tags($_POST['my_link_protocol']);
	$my_link_created_by = strip_tags($_SESSION['user']);
	$switch_id = strip_tags($_SESSION['switch_id']);
	
	$query = "INSERT INTO my_links (my_link_name, my_link_description, my_link_url, my_link_created_by, switch_id, my_link_image, my_link_favorite, my_link_new_tab, my_link_protocol) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
	
	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, 'sssssssss', $my_link_name, $my_link_description, $my_link_url, $my_link_created_by, $switch_id, $my_link_image, $my_link_favorite, $my_link_new_tab, $my_link_protocol);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	} else {
		die('Query preparation failed.');
	}
}
mysqli_close($dbc);