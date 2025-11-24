<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

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

if (!isset($_POST['dock_id']) || !is_numeric($_POST['dock_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid dock ID']);
    exit();
}
$dock_id = (int)$_POST['dock_id'];
$user_id = $_SESSION['id'];

$check_query = "SELECT dock_id, dock_name, is_active FROM lh_docks WHERE dock_id = ?";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $dock_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Dock not found']);
    exit();
}

$dock = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);
if (!$dock['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Cannot set an inactive dock as default']);
    exit();
}

mysqli_begin_transaction($dbc);

try {
	$clear_query = "UPDATE lh_docks SET is_default = 0";
    if (!mysqli_query($dbc, $clear_query)) {
        throw new Exception('Failed to clear default flags');
    }
    
	$set_query = "UPDATE lh_docks SET is_default = 1 WHERE dock_id = ?";
    $stmt = mysqli_prepare($dbc, $set_query);
    mysqli_stmt_bind_param($stmt, "i", $dock_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to set default dock');
    }
    
    mysqli_stmt_close($stmt);
    
	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Default dock updated successfully',
        'dock_name' => $dock['dock_name']
    ]);
    
} catch (Exception $e) {
	mysqli_rollback($dbc);
    
 	error_log('set_default_dock.php error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update default dock. Please try again.'
    ]);
}
mysqli_close($dbc);
?>