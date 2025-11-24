<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

// Check admin role
if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['status' => 'error', 'message' => 'Access denied - Admin only']));
}

// Query to get all lh_docks (not just active ones - admins see all)
$query = "SELECT * FROM lh_docks ORDER BY dock_order ASC, dock_name ASC";
$result = mysqli_query($dbc, $query);

if (!$result) {
    // Database query failed
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed: ' . mysqli_error($dbc)
    ]);
    exit();
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'dock_id' => $row['dock_id'],
        'dock_name' => $row['dock_name'],
        'dock_description' => $row['dock_description'],
        'dock_color' => $row['dock_color'],
        'dock_icon' => $row['dock_icon'],
        'dock_order' => $row['dock_order'],
        'is_active' => $row['is_active'],
        'is_default' => isset($row['is_default']) ? $row['is_default'] : 0
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $data,
    'count' => count($data)
]);
?>