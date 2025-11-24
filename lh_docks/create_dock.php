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

if (empty($_POST['dock_name']) || empty($_POST['dock_color']) || empty($_POST['dock_icon'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

$dock_name = trim($_POST['dock_name']);
$dock_description = isset($_POST['dock_description']) ? trim($_POST['dock_description']) : '';
$dock_color = trim($_POST['dock_color']);
$dock_icon = trim($_POST['dock_icon']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

$order_query = "SELECT MAX(dock_order) as max_order FROM lh_docks";
$order_result = mysqli_query($dbc, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$next_order = ($order_row['max_order'] ?? 0) + 1;

$query = "INSERT INTO lh_docks 
          (dock_name, dock_description, dock_color, dock_icon, dock_order, is_active) 
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssii', 
    $dock_name, 
    $dock_description, 
    $dock_color, 
    $dock_icon, 
    $next_order, 
    $is_active
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Dock created successfully',
        'dock_id' => mysqli_insert_id($dbc)
    ]);
} else {
	error_log('Failed to create dock: ' . mysqli_error($dbc));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create dock. Please try again or contact support.'
    ]);
}

mysqli_stmt_close($stmt);
?>
