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
if (empty($_POST['sea_state_name']) || empty($_POST['sea_state_color'])) {
    echo json_encode(['success' => false, 'message' => 'Sea state name and color are required']);
    exit();
}

$sea_state_name = trim($_POST['sea_state_name']);
$sea_state_description = isset($_POST['sea_state_description']) ? trim($_POST['sea_state_description']) : '';
$sea_state_color = trim($_POST['sea_state_color']);
$sea_state_icon = isset($_POST['sea_state_icon']) ? trim($_POST['sea_state_icon']) : 'fa-solid fa-circle';
$is_active = isset($_POST['is_active']) ? 1 : 0;
$is_closed_resolution = isset($_POST['is_closed_resolution']) ? (int)$_POST['is_closed_resolution'] : 0;

// Get the next order number
$order_query = "SELECT MAX(sea_state_order) as max_order FROM lh_sea_states";
$order_result = mysqli_query($dbc, $order_query);
$order_row = mysqli_fetch_assoc($order_result);
$next_order = ($order_row['max_order'] ?? 0) + 1;

// Insert sea state
$query = "INSERT INTO lh_sea_states 
          (sea_state_name, sea_state_description, sea_state_color, sea_state_icon, sea_state_order, is_active, is_closed_resolution) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssiii', 
    $sea_state_name,
    $sea_state_description,
    $sea_state_color,
    $sea_state_icon,
    $next_order, 
    $is_active,
    $is_closed_resolution
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Sea state created successfully',
        'sea_state_id' => mysqli_insert_id($dbc)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create sea state: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>
