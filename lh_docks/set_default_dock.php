<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

if (!checkRole('lighthouse_maritime')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Insufficient permissions']));
}

// Validate required fields
if (!isset($_POST['dock_id']) || !is_numeric($_POST['dock_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid dock ID']);
    exit();
}

$dock_id = (int)$_POST['dock_id'];

// Start transaction
mysqli_begin_transaction($dbc);

try {
    // First, clear all default flags
    $clear_query = "UPDATE lh_docks SET is_default = 0";
    if (!mysqli_query($dbc, $clear_query)) {
        throw new Exception('Failed to clear default flags');
    }
    
    // Then, set the selected dock as default
    $set_query = "UPDATE lh_docks SET is_default = 1 WHERE dock_id = ?";
    $stmt = mysqli_prepare($dbc, $set_query);
    mysqli_stmt_bind_param($stmt, "i", $dock_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to set default dock');
    }
    
    mysqli_stmt_close($stmt);
    
    // Commit transaction
    mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Default dock updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($dbc);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update default dock: ' . $e->getMessage()
    ]);
}
?>