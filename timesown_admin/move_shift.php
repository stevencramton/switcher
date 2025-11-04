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

$shift_id = (int)$_POST['shift_id'];
$new_user_id = !empty($_POST['new_user_id']) ? (int)$_POST['new_user_id'] : null;
$new_department_id = !empty($_POST['new_department_id']) ? (int)$_POST['new_department_id'] : null;
$new_role_id = !empty($_POST['new_role_id']) ? (int)$_POST['new_role_id'] : null;
$new_start_time = $_POST['new_start_time'] ?? null;
$new_end_time = $_POST['new_end_time'] ?? null;

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

$updates = [];
$params = [];
$types = '';
$old_values = [];
$new_values = [];

if ($new_user_id !== null && $new_user_id != $current_shift['assigned_user_id']) {
    if ($new_user_id > 0) {
        $user_check = "
            SELECT u.id 
            FROM users u 
            JOIN to_user_tenants ut ON u.id = ut.user_id 
            WHERE u.id = ? AND ut.tenant_id = ? AND ut.active = 1 AND u.account_delete = 0
        ";
        $stmt = mysqli_prepare($dbc, $user_check);
        mysqli_stmt_bind_param($stmt, 'ii', $new_user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user assignment']);
            exit();
        }
    }
    
    $updates[] = 'assigned_user_id = ?';
    $params[] = $new_user_id;
    $types .= 'i';
    $old_values['assigned_user_id'] = $current_shift['assigned_user_id'];
    $new_values['assigned_user_id'] = $new_user_id;
}

if ($new_department_id !== null && $new_department_id != $current_shift['department_id']) {
    $dept_check = "SELECT id FROM to_departments WHERE id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $dept_check);
    mysqli_stmt_bind_param($stmt, 'ii', $new_department_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department']);
        exit();
    }
    
    $updates[] = 'department_id = ?';
    $params[] = $new_department_id;
    $types .= 'i';
    $old_values['department_id'] = $current_shift['department_id'];
    $new_values['department_id'] = $new_department_id;
}

if ($new_role_id !== null && $new_role_id != $current_shift['job_role_id']) {
    $dept_id_for_validation = $new_department_id ?? $current_shift['department_id'];
    $role_check = "SELECT id FROM to_job_roles WHERE id = ? AND department_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $role_check);
    mysqli_stmt_bind_param($stmt, 'ii', $new_role_id, $dept_id_for_validation);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid role for department',
            'debug' => [
                'new_role_id' => $new_role_id,
                'dept_id_for_validation' => $dept_id_for_validation,
                'new_department_id' => $new_department_id,
                'current_department_id' => $current_shift['department_id']
            ]
        ]);
        exit();
    }
    
    $updates[] = 'job_role_id = ?';
    $params[] = $new_role_id;
    $types .= 'i';
    $old_values['job_role_id'] = $current_shift['job_role_id'];
    $new_values['job_role_id'] = $new_role_id;
}

if ($new_start_time !== null && $new_start_time != $current_shift['start_time']) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $new_start_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid start time format']);
        exit();
    }
    
    $updates[] = 'start_time = ?';
    $params[] = $new_start_time;
    $types .= 's';
    $old_values['start_time'] = $current_shift['start_time'];
    $new_values['start_time'] = $new_start_time;
}

if ($new_end_time !== null && $new_end_time != $current_shift['end_time']) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $new_end_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid end time format']);
        exit();
    }
    
    $updates[] = 'end_time = ?';
    $params[] = $new_end_time;
    $types .= 's';
    $old_values['end_time'] = $current_shift['end_time'];
    $new_values['end_time'] = $new_end_time;
}

$final_start_time = $new_start_time ?? $current_shift['start_time'];
$final_end_time = $new_end_time ?? $current_shift['end_time'];

if (strtotime($final_start_time) >= strtotime($final_end_time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$final_user_id = $new_user_id ?? $current_shift['assigned_user_id'];

if ($final_user_id && (isset($new_values['assigned_user_id']) || isset($new_values['start_time']) || isset($new_values['end_time']))) {
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
        $final_user_id, 
        $current_shift['shift_date'], 
        $shift_id,
        $tenant_id,
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
    $stmt->bind_param($types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error updating shift: ' . mysqli_error($dbc));
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift moved successfully',
        'changes' => $new_values,
        'debug' => [
            'shift_id' => $shift_id,
            'updates_made' => count($updates) - 1,
            'final_user_id' => $final_user_id,
            'tenant_id' => $tenant_id
        ]
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