<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')){
	header("Location:index.php?msg1");
	exit();
}

if (isset($_POST['share_name'])) {
	$share_name = strip_tags($_POST['share_name']);
    $share_ad_group = strip_tags($_POST['share_ad_group']);
    $share_type = strip_tags($_POST['share_type']);
    $share_server = strip_tags($_POST['share_server']);
    $share_notes = strip_tags($_POST['share_notes']);
    date_default_timezone_set("America/New_York");
    $share_updated_date = date('m-d-Y');
    $share_updated_time = date('g:i A');
    $first_name = strip_tags($_SESSION['first_name']);
    $last_name = strip_tags($_SESSION['last_name']);
	$share_updated_by = $first_name . " " . $last_name;
	$share_mapping = "//" . $share_server . ".plymouth.edu/" . $share_name;

    $query = "INSERT INTO shares (share_drive_name, share_ad_name, share_mapping, share_server, share_notes, share_type, share_updated_date, share_updated_time, share_updated_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssssss', $share_name, $share_ad_group, $share_mapping, $share_server, $share_notes, $share_type, $share_updated_date, $share_updated_time, $share_updated_by);
        
        if (mysqli_stmt_execute($stmt)) {

        } else {
            echo "Error description.";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
}

mysqli_close($dbc);