<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$signal_id = isset($_POST['signal_id']) ? (int)$_POST['signal_id'] : 0;
$update_type = isset($_POST['update_type']) ? $_POST['update_type'] : 'comment';
$old_value = isset($_POST['old_value']) ? trim($_POST['old_value']) : null;
$new_value = isset($_POST['new_value']) ? trim($_POST['new_value']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$is_internal = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 0;
$user_id = $_SESSION['id'];

if ($signal_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid signal ID']);
    exit();
}

// Verify signal exists and user has permission
$check_query = "SELECT sent_by FROM lh_signals WHERE signal_id = ? AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $signal_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Signal not found']);
    exit();
}

$signal = mysqli_fetch_assoc($check_result);
$is_admin = checkRole('lighthouse_keeper');

// Both users and admins can create updates
// Check permissions - users can update their own signals, admins can update any
if (!$is_admin && $signal['sent_by'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

// Only admins can create internal updates
if ($is_internal == 1 && !$is_admin) {
    $is_internal = 0;
}

try {
    // Start transaction
    mysqli_begin_transaction($dbc);
    
    // Get current datetime for consistent timestamps
    $current_datetime = date('Y-m-d H:i:s');
    
    // Insert the update with explicit created_date
    $insert_query = "INSERT INTO lh_signal_updates (signal_id, user_id, update_type, old_value, new_value, message, is_internal, created_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insert_stmt = mysqli_prepare($dbc, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'iissssss', $signal_id, $user_id, $update_type, $old_value, $new_value, $message, $is_internal, $current_datetime);
    
    if (!mysqli_stmt_execute($insert_stmt)) {
        throw new Exception('Failed to add update');
    }
    
    // Log activity
    $activity_text = $is_internal ? 'Added private update' : 'Added signal update';
    $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, created_date) 
                       VALUES (?, ?, ?, ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iiss', $signal_id, $user_id, $activity_text, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
    // Update signal updated_date
    $update_query = "UPDATE lh_signals SET updated_date = ? WHERE signal_id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'si', $current_datetime, $signal_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update signal timestamp');
    }
    
    // Commit transaction
    mysqli_commit($dbc);
    
    echo json_encode(['success' => true, 'message' => 'Update added successfully']);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add update: ' . $e->getMessage()
    ]);
}

mysqli_close($dbc);
?>