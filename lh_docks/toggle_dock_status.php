<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['success' => false, 'message' => 'Access denied']));
}

if (!isset($_POST['dock_id']) || !isset($_POST['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$dock_id = (int)$_POST['dock_id'];
$is_active = (int)$_POST['is_active'];

$query = "UPDATE lh_docks SET is_active = ? WHERE dock_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ii', $is_active, $dock_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Beacon updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status'
    ]);
}

mysqli_stmt_close($stmt);
?>
