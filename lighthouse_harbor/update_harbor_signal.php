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

if (empty($_POST['signal_id']) || empty($_POST['title']) || empty($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$signal_id = (int)$_POST['signal_id'];
$title = trim($_POST['title']);
$message = trim($_POST['message']);
$sea_state_id = (int)$_POST['sea_state_id'];
$priority_id = (int)$_POST['priority_id'];
$resolution_notes = !empty($_POST['resolution_notes']) ? trim($_POST['resolution_notes']) : NULL;

$ownership_query = "SELECT sent_by, sea_state_id, priority_id FROM lh_signals WHERE signal_id = ? AND is_deleted = 0";
$ownership_stmt = mysqli_prepare($dbc, $ownership_query);
mysqli_stmt_bind_param($ownership_stmt, 'i', $signal_id);
mysqli_stmt_execute($ownership_stmt);
$ownership_result = mysqli_stmt_get_result($ownership_stmt);

if (mysqli_num_rows($ownership_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Signal not found']);
    exit();
}

$signal_data = mysqli_fetch_assoc($ownership_result);

// Check if user owns this signal or is an admin
if ($signal_data['sent_by'] != $user_id && !$is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only update your own signals']);
    exit();
}

$old_values = $signal_data;

// Generate timestamp using PHP
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($dbc);

try {
    // Determine if signal is being resolved
    $resolved_date = null;
    if ($sea_state_id == 4 && $old_values['sea_state_id'] != 4) {
        // Beacon changed to "Resolved"
        $resolved_date = $current_datetime;
    } elseif ($sea_state_id != 4 && $old_values['sea_state_id'] == 4) {
        // Beacon changed from "Resolved" to something else
        $resolved_date = null;
    }
    
    // Build update query - users can update: title, message, sea_state_id, priority_id, resolution_notes
    // Users can only update their own signals, admins can update any signal
    $query = "UPDATE lh_signals SET 
              title = ?,
              message = ?,
              sea_state_id = ?,
              priority_id = ?,
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
        mysqli_stmt_bind_param($stmt, 'ssiisssi', 
            $title, $message, $sea_state_id, $priority_id, 
            $resolution_notes, $current_datetime, $resolved_date, $signal_id
        );
    } else {
        mysqli_stmt_bind_param($stmt, 'ssiissi', 
            $title, $message, $sea_state_id, $priority_id, 
            $resolution_notes, $current_datetime, $signal_id
        );
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update signal');
    }
    
    // Log activity for changes
    $activities = [];
    
    if ($old_values['sea_state_id'] != $sea_state_id) {
        $activity_value = $is_admin ? 'Beacon updated by keeper' : 'Beacon updated by sender';
        $activities[] = ['type' => 'status_changed', 'value' => $activity_value];
    }
    
    if ($old_values['priority_id'] != $priority_id) {
        $activity_value = $is_admin ? 'Priority updated by keeper' : 'Priority updated by sender';
        $activities[] = ['type' => 'priority_changed', 'value' => $activity_value];
    }
    
    if ($resolved_date) {
        $activity_value = $is_admin ? 'Signal marked as resolved by keeper' : 'Signal marked as resolved by sender';
        $activities[] = ['type' => 'resolved', 'value' => $activity_value];
    }
    
    // Insert activity logs with explicit timestamp
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
    
    // Commit transaction
    mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Signal updated successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update signal: ' . $e->getMessage()
    ]);
}
?>