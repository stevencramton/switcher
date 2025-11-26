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

if (!$is_admin) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Only administrators can change signal status']));
}

$signal_ids = isset($_POST['signal_ids']) ? $_POST['signal_ids'] : [];
$status_id = isset($_POST['status_id']) ? $_POST['status_id'] : null;

if (empty($signal_ids) || !is_array($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No signals selected']));
}

if ($status_id === null || $status_id === '' || $status_id === 'null') {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Please select a status']));
}

$status_id = (int)$status_id;

$status_check_query = "SELECT sea_state_id, sea_state_name, sea_state_color FROM lh_sea_states WHERE sea_state_id = ? AND is_active = 1";
$status_check_stmt = mysqli_prepare($dbc, $status_check_query);
mysqli_stmt_bind_param($status_check_stmt, 'i', $status_id);
mysqli_stmt_execute($status_check_stmt);
$status_check_result = mysqli_stmt_get_result($status_check_stmt);

if (mysqli_num_rows($status_check_result) == 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Status not found or is inactive']));
}

$status_data = mysqli_fetch_assoc($status_check_result);
$status_name = $status_data['sea_state_name'];
mysqli_stmt_close($status_check_stmt);

$signal_ids = array_map('intval', $signal_ids);
$signal_ids = array_filter($signal_ids, function($id) { return $id > 0; });

if (empty($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal IDs']));
}

$placeholders = implode(',', array_fill(0, count($signal_ids), '?'));
$check_query = "SELECT signal_id, sea_state_id FROM lh_signals WHERE signal_id IN ($placeholders) AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);

$types = str_repeat('i', count($signal_ids));
mysqli_stmt_bind_param($check_stmt, $types, ...$signal_ids);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$signals_to_update = [];
$already_status = [];

while ($signal = mysqli_fetch_assoc($check_result)) {
	if ($signal['sea_state_id'] == $status_id) {
        $already_status[] = $signal['signal_id'];
        continue;
    }
    
    $signals_to_update[] = $signal['signal_id'];
}

mysqli_stmt_close($check_stmt);

if (empty($signals_to_update)) {
    if (!empty($already_status)) {
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'message' => 'All selected signals already have this status'
        ]));
    } else {
        http_response_code(404);
        die(json_encode([
            'success' => false, 
            'message' => 'No valid signals found to update'
        ]));
    }
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
    $updated_count = 0;
    
 	foreach ($signals_to_update as $signal_id) {
        $update_query = "UPDATE lh_signals SET sea_state_id = ?, updated_date = ? WHERE signal_id = ?";
        $update_stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($update_stmt, "isi", $status_id, $current_datetime, $signal_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $updated_count++;
            
         	$activity_type = 'status_change';
            $activity_value = "Status changed to $status_name";
            
            $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                               VALUES (?, ?, ?, ?, ?)";
            $activity_stmt = mysqli_prepare($dbc, $activity_query);
            mysqli_stmt_bind_param($activity_stmt, "iisss", $signal_id, $user_id, $activity_type, $activity_value, $current_datetime);
            mysqli_stmt_execute($activity_stmt);
            mysqli_stmt_close($activity_stmt);
        }
        
        mysqli_stmt_close($update_stmt);
    }
    
  	mysqli_commit($dbc);
    
    $message = "Successfully changed status of $updated_count signal(s) to '$status_name'";
    
    if (!empty($already_status)) {
        $message .= ". " . count($already_status) . " signal(s) already had this status.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'updated_count' => $updated_count,
        'already_status_count' => count($already_status),
        'status_name' => $status_name
    ]);
    
} catch (Exception $e) {
 	mysqli_rollback($dbc);
    
    error_log('Bulk change status keeper signals error (User ID: ' . $user_id . '): ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to change status. Please try again.'
    ]);
}
?>