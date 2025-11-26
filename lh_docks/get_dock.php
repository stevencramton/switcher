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

if (empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dock ID required']);
    exit();
}

$dock_id = (int)$_GET['id'];

$query = "SELECT * FROM lh_docks WHERE dock_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $dock_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'dock_id' => $row['dock_id'],
            'dock_name' => $row['dock_name'],
            'dock_description' => $row['dock_description'],
            'dock_color' => $row['dock_color'],
            'dock_icon' => $row['dock_icon'],
            'dock_order' => $row['dock_order'],
            'is_active' => $row['is_active']
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Dock not found'
    ]);
}

mysqli_stmt_close($stmt);
?>
