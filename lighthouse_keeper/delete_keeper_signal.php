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

// Get signal ID
$signal_id = isset($_POST['signal_id']) ? (int)$_POST['signal_id'] : 0;

if ($signal_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal ID']));
}

// Check if user has permission to delete this signal
$check_query = "SELECT signal_id, sent_by FROM lh_signals WHERE signal_id = ? AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $signal_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) == 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Signal not found']));
}

$signal = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

// Only admin or signal creator can delete
if (!$is_admin && $signal['sent_by'] != $user_id) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'You do not have permission to delete this signal']));
}

// Get all attachments for this signal before deleting
$attachments_query = "SELECT file_path FROM lh_signal_attachments WHERE signal_id = ?";
$attachments_stmt = mysqli_prepare($dbc, $attachments_query);
mysqli_stmt_bind_param($attachments_stmt, "i", $signal_id);
mysqli_stmt_execute($attachments_stmt);
$attachments_result = mysqli_stmt_get_result($attachments_stmt);

$files_to_delete = [];
while ($attachment = mysqli_fetch_assoc($attachments_result)) {
    $files_to_delete[] = $attachment['file_path'];
}
mysqli_stmt_close($attachments_stmt);

// Hard delete the signal (related records cascade automatically)
$delete_query = "DELETE FROM lh_signals WHERE signal_id = ?";
$delete_stmt = mysqli_prepare($dbc, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $signal_id);

if (mysqli_stmt_execute($delete_stmt)) {
    mysqli_stmt_close($delete_stmt);
    
    // Delete physical files from server
    foreach ($files_to_delete as $file_path) {
        $full_path = '../../' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Signal deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete signal'
    ]);
}
?>