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
$target_dock_id = isset($_POST['dock_id']) ? $_POST['dock_id'] : '';

$is_unassign = ($target_dock_id === 'null');
$target_dock_id = $is_unassign ? null : (int)$target_dock_id;

if (empty($signal_ids) || !is_array($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No signals selected']));
}

$target_dock = null;

if (!$is_unassign) {
	if ($target_dock_id <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid dock selected']));
    }
    
	$dock_check_query = "SELECT dock_id, dock_name FROM lh_docks WHERE dock_id = ? AND is_active = 1";
    $dock_check_stmt = mysqli_prepare($dbc, $dock_check_query);
    mysqli_stmt_bind_param($dock_check_stmt, 'i', $target_dock_id);
    mysqli_stmt_execute($dock_check_stmt);
    $dock_check_result = mysqli_stmt_get_result($dock_check_stmt);
    
    if (mysqli_num_rows($dock_check_result) == 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Target dock not found']));
    }
    
    $target_dock = mysqli_fetch_assoc($dock_check_result);
    mysqli_stmt_close($dock_check_stmt);
}

$signal_ids = array_map('intval', $signal_ids);
$signal_ids = array_filter($signal_ids, function($id) { return $id > 0; });

if (empty($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal IDs']));
}

$placeholders = implode(',', array_fill(0, count($signal_ids), '?'));
$check_query = "SELECT signal_id, sent_by, dock_id FROM lh_signals WHERE signal_id IN ($placeholders) AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);

$types = str_repeat('i', count($signal_ids));
mysqli_stmt_bind_param($check_stmt, $types, ...$signal_ids);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$signals_to_move = [];
$permission_denied = [];
$already_in_dock = [];

while ($signal = mysqli_fetch_assoc($check_result)) {
	if ($is_unassign) {
        if ($signal['dock_id'] === null || $signal['dock_id'] == 0) {
            $already_in_dock[] = $signal['signal_id'];
            continue;
        }
    } else {
    	if ($signal['dock_id'] == $target_dock_id) {
            $already_in_dock[] = $signal['signal_id'];
            continue;
        }
    }
    
 	if ($is_admin || $signal['sent_by'] == $user_id) {
        $signals_to_move[] = $signal['signal_id'];
    } else {
        $permission_denied[] = $signal['signal_id'];
    }
}

mysqli_stmt_close($check_stmt);

if (empty($signals_to_move)) {
    if (!empty($already_in_dock)) {
        $error_message = $is_unassign 
            ? 'All selected signals are already unassigned from docks'
            : 'All selected signals are already in this dock';
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'message' => $error_message
        ]));
    } else {
        http_response_code(403);
        die(json_encode([
            'success' => false, 
            'message' => 'You do not have permission to move any of the selected signals'
        ]));
    }
}

$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
    $moved_count = 0;
    
 	foreach ($signals_to_move as $signal_id) {
        if ($is_unassign) {
         	$move_query = "UPDATE lh_signals SET dock_id = NULL, updated_date = ? WHERE signal_id = ?";
            $move_stmt = mysqli_prepare($dbc, $move_query);
            mysqli_stmt_bind_param($move_stmt, "si", $current_datetime, $signal_id);
        } else {
         	$move_query = "UPDATE lh_signals SET dock_id = ?, updated_date = ? WHERE signal_id = ?";
            $move_stmt = mysqli_prepare($dbc, $move_query);
            mysqli_stmt_bind_param($move_stmt, "isi", $target_dock_id, $current_datetime, $signal_id);
        }
        
        if (mysqli_stmt_execute($move_stmt)) {
            $moved_count++;
            
        	$activity_value = $is_unassign ? 'Unassigned from dock' : $target_dock['dock_name'];
            $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, old_value, new_value, created_date) 
                               VALUES (?, ?, 'dock_changed', '', ?, ?)";
            $activity_stmt = mysqli_prepare($dbc, $activity_query);
            mysqli_stmt_bind_param($activity_stmt, "iiss", $signal_id, $user_id, $activity_value, $current_datetime);
            mysqli_stmt_execute($activity_stmt);
            mysqli_stmt_close($activity_stmt);
        }
        
        mysqli_stmt_close($move_stmt);
    }
    
 	mysqli_commit($dbc);
    
    $message = $is_unassign 
        ? "Successfully unassigned $moved_count signal(s) from docks"
        : "Successfully moved $moved_count signal(s) to " . $target_dock['dock_name'];
    
    if (!empty($permission_denied)) {
        $message .= ". " . count($permission_denied) . " signal(s) could not be moved due to permissions.";
    }
    
    if (!empty($already_in_dock)) {
        $already_message = $is_unassign
            ? " signal(s) were already unassigned."
            : " signal(s) were already in this dock.";
        $message .= ". " . count($already_in_dock) . $already_message;
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'moved_count' => $moved_count,
        'permission_denied_count' => count($permission_denied),
        'already_in_dock_count' => count($already_in_dock),
        'dock_name' => $is_unassign ? 'Unassigned' : $target_dock['dock_name']
    ]);
    
} catch (Exception $e) {
	mysqli_rollback($dbc);
    
    error_log('Bulk move keeper signals error (User ID: ' . $user_id . '): ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move signals. Please try again.'
    ]);
}
?>
