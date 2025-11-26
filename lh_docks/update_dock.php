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

if (empty($_POST['dock_id']) || empty($_POST['dock_name']) || empty($_POST['dock_color']) || empty($_POST['dock_icon'])) {
    $missing = [];
    if (empty($_POST['dock_id'])) $missing[] = 'dock_id';
    if (empty($_POST['dock_name'])) $missing[] = 'dock_name';
    if (empty($_POST['dock_color'])) $missing[] = 'dock_color';
    if (empty($_POST['dock_icon'])) $missing[] = 'dock_icon';
    
    echo json_encode([
        'success' => false, 
        'message' => 'Required fields missing: ' . implode(', ', $missing),
        'debug' => $_POST
    ]);
    exit();
}

$dock_id = (int)$_POST['dock_id'];
$dock_name = trim($_POST['dock_name']);
$dock_description = isset($_POST['dock_description']) ? trim($_POST['dock_description']) : '';
$dock_color = trim($_POST['dock_color']);
$dock_icon = trim($_POST['dock_icon']);
$is_active = isset($_POST['is_active']) ? 1 : 0;

$query = "UPDATE lh_docks SET 
          dock_name = ?, 
          dock_description = ?, 
          dock_color = ?, 
          dock_icon = ?, 
          is_active = ?
          WHERE dock_id = ?";

$stmt = mysqli_prepare($dbc, $query);

if (!$stmt) {
	error_log('Failed to prepare update dock statement (ID: ' . $dock_id . '): ' . mysqli_error($dbc));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update dock. Please try again or contact support.'
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, 'ssssii', 
    $dock_name, 
    $dock_description, 
    $dock_color, 
    $dock_icon, 
    $is_active,
    $dock_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Dock updated successfully',
        'data' => [
            'dock_id' => $dock_id,
            'dock_name' => $dock_name,
            'dock_color' => $dock_color,
            'dock_icon' => $dock_icon
        ]
    ]);
} else {
	error_log('Failed to update dock (ID: ' . $dock_id . '): ' . mysqli_stmt_error($stmt));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update dock. Please try again or contact support.'
    ]);
}

mysqli_stmt_close($stmt);
?>