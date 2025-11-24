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

// Query to get all lh_services (not just active ones - admins see all)
$query = "SELECT * FROM lh_services ORDER BY service_order ASC, service_name ASC";
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
        'service_id' => $row['service_id'],
        'service_name' => $row['service_name'],
        'service_description' => $row['service_description'],
        'service_color' => $row['service_color'],
        'service_icon' => $row['service_icon'],
        'service_order' => $row['service_order'],
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