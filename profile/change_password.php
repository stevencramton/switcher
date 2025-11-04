<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['current_pass']) && isset($_POST['new_pass']) && isset($_POST['verify'])) {
    $current_pass = strip_tags($_POST['current_pass']);
    $new_pass = strip_tags($_POST['new_pass']);
    $verify = strip_tags($_POST['verify']);
	$password_temp = 0;
    $switch_id = strip_tags($_SESSION['switch_id']);

	$stmt = $dbc->prepare("SELECT password FROM users WHERE switch_id = ?");
    $stmt->bind_param("s", $switch_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if (!password_verify($current_pass, $hashed_password)) {
        echo json_encode("error_current");
        $stmt->close();
        mysqli_close($dbc);
        exit;
    }

	$hashed_new_password = password_hash($new_pass, PASSWORD_BCRYPT);
    
	$stmt = $dbc->prepare("UPDATE users SET password = ?, password_temp = ?, last_pw_change = ? WHERE switch_id = ?");
    $date = date('m-d-Y g:i A');
    $stmt->bind_param("siss", $hashed_new_password, $password_temp, $date, $switch_id);

    if ($stmt->execute()) {
        $response = "success";
    } else {
        error_log("Database error: " . $stmt->error);
        $response = "error";
    }

    $stmt->close();
    mysqli_close($dbc);
    echo json_encode($response);
}