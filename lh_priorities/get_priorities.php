<?php
session_start();
include '../../mysqli_connect.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

// Query ALL fields including priority_description
$query = "SELECT 
    priority_id,
    priority_name,
    priority_description,
    priority_icon,
    priority_color,
    priority_order
FROM lh_priorities 
WHERE is_active = 1 
ORDER BY priority_order, priority_name";

$result = mysqli_query($dbc, $query);

if ($result) {
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'priority_id' => $row['priority_id'],
            'priority_name' => $row['priority_name'],
            'priority_icon' => $row['priority_icon'],
            'priority_color' => $row['priority_color'],
            'priority_description' => $row['priority_description'] ?? '',
            'priority_order' => $row['priority_order']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch priorities'
    ]);
}
?>