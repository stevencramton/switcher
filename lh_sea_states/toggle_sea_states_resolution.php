<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$sea_state_id = isset($_POST['sea_state_id']) ? intval($_POST['sea_state_id']) : 0;
$is_closed_resolution = isset($_POST['is_closed_resolution']) ? intval($_POST['is_closed_resolution']) : 0;

if ($sea_state_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sea state ID']);
    exit();
}

// Validate is_closed_resolution value
if ($is_closed_resolution !== 0 && $is_closed_resolution !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid resolution value']);
    exit();
}

// Update the sea state resolution type
$query = "UPDATE lh_sea_states SET is_closed_resolution = ? WHERE sea_state_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, "ii", $is_closed_resolution, $sea_state_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Resolution type updated successfully',
        'is_closed_resolution' => $is_closed_resolution
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update resolution type: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>