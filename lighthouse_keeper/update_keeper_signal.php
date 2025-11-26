<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_keeper')){
	http_response_code(403);
	die(json_encode(['success' => false, 'message' => 'Access denied']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['id'];

if (empty($_POST['signal_id']) || empty($_POST['title']) || empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$signal_id = (int)$_POST['signal_id'];
$title = trim($_POST['title']);
$message = trim($_POST['message']);
$signal_type = !empty($_POST['signal_type']) ? trim($_POST['signal_type']) : 'other';
$sea_state_id = (int)$_POST['sea_state_id'];
$priority_id = (int)$_POST['priority_id'];
$dock_id = !empty($_POST['dock_id']) ? (int)$_POST['dock_id'] : NULL;
$service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : NULL;
$keeper_assigned = !empty($_POST['keeper_assigned']) ? (int)$_POST['keeper_assigned'] : NULL;
$resolution_notes = !empty($_POST['resolution_notes']) ? trim($_POST['resolution_notes']) : NULL;

// Validate signal_type against allowed values
$allowed_types = ['general_request', 'project', 'other'];
if (!in_array($signal_type, $allowed_types)) {
    $signal_type = 'other';
}

$old_query = "SELECT sea_state_id, priority_id, dock_id, service_id, keeper_assigned, signal_type FROM lh_signals WHERE signal_id = ? AND is_deleted = 0";
$old_stmt = mysqli_prepare($dbc, $old_query);
mysqli_stmt_bind_param($old_stmt, 'i', $signal_id);
mysqli_stmt_execute($old_stmt);
$old_result = mysqli_stmt_get_result($old_stmt);

if (mysqli_num_rows($old_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Signal not found']);
    exit();
}

$old_values = mysqli_fetch_assoc($old_result);
$current_datetime = date('Y-m-d H:i:s');

mysqli_begin_transaction($dbc);

try {
  	$resolved_date = null;
    if ($sea_state_id == 4 && $old_values['sea_state_id'] != 4) {
       	$resolved_date = $current_datetime;
    } elseif ($sea_state_id != 4 && $old_values['sea_state_id'] == 4) {
      	$resolved_date = null;
    }
    
 	$query = "UPDATE lh_signals SET 
              title = ?,
              message = ?,
              signal_type = ?,
              sea_state_id = ?,
              priority_id = ?,
              dock_id = ?,
              service_id = ?,
              keeper_assigned = ?,
              resolution_notes = ?,
              updated_date = ?";
    
    if ($resolved_date !== null) {
        $query .= ", resolved_date = ?";
    } elseif ($sea_state_id != 4) {
        $query .= ", resolved_date = NULL";
    }
    
    $query .= " WHERE signal_id = ?";
    
    $stmt = mysqli_prepare($dbc, $query);
    
    if ($resolved_date !== null) {
        mysqli_stmt_bind_param($stmt, 'sssiiiiisssi', 
            $title, $message, $signal_type, $sea_state_id, $priority_id, $dock_id, $service_id, 
            $keeper_assigned, $resolution_notes, $current_datetime, $resolved_date, $signal_id
        );
    } else {
        mysqli_stmt_bind_param($stmt, 'sssiiiiissi', 
            $title, $message, $signal_type, $sea_state_id, $priority_id, $dock_id, $service_id, 
            $keeper_assigned, $resolution_notes, $current_datetime, $signal_id
        );
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update signal');
    }
    
 	$activities = [];
    
    if ($old_values['signal_type'] != $signal_type) {
        $activities[] = ['type' => 'type_changed', 'value' => 'Signal type updated'];
    }
    
    if ($old_values['sea_state_id'] != $sea_state_id) {
        $activities[] = ['type' => 'status_changed', 'value' => 'Beacon updated'];
    }
    
    if ($old_values['priority_id'] != $priority_id) {
        $activities[] = ['type' => 'priority_changed', 'value' => 'Priority updated'];
    }
    
    if ($old_values['dock_id'] != $dock_id) {
        $activities[] = ['type' => 'dock_changed', 'value' => 'Dock updated'];
    }
    
    if ($old_values['service_id'] != $service_id) {
        if ($service_id === NULL) {
            $activities[] = ['type' => 'service_removed', 'value' => 'Service removed'];
        } else {
            $activities[] = ['type' => 'service_changed', 'value' => 'Service updated'];
        }
    }
    
    if ($old_values['keeper_assigned'] != $keeper_assigned) {
        if ($keeper_assigned === NULL) {
            $activities[] = ['type' => 'no keeper', 'value' => 'Signal no keeper'];
        } else {
            $activities[] = ['type' => 'assigned', 'value' => 'Signal assigned'];
        }
    }
    
    if ($resolved_date) {
        $activities[] = ['type' => 'resolved', 'value' => 'Signal marked as resolved'];
    }
    
 	foreach ($activities as $activity) {
        $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                          VALUES (?, ?, ?, ?, ?)";
        $activity_stmt = mysqli_prepare($dbc, $activity_query);
        mysqli_stmt_bind_param($activity_stmt, 'iisss', 
            $signal_id, $user_id, $activity['type'], $activity['value'], $current_datetime
        );
        
        if (!mysqli_stmt_execute($activity_stmt)) {
            throw new Exception('Failed to log activity');
        }
    }
    
	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Signal updated successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    error_log('Update keeper signal error (Signal ID: ' . $signal_id . ', User ID: ' . $user_id . '): ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update signal. Please try again.'
    ]);
}
?>