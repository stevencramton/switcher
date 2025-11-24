<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

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

if (empty($_POST['signal_id']) || empty($_POST['message_text'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$signal_id = (int)$_POST['signal_id'];
$message_text = trim($_POST['message_text']);
$is_internal = ($is_admin && isset($_POST['is_internal']) && $_POST['is_internal'] == '1') ? 1 : 0;

$verify_query = "SELECT sent_by FROM lh_signals WHERE signal_id = ? AND is_deleted = 0";
$verify_stmt = mysqli_prepare($dbc, $verify_query);
mysqli_stmt_bind_param($verify_stmt, 'i', $signal_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Signal not found']);
    exit();
}

$signal_data = mysqli_fetch_assoc($verify_result);

if (!$is_admin && $signal_data['sent_by'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit();
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
 	$query = "INSERT INTO lh_signal_messages (signal_id, user_id, message_text, is_internal, created_date) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iisis', $signal_id, $user_id, $message_text, $is_internal, $current_datetime);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to add comment');
    }
    
  	$activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                      VALUES (?, ?, 'comment_added', ?, ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    $activity_text = $is_internal ? 'Added internal note' : 'Added comment';
    mysqli_stmt_bind_param($activity_stmt, 'iiss', $signal_id, $user_id, $activity_text, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
  	$update_query = "UPDATE lh_signals SET updated_date = ? WHERE signal_id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'si', $current_datetime, $signal_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update signal timestamp');
    }
    
 	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Message added successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    error_log('Add comment error (Signal ID: ' . $signal_id . ', User ID: ' . $user_id . '): ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add comment. Please try again.'
    ]);
}
?>
