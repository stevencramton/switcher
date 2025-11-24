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
if (empty($_POST['service_id']) || empty($_POST['service_name']) || empty($_POST['service_color']) || empty($_POST['service_icon'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

$service_id = intval($_POST['service_id']);
$service_name = trim($_POST['service_name']);
$service_description = isset($_POST['service_description']) ? trim($_POST['service_description']) : '';
$service_color = trim($_POST['service_color']);
$service_icon = trim($_POST['service_icon']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Update service
$query = "UPDATE lh_services 
          SET service_name = ?, 
              service_description = ?, 
              service_color = ?, 
              service_icon = ?, 
              is_active = ?
          WHERE service_id = ?";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssii', 
    $service_name, 
    $service_description, 
    $service_color, 
    $service_icon, 
    $is_active, 
    $service_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Service updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update service: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>