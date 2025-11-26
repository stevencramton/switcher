<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['status' => 'error', 'message' => 'Access denied']));
}

// Validate service ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid service ID']);
    exit();
}

$service_id = intval($_GET['id']);

// Get service
$query = "SELECT * FROM lh_services WHERE service_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $service_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'service_id' => $row['service_id'],
            'service_name' => $row['service_name'],
            'service_description' => $row['service_description'],
            'service_color' => $row['service_color'],
            'service_icon' => $row['service_icon'],
            'service_order' => $row['service_order'],
            'is_active' => $row['is_active']
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Service not found'
    ]);
}

mysqli_stmt_close($stmt);
?>