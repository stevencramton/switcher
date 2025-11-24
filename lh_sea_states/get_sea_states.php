<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
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

// Query to get all sea states (admins see all)
$query = "SELECT * FROM lh_sea_states ORDER BY sea_state_order ASC, sea_state_name ASC";
$result = mysqli_query($dbc, $query);

if (!$result) {
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
        'sea_state_id' => $row['sea_state_id'],
        'sea_state_name' => $row['sea_state_name'],
        'sea_state_color' => $row['sea_state_color'],
        'sea_state_icon' => $row['sea_state_icon'],
        'sea_state_order' => $row['sea_state_order'],
        'is_active' => $row['is_active']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $data,
    'count' => count($data)
]);
?>