<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['status' => 'error', 'message' => 'Access denied']));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit();
}

$query = "SELECT * FROM lh_priorities WHERE priority_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'priority_id' => $row['priority_id'],
            'priority_name' => $row['priority_name'],
            'priority_description' => $row['priority_description'],
            'priority_icon' => $row['priority_icon'],
            'priority_color' => $row['priority_color'],
            'priority_order' => $row['priority_order'],
            'is_active' => $row['is_active']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Priority not found']);
}

mysqli_stmt_close($stmt);
?>