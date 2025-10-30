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

$user_switch_id = $_SESSION['switch_id'];
$role_query = "SELECT id, role FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $role_query);
mysqli_stmt_bind_param($stmt, 'i', $user_switch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_id = $user_data['id'];

if ($user_data['role'] < 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_POST['shift_id']) || empty($_POST['shift_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing shift ID']);
    exit();
}

if (!isset($_POST['new_start_time']) || !isset($_POST['new_end_time'])) {
    echo json_encode(['success' => false, 'message' => 'Missing time parameters']);
    exit();
}

$shift_id = (int)$_POST['shift_id'];
$new_start_time = $_POST['new_start_time'];
$new_end_time = $_POST['new_end_time'];

if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $new_start_time) || 
    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $new_end_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit();
}

if (strtotime($new_start_time) >= strtotime($new_end_time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$start_time_obj = new DateTime($new_start_time);
$end_time_obj = new DateTime($new_end_time);
$duration_minutes = $end_time_obj->diff($start_time_obj)->h * 60 + $end_time_obj->diff($start_time_obj)->i;

if ($duration_minutes < 30) {
    echo json_encode(['success' => false, 'message' => 'Shift must be at least 30 minutes long']);
    exit();
}

if ($duration_minutes > 720) {
    echo json_encode(['success' => false, 'message' => 'Shift cannot be longer than 12 hours']);
    exit();
}

$shift_query = "
    SELECT s.*, d.tenant_id 
    FROM to_shifts s 
    JOIN to_departments d ON s.department_id = d.id 
    WHERE s.id = ?
";
$stmt = mysqli_prepare($dbc, $shift_query);
mysqli_stmt_bind_param($stmt, 'i', $shift_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_shift = mysqli_fetch_assoc($result);

if (!$current_shift) {
    echo json_encode(['success' => false, 'message' => 'Shift not found']);
    exit();
}

$tenant_id = $current_shift['tenant_id'];

if ($user_data['role'] < 3) {
    $tenant_check = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_check);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
        exit();
    }
}

if ($current_shift['assigned_user_id']) {
    $conflict_query = "
        SELECT s.id 
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
        $current_shift['assigned_user_id'], 
        $current_shift['shift_date'], 
        $shift_id,
        $tenant_id,
        $new_start_time,
        $new_end_time
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Time change would create a conflict with another shift']);
        exit();
    }
}

try {
  	mysqli_autocommit($dbc, false);
    
 	$update_query = "UPDATE to_shifts SET start_time = ?, end_time = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, 'ssi', $new_start_time, $new_end_time, $shift_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error updating shift times: ' . mysqli_error($dbc));
    }
    
	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift times updated successfully',
        'new_start_time' => $new_start_time,
        'new_end_time' => $new_end_time,
        'duration_hours' => round($duration_minutes / 60, 2)
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>