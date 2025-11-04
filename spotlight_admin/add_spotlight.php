<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('spotlight_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_name'])) {
	date_default_timezone_set("America/New_York");
    $inquiry_creation_date = date('m-d-Y g:i A');
	$inquiry_author = $_SESSION['display_name'];
    $inquiry_name = $_POST['inquiry_name'];
    $inquiry_image = $_POST['inquiry_image'];
    $inquiry_nominee_image = $_POST['inquiry_nominee_image'];
    $nominee_name = $_POST['nominee_name'];
    $inquiry_overview = $_POST['inquiry_overview'];
    $bullet_one = $_POST['bullet_one'];
    $bullet_two = $_POST['bullet_two'];
    $bullet_three = $_POST['bullet_three'];
    $special_preview = $_POST['special_preview'];
    $inquiry_opening = $_POST['inquiry_opening'];
    $inquiry_closing = $_POST['inquiry_closing'];
    $showcase_start_date = $_POST['showcase_start_date'] ?? null;
    $showcase_end_date = $_POST['showcase_end_date'] ?? null;
    $inquiry_status = $_POST['inquiry_status'];

  	if (empty($showcase_start_date)) {
        $showcase_start_date = null;
    }
    if (empty($showcase_end_date)) {
        $showcase_end_date = null;
    }

	$query = "INSERT INTO spotlight_inquiry (inquiry_creation_date, inquiry_author, inquiry_name, inquiry_image, inquiry_nominee_image, nominee_name, inquiry_overview, bullet_one, bullet_two, bullet_three, special_preview, inquiry_opening, inquiry_closing, showcase_start_date, showcase_end_date, inquiry_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssssssssssss", $inquiry_creation_date, $inquiry_author, $inquiry_name, $inquiry_image, $inquiry_nominee_image, $nominee_name, $inquiry_overview, $bullet_one, $bullet_two, $bullet_three, $special_preview, $inquiry_opening, $inquiry_closing, $showcase_start_date, $showcase_end_date, $inquiry_status);
		$execute_result = mysqli_stmt_execute($stmt);
		if (!$execute_result) {
            die('Query Failed.');
        }
		mysqli_stmt_close($stmt);
    } else {
        die('Query Preperation Failed.');
    }
}

mysqli_close($dbc);
?>