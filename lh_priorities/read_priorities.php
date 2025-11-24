<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['status' => 'error', 'message' => 'Access denied - Admin only']));
}

$query = "SELECT * FROM lh_priorities ORDER BY priority_order ASC, priority_name ASC";
$result = mysqli_query($dbc, $query);

if (!$result) {
	error_log('Failed to read priorities: ' . mysqli_error($dbc));
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed. Please try again or contact support.'
    ]);
    exit();
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'priority_id' => $row['priority_id'],
        'priority_name' => $row['priority_name'],
        'priority_description' => $row['priority_description'],
        'priority_icon' => $row['priority_icon'],
        'priority_color' => $row['priority_color'],
        'priority_order' => $row['priority_order'],
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
