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

if (isset($_POST['id'])) {
    $id = strip_tags($_POST['id']);
    $new_tab = strip_tags($_POST['new_tab']);
    $fav_link = strip_tags($_POST['fav_link']);
    $link_full_name = strip_tags($_POST['link_full_name']);
    $link_description = strip_tags($_POST['link_description']);
    $link_url = strip_tags($_POST['link_url']);
    $link_image = strip_tags($_POST['link_image']);
    $link_icon = strip_tags($_POST['link_icon']);
    $link_protocol = strip_tags($_POST['link_protocol']);

    $query = "UPDATE links SET link_full_name = ?, link_description = ?, link_url = ?, link_image = ?, link_icon = ?, favorite = ?, new_tab = ?, link_protocol = ? WHERE link_id = ?";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'ssssssssi', $link_full_name, $link_description, $link_url, $link_image, $link_icon, $fav_link, $new_tab, $link_protocol, $id);
        $result = mysqli_stmt_execute($stmt);
        confirmQuery($result);
        mysqli_stmt_close($stmt);
    } else {
        confirmQuery(false);
    }
}

mysqli_close($dbc);