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

if (empty($_POST['comment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing comment ID']);
    exit();
}

$comment_id = (int)$_POST['comment_id'];

$verify_query = "SELECT uc.update_id, uc.user_id, u.signal_id 
                 FROM lh_signal_update_comments uc
                 INNER JOIN lh_signal_updates u ON uc.update_id = u.update_id
                 INNER JOIN lh_signals s ON u.signal_id = s.signal_id
                 WHERE uc.comment_id = ? AND s.is_deleted = 0";
$verify_stmt = mysqli_prepare($dbc, $verify_query);
mysqli_stmt_bind_param($verify_stmt, 'i', $comment_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit();
}

$comment_data = mysqli_fetch_assoc($verify_result);

if (!$is_admin && $comment_data['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
	$query = "DELETE FROM lh_signal_update_comments WHERE comment_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $comment_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete comment');
    }
    
	$activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                      VALUES (?, ?, 'comment_deleted', 'Deleted a comment from an update', ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iis', $comment_data['signal_id'], $user_id, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
	$update_query = "UPDATE lh_signals SET updated_date = ? WHERE signal_id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'si', $current_datetime, $comment_data['signal_id']);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update signal timestamp');
    }
    
	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    
	error_log('Failed to delete update comment (ID: ' . $comment_id . '): ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete comment. Please try again or contact support.'
    ]);
}

mysqli_close($dbc);
?>
