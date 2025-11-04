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
$shift_id = isset($_POST['shift_id']) ? (int)$_POST['shift_id'] : 0;
$tenant_id = isset($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : 0;
$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$job_role_id = isset($_POST['job_role_id']) ? (int)$_POST['job_role_id'] : null;
$assigned_user_id = isset($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null;
$shift_date = isset($_POST['shift_date']) ? $_POST['shift_date'] : null;
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;
$shift_color = isset($_POST['shift_color']) ? $_POST['shift_color'] : null;
$shift_text_color = isset($_POST['shift_text_color']) ? $_POST['shift_text_color'] : null;
$public_notes = isset($_POST['public_notes']) ? $_POST['public_notes'] : null;
$private_notes = isset($_POST['private_notes']) ? $_POST['private_notes'] : null;
$attendance_status = isset($_POST['attendance_status']) ? $_POST['attendance_status'] : null;
$minutes_late = isset($_POST['minutes_late']) ? (int)$_POST['minutes_late'] : null;
$attendance_notes = isset($_POST['attendance_notes']) ? $_POST['attendance_notes'] : null;

if (!$shift_id) {
    echo json_encode(['success' => false, 'message' => 'Shift ID is required']);
    exit();
}

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$current_query = "SELECT * FROM to_shifts WHERE id = ? AND tenant_id = ?";
$stmt = mysqli_prepare($dbc, $current_query);
mysqli_stmt_bind_param($stmt, 'ii', $shift_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Shift not found']);
    exit();
}

$current_shift = mysqli_fetch_assoc($result);

$updates = [];
$params = [];
$types = '';
$old_values = [];
$new_values = [];

if ($department_id !== null && $department_id != $current_shift['department_id']) {
    $updates[] = 'department_id = ?';
    $params[] = $department_id;
    $types .= 'i';
    $old_values['department_id'] = $current_shift['department_id'];
    $new_values['department_id'] = $department_id;
}

if ($job_role_id !== null && $job_role_id != $current_shift['job_role_id']) {
    $updates[] = 'job_role_id = ?';
    $params[] = $job_role_id;
    $types .= 'i';
    $old_values['job_role_id'] = $current_shift['job_role_id'];
    $new_values['job_role_id'] = $job_role_id;
}

if ($assigned_user_id !== null && $assigned_user_id != $current_shift['assigned_user_id']) {
 	$assigned_user_id = $assigned_user_id === 0 ? null : $assigned_user_id;
    $updates[] = 'assigned_user_id = ?';
    $params[] = $assigned_user_id;
    $types .= 'i';
    $old_values['assigned_user_id'] = $current_shift['assigned_user_id'];
    $new_values['assigned_user_id'] = $assigned_user_id;
}

if ($shift_date !== null && $shift_date != $current_shift['shift_date']) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shift_date) || !strtotime($shift_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }
    
    $updates[] = 'shift_date = ?';
    $params[] = $shift_date;
    $types .= 's';
    $old_values['shift_date'] = $current_shift['shift_date'];
    $new_values['shift_date'] = $shift_date;
}

if ($start_time !== null && $start_time != $current_shift['start_time']) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid start time format']);
        exit();
    }
    
    $updates[] = 'start_time = ?';
    $params[] = $start_time;
    $types .= 's';
    $old_values['start_time'] = $current_shift['start_time'];
    $new_values['start_time'] = $start_time;
}

if ($end_time !== null && $end_time != $current_shift['end_time']) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid end time format']);
        exit();
    }
    
    $updates[] = 'end_time = ?';
    $params[] = $end_time;
    $types .= 's';
    $old_values['end_time'] = $current_shift['end_time'];
    $new_values['end_time'] = $end_time;
}

if ($status !== null && $status != $current_shift['status']) {
    if (!in_array($status, ['scheduled', 'open', 'pending', 'cancelled', 'completed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    $updates[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
    $old_values['status'] = $current_shift['status'];
    $new_values['status'] = $status;
}

if ($shift_color !== null && $shift_color != $current_shift['shift_color']) {
    $updates[] = 'shift_color = ?';
    $params[] = $shift_color;
    $types .= 's';
    $old_values['shift_color'] = $current_shift['shift_color'];
    $new_values['shift_color'] = $shift_color;
}

if ($shift_text_color !== null && $shift_text_color != $current_shift['shift_text_color']) {
    $updates[] = 'shift_text_color = ?';
    $params[] = $shift_text_color;
    $types .= 's';
    $old_values['shift_text_color'] = $current_shift['shift_text_color'];
    $new_values['shift_text_color'] = $shift_text_color;
}

if ($public_notes !== null && $public_notes != $current_shift['public_notes']) {
    $updates[] = 'public_notes = ?';
    $params[] = $public_notes;
    $types .= 's';
    $old_values['public_notes'] = $current_shift['public_notes'];
    $new_values['public_notes'] = $public_notes;
}

if ($private_notes !== null && $private_notes != $current_shift['private_notes']) {
    $updates[] = 'private_notes = ?';
    $params[] = $private_notes;
    $types .= 's';
    $old_values['private_notes'] = $current_shift['private_notes'];
    $new_values['private_notes'] = $private_notes;
}

if ($attendance_status !== null && $attendance_status != $current_shift['attendance_status']) {
    $valid_statuses = ['on_time', 'tardy', 'late_arrival', 'very_late_absent', 'no_call_no_show', 'planned_out', 'dropped_shift'];
    if (in_array($attendance_status, $valid_statuses)) {
        $updates[] = 'attendance_status = ?';
        $params[] = $attendance_status;
        $types .= 's';
        $old_values['attendance_status'] = $current_shift['attendance_status'];
        $new_values['attendance_status'] = $attendance_status;
        
        $updates[] = 'attendance_recorded_by = ?';
        $params[] = $user_id;
        $types .= 'i';
        
        $updates[] = 'attendance_recorded_at = NOW()';
    }
}

if ($attendance_notes !== null && $attendance_notes != $current_shift['attendance_notes']) {
    $updates[] = 'attendance_notes = ?';
    $params[] = $attendance_notes;
    $types .= 's';
    $old_values['attendance_notes'] = $current_shift['attendance_notes'];
    $new_values['attendance_notes'] = $attendance_notes;
}

$final_start_time = $start_time ?? $current_shift['start_time'];
$final_end_time = $end_time ?? $current_shift['end_time'];

if (strtotime($final_start_time) >= strtotime($final_end_time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$final_user_id = isset($new_values['assigned_user_id']) ? $new_values['assigned_user_id'] : $current_shift['assigned_user_id'];
$final_date = isset($new_values['shift_date']) ? $new_values['shift_date'] : $current_shift['shift_date'];

if ($final_user_id && (isset($new_values['assigned_user_id']) || isset($new_values['start_time']) || isset($new_values['end_time']) || isset($new_values['shift_date']))) {
    
    $conflict_query = "
        SELECT s.id, s.start_time, s.end_time, s.assigned_user_id, s.status
        FROM to_shifts s 
        JOIN to_departments d ON s.department_id = d.id
        WHERE s.assigned_user_id = ? 
        AND s.shift_date = ? 
        AND s.id != ?
        AND d.tenant_id = ?
        AND s.status IN ('scheduled', 'pending')
        AND NOT (
            TIME(s.end_time) <= TIME(?) OR TIME(s.start_time) >= TIME(?)
        )
    ";
    
	$stmt = mysqli_prepare($dbc, $conflict_query);
    
	    mysqli_stmt_bind_param($stmt, 'isiiss', 
	        $final_user_id,
	        $final_date,
	        $shift_id,
	        $actual_tenant_id,
	        $final_start_time,
	        $final_end_time
	    );
    
	    mysqli_stmt_execute($stmt);
	    $result = mysqli_stmt_get_result($stmt);
    
	    if (mysqli_num_rows($result) > 0) {
	        echo json_encode(['success' => false, 'message' => 'User already has a conflicting shift at this time']);
	        exit();
	    }
}

if (empty($updates)) {
    echo json_encode(['success' => true, 'message' => 'No changes to make']);
    exit();
}

$updates[] = 'updated_at = NOW()';
$update_query = "UPDATE to_shifts SET " . implode(', ', $updates) . " WHERE id = ?";
$params[] = $shift_id;
$types .= 'i';

try {
    mysqli_autocommit($dbc, false);
    
    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error updating shift: ' . mysqli_error($dbc));
    }
    
    if ($attendance_status !== null && $final_user_id) {
      	$check_attendance_query = "SELECT id FROM to_shift_attendance WHERE shift_id = ?";
        $stmt = mysqli_prepare($dbc, $check_attendance_query);
        mysqli_stmt_bind_param($stmt, 'i', $shift_id);
        mysqli_stmt_execute($stmt);
        $attendance_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($attendance_result) > 0) {
         	$update_attendance_query = "
                UPDATE to_shift_attendance 
                SET attendance_status = ?, 
                    minutes_late = ?, 
                    attendance_notes = ?, 
                    recorded_by = ?, 
                    updated_at = NOW()
                WHERE shift_id = ?
            ";
            
            $stmt = mysqli_prepare($dbc, $update_attendance_query);
            mysqli_stmt_bind_param($stmt, 'sisii', 
                $attendance_status, $minutes_late, $attendance_notes, $user_id, $shift_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error updating attendance record: ' . mysqli_error($dbc));
            }
        } else {
         	$insert_attendance_query = "
                INSERT INTO to_shift_attendance 
                (shift_id, tenant_id, attendance_status, minutes_late, attendance_notes, recorded_by, recorded_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = mysqli_prepare($dbc, $insert_attendance_query);
            mysqli_stmt_bind_param($stmt, 'iisisi', 
                $shift_id, $tenant_id, $attendance_status, $minutes_late, $attendance_notes, $user_id
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error creating attendance record: ' . mysqli_error($dbc));
            }
        }
        
      	$new_values['minutes_late'] = $minutes_late;
        $new_values['attendance_recorded_by'] = $user_id;
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    $audit_query = "
        INSERT INTO to_audit_log (
            tenant_id, user_id, action, table_name, record_id, 
            old_values, new_values, ip_address, user_agent, created_at
        ) VALUES (?, ?, 'UPDATE', 'to_shifts', ?, ?, ?, ?, ?, NOW())
    ";
    
    $old_values_json = json_encode($old_values);
    $new_values_json = json_encode($new_values);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    
    mysqli_stmt_bind_param($stmt, 'iiissss', 
        $tenant_id,
        $user_id, 
        $shift_id,
        $old_values_json,
        $new_values_json,
        $ip_address,
        $user_agent
    );
    
    mysqli_stmt_execute($stmt);
    
    $response_message = 'Shift updated successfully';
    if ($attendance_status !== null) {
        $response_message .= ' with attendance tracking';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $response_message,
        'attendance_tracked' => !empty($attendance_status),
        'changes' => $new_values
    ]);

} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error updating shift: ' . $e->getMessage()
    ]);
}
?>