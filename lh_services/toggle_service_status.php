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

// Validate required fields
if (!isset($_POST['service_id']) || !isset($_POST['is_active'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$service_id = intval($_POST['service_id']);
$is_active = intval($_POST['is_active']);

// Update service status
$query = "UPDATE lh_services SET is_active = ? WHERE service_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ii', $is_active, $service_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0 || mysqli_stmt_affected_rows($stmt) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'is_active' => $is_active
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Service not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>