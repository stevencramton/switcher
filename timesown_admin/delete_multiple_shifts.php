<?php
session_start();
include_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];

if (!isset($_POST['shift_ids']) || !isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$shift_ids = $_POST['shift_ids'];
$tenant_id = (int)$_POST['tenant_id'];

if (!is_array($shift_ids) || empty($shift_ids)) {
    echo json_encode(['success' => false, 'message' => 'No shifts selected for deletion']);
    exit();
}

// Sanitize shift IDs
$shift_ids = array_map('intval', $shift_ids);
$shift_ids = array_filter($shift_ids, function($id) { return $id > 0; });

if (empty($shift_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid shift IDs provided']);
    exit();
}

try {
	mysqli_autocommit($dbc, false);
    
    $deleted_count = 0;
    $errors = [];
	$placeholders = implode(',', array_fill(0, count($shift_ids), '?'));
    
	$verify_query = "
        SELECT s.id, s.shift_date, s.start_time, s.end_time, s.assigned_user_id,
               d.name as department_name, r.name as role_name,
               CONCAT(COALESCE(u.display_name, CONCAT(u.first_name, ' ', u.last_name)), '') as user_name
        FROM to_shifts s
        JOIN to_departments d ON s.department_id = d.id
        JOIN to_job_roles r ON s.job_role_id = r.id
        LEFT JOIN users u ON s.assigned_user_id = u.id
        WHERE s.id IN ($placeholders) 
        AND s.tenant_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $verify_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare verification query: ' . mysqli_error($dbc));
    }
    
	$types = str_repeat('i', count($shift_ids)) . 'i';
    $params = array_merge($shift_ids, [$tenant_id]);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to verify shifts: ' . mysqli_stmt_error($stmt));
    }
    
    $verify_result = mysqli_stmt_get_result($stmt);
    $verified_shifts = mysqli_fetch_all($verify_result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
	$verified_ids = array_column($verified_shifts, 'id');
    $not_found = array_diff($shift_ids, $verified_ids);
    
    if (!empty($not_found)) {
        throw new Exception('Some shifts not found or do not belong to this tenant');
    }
    
	$delete_query = "DELETE FROM to_shifts WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $delete_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare delete query: ' . mysqli_error($dbc));
    }
    
    $types = str_repeat('i', count($shift_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$shift_ids);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete shifts: ' . mysqli_stmt_error($stmt));
    }
    
    $deleted_count = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
	foreach ($verified_shifts as $shift) {
        $activity_description = sprintf(
            "Bulk deleted shift: %s - %s on %s for %s (%s)",
            $shift['start_time'],
            $shift['end_time'],
            $shift['shift_date'],
            $shift['user_name'] ?: 'Open Shift',
            $shift['department_name'] . ' - ' . $shift['role_name']
        );
        
        $log_query = "
            INSERT INTO to_audit_log 
            (user_id, tenant_id, action, table_name, record_id, old_values, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $log_stmt = mysqli_prepare($dbc, $log_query);
        if ($log_stmt) {
            $action = 'bulk_shift_delete';
            $table_name = 'to_shifts';
            $log_data = json_encode($shift);
            mysqli_stmt_bind_param($log_stmt, 'iissis', $user_id, $tenant_id, $action, $table_name, $shift['id'], $log_data);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully deleted $deleted_count shifts",
        'deleted_count' => $deleted_count,
        'requested_count' => count($shift_ids)
    ]);
    
} catch (Exception $e) {
	mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    error_log("Bulk shift deletion error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete shifts: ' . $e->getMessage(),
        'deleted_count' => $deleted_count
    ]);
}
?>