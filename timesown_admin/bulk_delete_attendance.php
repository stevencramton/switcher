<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
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
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No input data received']);
    exit();
}

$shift_ids = isset($input['shift_ids']) ? $input['shift_ids'] : [];
$tenant_id = isset($input['tenant_id']) ? (int)$input['tenant_id'] : 0;
$employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;

if (empty($shift_ids) || !is_array($shift_ids)) {
    echo json_encode(['success' => false, 'message' => 'No shift IDs provided']);
    exit();
}

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

$shift_ids = array_map('intval', $shift_ids);
$shift_ids = array_filter($shift_ids, function($id) { return $id > 0; });

if (empty($shift_ids)) {
    echo json_encode(['success' => false, 'message' => 'No valid shift IDs provided']);
    exit();
}

try {
    mysqli_autocommit($dbc, false);
    
    $placeholders = str_repeat('?,', count($shift_ids) - 1) . '?';
    $verify_query = "
        SELECT s.id, s.shift_date, s.attendance_status, s.attendance_notes
        FROM to_shifts s 
        WHERE s.id IN ($placeholders) 
        AND s.assigned_user_id = ? 
        AND s.tenant_id = ?
        AND (s.attendance_status IS NOT NULL OR EXISTS(
            SELECT 1 FROM to_shift_attendance sa WHERE sa.shift_id = s.id
        ))
    ";
    
    $verify_params = $shift_ids;
    $verify_params[] = $employee_id;
    $verify_params[] = $tenant_id;
    
    $types = str_repeat('i', count($shift_ids)) . 'ii';
    
    $stmt = mysqli_prepare($dbc, $verify_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare verify query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$verify_params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute verify query: ' . mysqli_stmt_error($stmt));
    }
    
    $verify_result = mysqli_stmt_get_result($stmt);
    
    $valid_shifts = [];
    $audit_data = [];
    
    while ($row = mysqli_fetch_assoc($verify_result)) {
        $valid_shifts[] = $row['id'];
        $audit_data[$row['id']] = [
            'shift_date' => $row['shift_date'],
            'attendance_status' => $row['attendance_status'],
            'attendance_notes' => $row['attendance_notes']
        ];
    }
    
    if (empty($valid_shifts)) {
        echo json_encode(['success' => false, 'message' => 'No valid attendance records found to delete']);
        exit();
    }
    
    $deleted_count = 0;
	$delete_placeholders = str_repeat('?,', count($valid_shifts) - 1) . '?';
    $delete_attendance_query = "DELETE FROM to_shift_attendance WHERE shift_id IN ($delete_placeholders)";
    $stmt = mysqli_prepare($dbc, $delete_attendance_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare delete query: ' . mysqli_error($dbc));
    }
    
    $delete_types = str_repeat('i', count($valid_shifts));
    mysqli_stmt_bind_param($stmt, $delete_types, ...$valid_shifts);
    
    if (mysqli_stmt_execute($stmt)) {
        $deleted_count += mysqli_affected_rows($dbc);
    } else {
        throw new Exception('Failed to delete attendance records: ' . mysqli_stmt_error($stmt));
    }
    
	$clear_shifts_query = "
        UPDATE to_shifts 
        SET attendance_status = NULL, 
            attendance_notes = NULL, 
            attendance_recorded_by = NULL, 
            attendance_recorded_at = NULL,
            updated_at = NOW()
        WHERE id IN ($delete_placeholders)
    ";
    
    $stmt = mysqli_prepare($dbc, $clear_shifts_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare update query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, $delete_types, ...$valid_shifts);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error clearing shift attendance data: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
  	foreach ($valid_shifts as $shift_id) {
        if (isset($audit_data[$shift_id])) {
            $audit_values = json_encode([
                'action' => 'bulk_delete_attendance',
                'shift_id' => $shift_id,
                'deleted_shift_date' => $audit_data[$shift_id]['shift_date'],
                'deleted_attendance_status' => $audit_data[$shift_id]['attendance_status'],
                'deleted_attendance_notes' => $audit_data[$shift_id]['attendance_notes'],
                'employee_id' => $employee_id,
                'bulk_operation_count' => count($valid_shifts)
            ]);
            
            $audit_query = "
                INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $action = 'BULK_DELETE';
            $table_name = 'to_shift_attendance';
            
            $stmt = mysqli_prepare($dbc, $audit_query);
            if ($stmt) {
              	mysqli_stmt_bind_param($stmt, 'iississs', 
                    $tenant_id,
                    $user_id,
                    $action,
                    $table_name,
                    $shift_id,
                    $audit_values,
                    $ip_address,
                    $user_agent
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    error_log('Audit log insert failed: ' . mysqli_stmt_error($stmt));
                }
            } else {
                error_log('Failed to prepare audit log query: ' . mysqli_error($dbc));
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance records deleted successfully',
        'deleted_count' => count($valid_shifts),
        'requested_count' => count($shift_ids),
        'valid_shifts' => $valid_shifts
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
	echo json_encode([
        'success' => false,
        'message' => 'Error deleting attendance records: ' . $e->getMessage()
    ]);
}
?>