<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_name'])) {
	$inquiry_creation_date = date_default_timezone_set("America/New_York");
    $inquiry_creation_date = date('m-d-Y g:i A');
    $inquiry_author = $_SESSION['display_name'];
    $inquiry_name = $_POST['inquiry_name'];
    $inquiry_image = $_POST['inquiry_image'];
    $inquiry_question = $_POST['inquiry_question'];
    $inquiry_info = $_POST['inquiry_info'];
    $inquiry_overview = $_POST['inquiry_overview'];
    $inquiry_status = $_POST['inquiry_status'];

	$query = "INSERT INTO poll_inquiry (inquiry_creation_date, inquiry_author, inquiry_name, inquiry_image, inquiry_question, inquiry_info, inquiry_overview, inquiry_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
   	 	mysqli_stmt_bind_param($stmt, 'ssssssss', $inquiry_creation_date, $inquiry_author, $inquiry_name, $inquiry_image, $inquiry_question, $inquiry_info, $inquiry_overview, $inquiry_status);
        
		if (mysqli_stmt_execute($stmt)) {
     	} else {
            die('Query Failed.');
        }
  	  	mysqli_stmt_close($stmt);
    } else {
        die('Query Prep Failed.');
    }
}

mysqli_close($dbc);