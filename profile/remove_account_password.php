<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(["status" => "error", "message" => "Unauthorized request."]));
}

if (!checkRole('user_profile')) {
    echo json_encode(["status" => "error", "message" => "Insufficient permissions."]);
    exit();
}

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

$userId = $_SESSION['id'];
$query = "UPDATE users SET password = NULL WHERE id = ?";

if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["status" => "success", "message" => "Your password has been removed successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove password."]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(["status" => "error", "message" => "Database error."]);
}

mysqli_close($dbc);
?>
