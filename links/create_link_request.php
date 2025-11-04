<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('hd_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['suggested_link_title'])) {
    $suggested_link_title = strip_tags($_POST['suggested_link_title']);
    $suggested_link_address = strip_tags($_POST['suggested_link_address']);
    $suggested_link_location = strip_tags($_POST['suggested_link_location']);

	date_default_timezone_set('America/New_York');
	$link_request_time = date('m-d-Y g:i A');
	$link_request_sender = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

    $query = "INSERT INTO links_request (link_request_title, link_request_url, link_request_location, link_request_time, link_request_sender) 
              VALUES (?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'sssss', $suggested_link_title, $suggested_link_address, $suggested_link_location, $link_request_time, $link_request_sender);

        if (mysqli_stmt_execute($stmt)) {
            confirmQuery(true);
        } else {
            confirmQuery(false);
        }

        mysqli_stmt_close($stmt);
    } else {
        confirmQuery(false);
    }

    mysqli_close($dbc);
}