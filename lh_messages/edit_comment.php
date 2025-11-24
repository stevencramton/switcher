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

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['id'];
$is_admin = checkRole('lighthouse_harbor');

// Validate required fields
if (empty($_POST['message_id']) || empty($_POST['message_text'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$message_id = (int)$_POST['message_id'];
$message_text = trim($_POST['message_text']);

// Verify comment exists and user has permission
$verify_query = "SELECT sm.signal_id, sm.user_id, s.sent_by 
                 FROM lh_signal_messages sm
                 INNER JOIN lh_signals s ON sm.signal_id = s.signal_id
                 WHERE sm.message_id = ?";
$verify_stmt = mysqli_prepare($dbc, $verify_query);
mysqli_stmt_bind_param($verify_stmt, 'i', $message_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit();
}

$comment_data = mysqli_fetch_assoc($verify_result);

// Users can only edit their own comments, or admins can edit any
if (!$is_admin && $comment_data['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

// *** CHANGE: Generate timestamp using PHP instead of MySQL NOW() ***
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($dbc);

try {
    // Update comment
    $query = "UPDATE lh_signal_messages SET message_text = ? WHERE message_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'si', $message_text, $message_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update comment');
    }
    
    // Log activity with PHP-generated timestamp
    $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                      VALUES (?, ?, 'comment_edited', 'Edited a comment', ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iis', $comment_data['signal_id'], $user_id, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
    // Update signal updated_date with PHP-generated timestamp
    $update_query = "UPDATE lh_signals SET updated_date = ? WHERE signal_id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'si', $current_datetime, $comment_data['signal_id']);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update signal timestamp');
    }
    
    // Commit transaction
    mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment updated successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update comment: ' . $e->getMessage()
    ]);
}
?>