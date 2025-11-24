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

$query = "SELECT * FROM lh_sea_states WHERE sea_state_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'status' => 'success',
        'data' => [
            'sea_state_id' => $row['sea_state_id'],
            'sea_state_name' => $row['sea_state_name'],
            'sea_state_color' => $row['sea_state_color'],
            'sea_state_icon' => $row['sea_state_icon'],
            'sea_state_order' => $row['sea_state_order'],
            'is_active' => $row['is_active'],
            'is_closed_resolution' => $row['is_closed_resolution']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Sea state not found']);
}

mysqli_stmt_close($stmt);
?>