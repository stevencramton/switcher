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

if (empty($_POST['priority_name']) || empty($_POST['priority_icon']) || empty($_POST['priority_color'])) {
    echo json_encode(['success' => false, 'message' => 'Priority name, icon, and color are required']);
    exit();
}

$priority_name = trim($_POST['priority_name']);
$priority_description = isset($_POST['priority_description']) && trim($_POST['priority_description']) !== '' ? trim($_POST['priority_description']) : null;
$priority_icon = trim($_POST['priority_icon']);
$priority_color = trim($_POST['priority_color']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

$order_query = "SELECT MAX(priority_order) as max_order FROM lh_priorities";
$order_result = mysqli_query($dbc, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$next_order = ($order_row['max_order'] ?? 0) + 1;

$query = "INSERT INTO lh_priorities 
          (priority_name, priority_description, priority_icon, priority_color, priority_order, is_active) 
          VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssii', 
    $priority_name,
    $priority_description, 
    $priority_icon,
    $priority_color, 
    $next_order, 
    $is_active
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Priority created successfully',
        'priority_id' => mysqli_insert_id($dbc)
    ]);
} else {
	error_log('Failed to create priority: ' . mysqli_error($dbc));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create priority. Please try again or contact support.'
    ]);
}

mysqli_stmt_close($stmt);
?>