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
$is_admin = checkRole('lighthouse_keeper');

// Get attachment ID
$attachment_id = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;

if ($attachment_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid attachment ID']));
}

// Get attachment details
$check_query = "SELECT a.*, s.sent_by 
                FROM lh_signal_attachments a
                LEFT JOIN lh_signals s ON a.signal_id = s.signal_id
                WHERE a.attachment_id = ?";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $attachment_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) == 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Attachment not found']));
}

$attachment = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

// Only admin or signal creator can delete attachments
if (!$is_admin && $attachment['sent_by'] != $user_id) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'You do not have permission to delete this attachment']));
}

// Delete the physical file from server
$file_path = '../../' . $attachment['file_path'];
if (file_exists($file_path)) {
    if (!unlink($file_path)) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Failed to delete file from server']));
    }
}

// Delete from database
$delete_query = "DELETE FROM lh_signal_attachments WHERE attachment_id = ?";
$delete_stmt = mysqli_prepare($dbc, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $attachment_id);

if (mysqli_stmt_execute($delete_stmt)) {
    mysqli_stmt_close($delete_stmt);
    
    // Log the deletion in activity
    $current_datetime = date('Y-m-d H:i:s');
    $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                       VALUES (?, ?, 'attachment_deleted', ?, ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    $activity_value = 'Deleted attachment: ' . $attachment['file_name'];
    mysqli_stmt_bind_param($activity_stmt, "iiss", $attachment['signal_id'], $user_id, $activity_value, $current_datetime);
    mysqli_stmt_execute($activity_stmt);
    mysqli_stmt_close($activity_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attachment deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete attachment from database'
    ]);
}

mysqli_close($dbc);
?>