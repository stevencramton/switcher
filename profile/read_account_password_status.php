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

$user_id = $_SESSION['id'];
$query = "SELECT password FROM users WHERE id = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($password);
$stmt->fetch();
$stmt->close();

if (is_null($password)) {
    $status = 'Removed';
} else {
    $status = 'Active';
}

echo $status;

mysqli_close($dbc);
?>