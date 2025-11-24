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

$query = "SELECT * FROM lh_docks WHERE is_active = 1 ORDER BY dock_order, dock_name";
$result = mysqli_query($dbc, $query);

if ($result) {
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'dock_id' => $row['dock_id'],
            'dock_name' => $row['dock_name'],
            'dock_description' => $row['dock_description'],
            'dock_color' => $row['dock_color'],
            'dock_icon' => $row['dock_icon'],
            'dock_order' => $row['dock_order']
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
        'message' => 'Failed to fetch docks'
    ]);
}
?>
