<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_links_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['link_full_name'])) {

	$new_tab = isset($_POST['new_tab']) ? strip_tags($_POST['new_tab']) : '';
	$fav_link = isset($_POST['fav_link']) ? strip_tags($_POST['fav_link']) : '';
	$link_full_name = isset($_POST['link_full_name']) ? strip_tags($_POST['link_full_name']) : '';
	$link_description = isset($_POST['link_description']) ? strip_tags($_POST['link_description']) : '';
	$link_url = isset($_POST['link_url']) ? strip_tags($_POST['link_url']) : '';
	$link_image = isset($_POST['link_image']) ? strip_tags($_POST['link_image']) : '';
	$link_icon = isset($_POST['link_icon']) ? strip_tags($_POST['link_icon']) : '';
	$link_protocol = isset($_POST['link_protocol']) ? strip_tags($_POST['link_protocol']) : '';
	$link_creator = isset($_SESSION['user']) ? strip_tags($_SESSION['user']) : '';
    
    $display = 0;

    $display_query = "SELECT * FROM links";

    if ($display_result = mysqli_query($dbc, $display_query)) {
        while ($display_row = mysqli_fetch_array($display_result)) {
            $id = $display_row['link_id'];

            $update_display_query = "UPDATE links SET link_display_order = link_display_order + 1 WHERE link_id = ?";
            if ($update_stmt = mysqli_prepare($dbc, $update_display_query)) {
                mysqli_stmt_bind_param($update_stmt, 'i', $id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
        }
    }

    $query = "INSERT INTO links (link_display_order, link_full_name, link_description, link_url, link_image, link_created_by, link_icon, favorite, new_tab, link_protocol) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'isssssssss', $display, $link_full_name, $link_description, $link_url, $link_image, $link_creator, $link_icon, $fav_link, $new_tab, $link_protocol);
        $result = mysqli_stmt_execute($stmt);
        confirmQuery($result);
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($dbc);