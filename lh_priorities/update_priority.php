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

if (empty($_POST['priority_id']) || empty($_POST['priority_name']) || empty($_POST['priority_icon']) || empty($_POST['priority_color'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

$priority_id = intval($_POST['priority_id']);
$priority_name = trim($_POST['priority_name']);
$priority_description = isset($_POST['priority_description']) && trim($_POST['priority_description']) !== '' ? trim($_POST['priority_description']) : null;
$priority_icon = trim($_POST['priority_icon']);
$priority_color = trim($_POST['priority_color']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Update priority
$query = "UPDATE lh_priorities 
          SET priority_name = ?, priority_description = ?, priority_icon = ?, priority_color = ?, is_active = ?
          WHERE priority_id = ?";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ssssii', 
    $priority_name,
    $priority_description,
    $priority_icon,
    $priority_color, 
    $is_active,
    $priority_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Priority updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update priority: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>