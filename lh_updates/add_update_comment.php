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

$update_id = isset($_POST['update_id']) ? (int)$_POST['update_id'] : 0;
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
$is_internal = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 0;
$user_id = $_SESSION['id'];

if ($update_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid update ID']);
    exit();
}

if (empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Comment text is required']);
    exit();
}

$check_query = "SELECT u.signal_id, u.is_internal as update_is_internal, s.sent_by 
                FROM lh_signal_updates u
                JOIN lh_signals s ON u.signal_id = s.signal_id
                WHERE u.update_id = ? AND s.is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $update_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Update not found']);
    exit();
}
$update_data = mysqli_fetch_assoc($check_result);
$is_admin = checkRole('lighthouse_keeper');

if ($update_data['update_is_internal'] == 1) {
    $is_internal = 1;
}

if (!$is_admin && $update_data['sent_by'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}
try {
	mysqli_begin_transaction($dbc);
    
 	$insert_query = "INSERT INTO lh_signal_update_comments (update_id, user_id, comment_text, is_internal) 
                     VALUES (?, ?, ?, ?)";
    
    $insert_stmt = mysqli_prepare($dbc, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'iisi', $update_id, $user_id, $comment_text, $is_internal);
    
    if (!mysqli_stmt_execute($insert_stmt)) {
        throw new Exception('Failed to add comment');
    }
    
	$current_datetime = date('Y-m-d H:i:s');
    
 	$activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, created_date) 
                       VALUES (?, ?, 'Commented on update', ?)";
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
        'message' => 'Comment added successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    error_log('Add update comment error (Update ID: ' . $update_id . ', User ID: ' . $user_id . '): ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add comment. Please try again.'
    ]);
}
mysqli_close($dbc);
?>