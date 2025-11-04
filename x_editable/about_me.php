<?php
ob_start();
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

if (isset($_SESSION['id']) && isset($_POST['pk'])) {
    $id = mysqli_real_escape_string($dbc, $_POST['pk']);
    $value = htmlspecialchars($_POST['value'], ENT_QUOTES, 'UTF-8');
    $value = str_replace('&amp;', '&', $value);
    $values = nl2br($value, false);
	$query = "UPDATE users SET about_me = ? WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "si", $values, $id);
	$result = mysqli_stmt_execute($stmt);

    if ($result) {
        echo json_encode(['status' => 'success', 'about_me' => $values]);
    } else {
        error_log("Failed to update about_me.");
        http_response_code(500);
        die("Failed to update about_me. Please try again later.");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);