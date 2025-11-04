<?php
session_start();
include '../../mysqli_connect.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['current']) && !empty($_POST['current'])) {
    $current_pass = strip_tags($_POST['current']);
    $switch_id = strip_tags($_SESSION['switch_id']);

    $stmt = $dbc->prepare("SELECT password FROM users WHERE switch_id = ?");
    $stmt->bind_param("s", $switch_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (password_verify($current_pass, $hashed_password)) {
        $response = "success";
    } else {
        $response = "fail";
    }

    $stmt->close();
    echo json_encode($response);
}
mysqli_close($dbc);