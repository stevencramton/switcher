<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['id'];
$is_admin = checkRole('lighthouse_keeper');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => 'Permission denied - admin access required']);
    exit();
}

if (empty($_POST['update_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing update ID']);
    exit();
}

$update_id = (int)$_POST['update_id'];
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$is_internal = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 0;

$verify_query = "SELECT u.update_id, u.signal_id, u.user_id, u.update_type, s.is_deleted
                 FROM lh_signal_updates u
                 INNER JOIN lh_signals s ON u.signal_id = s.signal_id
                 WHERE u.update_id = ? AND s.is_deleted = 0";
$verify_stmt = mysqli_prepare($dbc, $verify_query);
mysqli_stmt_bind_param($verify_stmt, 'i', $update_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Update not found']);
    exit();
}

$update_data = mysqli_fetch_assoc($verify_result);

$update_creator_is_admin = checkRole('lighthouse_keeper', $update_data['user_id']);
if (!$update_creator_is_admin) {
    echo json_encode(['success' => false, 'message' => 'Cannot edit user-created updates']);
    exit();
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
 	$query = "UPDATE lh_signal_updates SET message = ?, is_internal = ? WHERE update_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sii', $message, $is_internal, $update_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update signal update');
    }
    
 	$activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                      VALUES (?, ?, 'update_edited', 'Edited a signal update', ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iis', $update_data['signal_id'], $user_id, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
 	$update_query = "UPDATE lh_signals SET updated_date = ? WHERE signal_id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'si', $current_datetime, $update_data['signal_id']);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update signal timestamp');
    }
    
 	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Update edited successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    
 	error_log('Failed to edit signal update (ID: ' . $update_id . ', User: ' . $user_id . '): ' . $e->getMessage());
    
  	echo json_encode([
        'success' => false,
        'message' => 'Failed to edit update. Please try again or contact support.'
    ]);
}

mysqli_close($dbc);
?>