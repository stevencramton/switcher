<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isset($_POST['id'])) {
	$id = strip_tags($_POST['id']);
    $my_link_new_tab = strip_tags($_POST['my_link_new_tab']);
    $my_link_favorite = strip_tags($_POST['my_link_favorite']);
    $my_link_name = strip_tags($_POST['my_link_name']);
    $my_link_description = strip_tags($_POST['my_link_description']);
    $my_link_url = strip_tags($_POST['my_link_url']);
    $my_link_image = strip_tags($_POST['my_link_image']);
    $my_link_protocol = strip_tags($_POST['my_link_protocol']);

	$query = "UPDATE my_links SET my_link_name = ?, my_link_description = ?, my_link_url = ?, my_link_image = ?, my_link_favorite = ?, my_link_new_tab = ?, my_link_protocol = ? WHERE my_link_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "ssssssss", $my_link_name, $my_link_description, $my_link_url, $my_link_image, $my_link_favorite, $my_link_new_tab, $my_link_protocol, $id);
	mysqli_stmt_execute($stmt);
	confirmQuery(mysqli_stmt_affected_rows($stmt));
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);