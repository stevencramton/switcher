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

// Only admins can assign signals
if (!$is_admin) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Only administrators can assign signals']));
}

// Get signal IDs array and target keeper
$signal_ids = isset($_POST['signal_ids']) ? $_POST['signal_ids'] : [];
$keeper_id = isset($_POST['keeper_id']) ? $_POST['keeper_id'] : null;

// Validate that we have signal IDs
if (empty($signal_ids) || !is_array($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'No signals selected']));
}

// Allow null keeper_id for unassigning (set to NULL)
// If keeper_id is empty string, convert to null
if ($keeper_id === '' || $keeper_id === 'null' || $keeper_id === 0) {
    $keeper_id = null;
}

// If keeper_id is provided, verify the keeper exists and is an admin
if ($keeper_id !== null) {
    $keeper_id = (int)$keeper_id;
    
    $keeper_check_query = "SELECT id, first_name, last_name FROM users WHERE id = ? AND role >= 1 AND account_delete = 0";
    $keeper_check_stmt = mysqli_prepare($dbc, $keeper_check_query);
    mysqli_stmt_bind_param($keeper_check_stmt, 'i', $keeper_id);
    mysqli_stmt_execute($keeper_check_stmt);
    $keeper_check_result = mysqli_stmt_get_result($keeper_check_stmt);
    
    if (mysqli_num_rows($keeper_check_result) == 0) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Lighthouse keeper not found']));
    }
    
    $keeper_data = mysqli_fetch_assoc($keeper_check_result);
    $keeper_name = $keeper_data['first_name'] . ' ' . $keeper_data['last_name'];
    mysqli_stmt_close($keeper_check_stmt);
} else {
    $keeper_name = 'Unassigned';
}

// Sanitize signal IDs
$signal_ids = array_map('intval', $signal_ids);
$signal_ids = array_filter($signal_ids, function($id) { return $id > 0; });

if (empty($signal_ids)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid signal IDs']));
}

// Verify signals exist and are not deleted
$placeholders = implode(',', array_fill(0, count($signal_ids), '?'));
$check_query = "SELECT signal_id, keeper_assigned FROM lh_signals WHERE signal_id IN ($placeholders) AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);

// Bind parameters dynamically
$types = str_repeat('i', count($signal_ids));
mysqli_stmt_bind_param($check_stmt, $types, ...$signal_ids);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$signals_to_assign = [];
$already_assigned = [];

while ($signal = mysqli_fetch_assoc($check_result)) {
    // Check if signal is already assigned to this keeper
    if ($keeper_id === null && $signal['keeper_assigned'] === null) {
        $already_assigned[] = $signal['signal_id'];
        continue;
    } elseif ($keeper_id !== null && $signal['keeper_assigned'] == $keeper_id) {
        $already_assigned[] = $signal['signal_id'];
        continue;
    }
    
    $signals_to_assign[] = $signal['signal_id'];
}

mysqli_stmt_close($check_stmt);

// If no signals need assignment, return message
if (empty($signals_to_assign)) {
    if (!empty($already_assigned)) {
        http_response_code(400);
        die(json_encode([
            'success' => false, 
            'message' => 'All selected signals are already assigned to this keeper'
        ]));
    } else {
        http_response_code(404);
        die(json_encode([
            'success' => false, 
            'message' => 'No valid signals found to assign'
        ]));
    }
}

// *** CHANGE: Generate timestamp using PHP instead of MySQL NOW() ***
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($dbc);

try {
    $assigned_count = 0;
    
    // Assign each signal to the keeper (or unassign if keeper_id is null)
    foreach ($signals_to_assign as $signal_id) {
        if ($keeper_id === null) {
            $assign_query = "UPDATE lh_signals SET keeper_assigned = NULL, updated_date = ? WHERE signal_id = ?";
            $assign_stmt = mysqli_prepare($dbc, $assign_query);
            mysqli_stmt_bind_param($assign_stmt, "si", $current_datetime, $signal_id);
        } else {
            $assign_query = "UPDATE lh_signals SET keeper_assigned = ?, updated_date = ? WHERE signal_id = ?";
            $assign_stmt = mysqli_prepare($dbc, $assign_query);
            mysqli_stmt_bind_param($assign_stmt, "isi", $keeper_id, $current_datetime, $signal_id);
        }
        
        if (mysqli_stmt_execute($assign_stmt)) {
            $assigned_count++;
            
            // Log the assignment in activity with explicit timestamp
            $activity_type = $keeper_id === null ? 'unassigned' : 'assigned';
            $activity_value = $keeper_id === null ? 'Unassigned' : "Assigned to $keeper_name";
            
            $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                               VALUES (?, ?, ?, ?, ?)";
            $activity_stmt = mysqli_prepare($dbc, $activity_query);
            mysqli_stmt_bind_param($activity_stmt, "iisss", $signal_id, $user_id, $activity_type, $activity_value, $current_datetime);
            mysqli_stmt_execute($activity_stmt);
            mysqli_stmt_close($activity_stmt);
        }
        
        mysqli_stmt_close($assign_stmt);
    }
    
    // Commit transaction
    mysqli_commit($dbc);
    
    if ($keeper_id === null) {
        $message = "Successfully unassigned $assigned_count signal(s)";
    } else {
        $message = "Successfully assigned $assigned_count signal(s) to $keeper_name";
    }
    
    if (!empty($already_assigned)) {
        $message .= ". " . count($already_assigned) . " signal(s) were already assigned to this keeper.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'assigned_count' => $assigned_count,
        'already_assigned_count' => count($already_assigned),
        'keeper_name' => $keeper_name
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($dbc);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to assign signals: ' . $e->getMessage()
    ]);
}
?>