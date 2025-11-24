<?php
/**
 * Captain's Log - Event Logger Helper
 * 
 * Include this file in your PHP scripts to easily log events to the Captain's Log.
 * 
 * Usage:
 *   include 'captains_log_helper.php';
 *   
 *   // Log a signal creation
 *   captainsLog($dbc, 'signal_created', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, null, $title, ['dock_id' => $dock_id]);
 *   
 *   // Log a status change
 *   captainsLog($dbc, 'status_changed', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, $old_status, $new_status, ['sea_state_id' => $new_state_id]);
 *   
 *   // Log a keeper assignment
 *   captainsLog($dbc, 'keeper_assigned', 'signal', 'signal', $signal_id, $signal_number, $user_id, $keeper_id, null, 'Assigned keeper', []);
 * 
 * Place in: ajax/lh_captains_log/captains_log_helper.php
 */

/**
 * Log an event to the Captain's Log
 *
 * @param mysqli $dbc Database connection
 * @param string $event_type Type of event (e.g., 'signal_created', 'status_changed')
 * @param string $event_category Category (e.g., 'signal', 'configuration', 'authentication', 'system')
 * @param string|null $entity_type Type of entity (e.g., 'signal', 'dock', 'sea_state')
 * @param int|null $entity_id ID of the affected entity
 * @param string|null $entity_reference Human-readable reference (e.g., signal number, dock name)
 * @param int $user_id User who performed the action
 * @param int|null $target_user_id User affected by the action (for assignments, etc.)
 * @param string|null $old_value Previous value
 * @param string|null $new_value New value
 * @param array $additional_details Additional details to store as JSON
 * @return bool True on success, false on failure
 */
function captainsLog($dbc, $event_type, $event_category, $entity_type = null, $entity_id = null, $entity_reference = null, $user_id = null, $target_user_id = null, $old_value = null, $new_value = null, $additional_details = []) {
    
    // Get user ID from session if not provided
    if ($user_id === null && isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
    }
    
    // Must have a user ID
    if (!$user_id) {
        error_log("Captain's Log: Cannot log event without user ID");
        return false;
    }
    
    // Get IP address and user agent
    $ip_address = getClientIP();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
    
    // Convert additional details to JSON
    $details = !empty($additional_details) ? json_encode($additional_details) : null;
    
    // Prepare the insert query
    $query = "INSERT INTO lh_captains_log 
              (event_type, event_category, entity_type, entity_id, entity_reference, user_id, target_user_id, old_value, new_value, details, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    
    if (!$stmt) {
        error_log("Captain's Log: Failed to prepare statement - " . mysqli_error($dbc));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'sssississsss',
        $event_type,
        $event_category,
        $entity_type,
        $entity_id,
        $entity_reference,
        $user_id,
        $target_user_id,
        $old_value,
        $new_value,
        $details,
        $ip_address,
        $user_agent
    );
    
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        error_log("Captain's Log: Failed to log event - " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    return $success;
}

/**
 * Get the client's real IP address
 *
 * @return string|null The client's IP address
 */
function getClientIP() {
    $ip = null;
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, get the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP address
    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return null;
}

/**
 * Quick logging functions for common events
 */

/**
 * Log signal creation
 */
function logSignalCreated($dbc, $signal_id, $signal_number, $title, $user_id, $details = []) {
    return captainsLog($dbc, 'signal_created', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, null, $title, $details);
}

/**
 * Log signal status change
 */
function logStatusChanged($dbc, $signal_id, $signal_number, $user_id, $old_status, $new_status, $details = []) {
    return captainsLog($dbc, 'status_changed', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, $old_status, $new_status, $details);
}

/**
 * Log priority change
 */
function logPriorityChanged($dbc, $signal_id, $signal_number, $user_id, $old_priority, $new_priority, $details = []) {
    return captainsLog($dbc, 'priority_changed', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, $old_priority, $new_priority, $details);
}

/**
 * Log dock change
 */
function logDockChanged($dbc, $signal_id, $signal_number, $user_id, $old_dock, $new_dock, $details = []) {
    return captainsLog($dbc, 'dock_changed', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, $old_dock, $new_dock, $details);
}

/**
 * Log keeper assignment
 */
function logKeeperAssigned($dbc, $signal_id, $signal_number, $user_id, $keeper_id, $keeper_name, $details = []) {
    return captainsLog($dbc, 'keeper_assigned', 'signal', 'signal', $signal_id, $signal_number, $user_id, $keeper_id, null, $keeper_name, $details);
}

/**
 * Log keeper unassignment
 */
function logKeeperUnassigned($dbc, $signal_id, $signal_number, $user_id, $keeper_id, $keeper_name, $details = []) {
    return captainsLog($dbc, 'keeper_unassigned', 'signal', 'signal', $signal_id, $signal_number, $user_id, $keeper_id, $keeper_name, null, $details);
}

/**
 * Log signal update added
 */
function logUpdateAdded($dbc, $signal_id, $signal_number, $user_id, $update_id, $details = []) {
    $details['update_id'] = $update_id;
    return captainsLog($dbc, 'update_added', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, null, 'Added signal update', $details);
}

/**
 * Log comment added
 */
function logCommentAdded($dbc, $signal_id, $signal_number, $user_id, $comment_id, $details = []) {
    $details['comment_id'] = $comment_id;
    return captainsLog($dbc, 'comment_added', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, null, 'Added comment', $details);
}

/**
 * Log attachment uploaded
 */
function logAttachmentUploaded($dbc, $signal_id, $signal_number, $user_id, $attachment_id, $filename, $details = []) {
    $details['attachment_id'] = $attachment_id;
    return captainsLog($dbc, 'attachment_uploaded', 'signal', 'signal', $signal_id, $signal_number, $user_id, null, null, $filename, $details);
}

/**
 * Log bulk operation
 */
function logBulkOperation($dbc, $event_type, $user_id, $signal_ids, $old_value, $new_value, $details = []) {
    $details['signal_ids'] = $signal_ids;
    $details['count'] = count($signal_ids);
    return captainsLog($dbc, $event_type, 'signal', 'signal', null, null, $user_id, null, $old_value, $new_value, $details);
}

/**
 * Log configuration change
 */
function logConfigChange($dbc, $event_type, $entity_type, $entity_id, $entity_name, $user_id, $old_value, $new_value, $details = []) {
    return captainsLog($dbc, $event_type, 'configuration', $entity_type, $entity_id, $entity_name, $user_id, null, $old_value, $new_value, $details);
}

/**
 * Log user login
 */
function logUserLogin($dbc, $user_id, $username, $success = true, $details = []) {
    $event_type = $success ? 'user_login' : 'user_login_failed';
    $new_value = $success ? 'Successful login' : 'Failed login attempt';
    return captainsLog($dbc, $event_type, 'authentication', 'user', $user_id, $username, $user_id, null, null, $new_value, $details);
}

/**
 * Log user logout
 */
function logUserLogout($dbc, $user_id, $username) {
    return captainsLog($dbc, 'user_logout', 'authentication', 'user', $user_id, $username, $user_id, null, null, 'User logged out', []);
}

/**
 * Log system error
 */
function logSystemError($dbc, $error_message, $user_id = null, $details = []) {
    return captainsLog($dbc, 'system_error', 'system', null, null, null, $user_id, null, null, $error_message, $details);
}
?>