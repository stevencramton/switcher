<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['response_answer'])) {
    
    foreach ($_POST["response_answer"] as $key => $value) {
     	$response_id = strip_tags($_POST['response_id'][$key]);
        $question_id = strip_tags($_POST['question_id'][$key]);
        $response_type = strip_tags($_POST['response_type'][$key]);
        $response_key = strip_tags($_POST['response_key'][$key]);
        $response_answer = strip_tags($_POST['response_answer'][$key]);
        $response_info = strip_tags($_POST['response_info'][$key]);

        $check_query = "SELECT response_id FROM poll_response WHERE response_id = ?";
        $check_stmt = mysqli_prepare($dbc, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'i', $response_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $update_query = "UPDATE poll_response SET question_id = ?, response_type = ?, response_key = ?, response_answer = ?, response_info = ? WHERE response_id = ?";
            $update_stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'issssi', $question_id, $response_type, $response_key, $response_answer, $response_info, $response_id);
            if (!mysqli_stmt_execute($update_stmt)) {
                echo("Error description.");
            }
            mysqli_stmt_close($update_stmt);
        } else {
            $insert_query = "INSERT INTO poll_response (question_id, response_type, response_key, response_answer, response_info) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($dbc, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'issss', $question_id, $response_type, $response_key, $response_answer, $response_info);
            if (!mysqli_stmt_execute($insert_stmt)) {
                echo("Error description.");
            }
            mysqli_stmt_close($insert_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}

mysqli_close($dbc);