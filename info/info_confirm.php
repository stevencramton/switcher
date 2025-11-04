<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('blog_view')){
    header("Location:../../index.php?msg1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['info_id']) && isset($_SESSION['id'])) {
	$info_id = intval($_POST['info_id']);
    $username = $_SESSION['user'];
    $switch_id = $_SESSION['switch_id'];

    $check_query = "SELECT * FROM info_confirm WHERE info_id = ? AND switch_id = ?";
    $stmt = $dbc->prepare($check_query);
    $stmt->bind_param("ii", $info_id, $switch_id);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo 'already_confirmed';
        exit();
    }

    $query = "SELECT * FROM info WHERE info_id = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param("i", $info_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    
    if ($info) {
        $info_title = $info['info_title'];
        $info_message = $info['info_message'];
        $info_created_by = $info['info_created_by'];
        $info_date_created = $info['info_date'];
        date_default_timezone_set("America/New_York");
      	$confirm_date = date('m-d-Y');
        $confirm_time = date('h:i A');
        
        $insert_query = "INSERT INTO info_confirm (info_id, switch_id, username, info_title_confirm, info_message_confirm, info_created_by_confirm, info_date_created_confirm, info_date_confirm, info_time_confirm)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                         
        $stmt = $dbc->prepare($insert_query);
        $stmt->bind_param('iisssssss', $info_id, $switch_id, $username, $info_title, $info_message, $info_created_by, $info_date_created, $confirm_date, $confirm_time);
        
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }
    }

    $stmt->close();
    $dbc->close();
}