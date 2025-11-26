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
$is_admin = checkRole('lighthouse_keeper');

$signal_ids = isset($_POST['signal_ids']) ? $_POST['signal_ids'] : [];

if (empty($signal_ids) || !is_array($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No signals selected']));
}

$signal_ids = array_map('intval', $signal_ids);
$signal_ids = array_filter($signal_ids, function($id) { return $id > 0; });

if (empty($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal IDs']));
}

$placeholders = implode(',', array_fill(0, count($signal_ids), '?'));
$check_query = "SELECT signal_id, sent_by FROM lh_signals WHERE signal_id IN ($placeholders) AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);

$types = str_repeat('i', count($signal_ids));
mysqli_stmt_bind_param($check_stmt, $types, ...$signal_ids);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$signals_to_delete = [];
$permission_denied = [];

while ($signal = mysqli_fetch_assoc($check_result)) {
 	if ($is_admin || $signal['sent_by'] == $user_id) {
        $signals_to_delete[] = $signal['signal_id'];
    } else {
        $permission_denied[] = $signal['signal_id'];
    }
}

mysqli_stmt_close($check_stmt);

if (empty($signals_to_delete)) {
    http_response_code(403);
    die(json_encode([
        'success' => false, 
        'message' => 'You do not have permission to delete any of the selected signals'
    ]));
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
    $deleted_count = 0;
    
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
    
  	foreach ($signals_to_delete as $signal_id) {
     	$delete_query = "DELETE FROM lh_signals WHERE signal_id = ?";
        $delete_stmt = mysqli_prepare($dbc, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $signal_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $deleted_count++;
        }
        
        mysqli_stmt_close($delete_stmt);
    }
    
  	foreach ($files_to_delete as $file_path) {
        $full_path = '../../' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
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
 	mysqli_rollback($dbc);
    
    error_log('Bulk delete keeper signals error (User ID: ' . $user_id . '): ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete signals. Please try again.'
    ]);
}
?>