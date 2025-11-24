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

// Get signal IDs array
$signal_ids = isset($_POST['signal_ids']) ? $_POST['signal_ids'] : [];

// Validate that we have signal IDs
if (empty($signal_ids) || !is_array($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No signals selected']));
}

// Sanitize signal IDs
$signal_ids = array_map('intval', $signal_ids);
$signal_ids = array_filter($signal_ids, function($id) { return $id > 0; });

if (empty($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal IDs']));
}

// Check permissions for each signal
$placeholders = implode(',', array_fill(0, count($signal_ids), '?'));
$check_query = "SELECT signal_id, sent_by FROM lh_signals WHERE signal_id IN ($placeholders) AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);

// Bind parameters dynamically
$types = str_repeat('i', count($signal_ids));
mysqli_stmt_bind_param($check_stmt, $types, ...$signal_ids);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$signals_to_delete = [];
$permission_denied = [];

while ($signal = mysqli_fetch_assoc($check_result)) {
    // Only admin or signal creator can delete
    if ($is_admin || $signal['sent_by'] == $user_id) {
        $signals_to_delete[] = $signal['signal_id'];
    } else {
        $permission_denied[] = $signal['signal_id'];
    }
}

mysqli_stmt_close($check_stmt);

// If no signals can be deleted, return error
if (empty($signals_to_delete)) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'message' => 'You do not have permission to delete any of the selected signals'
    ]));
}

// *** CHANGE: Generate timestamp using PHP instead of MySQL NOW() ***
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($dbc);

try {
    $deleted_count = 0;
    
    // Get all attachments for the signals being deleted
    $placeholders_attachments = implode(',', array_fill(0, count($signals_to_delete), '?'));
    $attachments_query = "SELECT file_path FROM lh_signal_attachments WHERE signal_id IN ($placeholders_attachments)";
    $attachments_stmt = mysqli_prepare($dbc, $attachments_query);
    $types_attachments = str_repeat('i', count($signals_to_delete));
    mysqli_stmt_bind_param($attachments_stmt, $types_attachments, ...$signals_to_delete);
    mysqli_stmt_execute($attachments_stmt);
    $attachments_result = mysqli_stmt_get_result($attachments_stmt);
    
    $files_to_delete = [];
    while ($attachment = mysqli_fetch_assoc($attachments_result)) {
        $files_to_delete[] = $attachment['file_path'];
    }
    mysqli_stmt_close($attachments_stmt);
    
    // Hard delete each signal (and related records via CASCADE)
    foreach ($signals_to_delete as $signal_id) {
        // Delete the signal (related activity, messages, attachments will cascade)
        $delete_query = "DELETE FROM lh_signals WHERE signal_id = ?";
        $delete_stmt = mysqli_prepare($dbc, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $signal_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $deleted_count++;
        }
        
        mysqli_stmt_close($delete_stmt);
    }
    
    // Delete physical files from server
    foreach ($files_to_delete as $file_path) {
        $full_path = '../../' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    // Commit transaction
    mysqli_commit($dbc);
    
    $message = "Successfully deleted $deleted_count signal(s)";
    if (!empty($permission_denied)) {
        $message .= ". " . count($permission_denied) . " signal(s) could not be deleted due to permissions.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted_count' => $deleted_count,
        'permission_denied_count' => count($permission_denied)
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($dbc);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete signals: ' . $e->getMessage()
    ]);
}
?>