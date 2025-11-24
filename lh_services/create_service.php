<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['success' => false, 'message' => 'Access denied']));
}

if (empty($_POST['service_name']) || empty($_POST['service_color']) || empty($_POST['service_icon'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

$service_name = trim($_POST['service_name']);
$service_description = isset($_POST['service_description']) ? trim($_POST['service_description']) : '';
$service_color = trim($_POST['service_color']);
$service_icon = trim($_POST['service_icon']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

$order_query = "SELECT MAX(service_order) as max_order FROM lh_services";
$order_result = mysqli_query($dbc, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$next_order = ($order_row['max_order'] ?? 0) + 1;

$query = "INSERT INTO lh_services 
          (service_name, service_description, service_color, service_icon, service_order, is_active) 
          VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssii', 
    $service_name, 
    $service_description, 
    $service_color, 
    $service_icon, 
    $next_order, 
    $is_active
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Service created successfully',
        'service_id' => mysqli_insert_id($dbc)
    ]);
} else {
    error_log('Create service error: ' . mysqli_error($dbc));
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create service. Please try again.'
    ]);
}
mysqli_stmt_close($stmt);
?>
