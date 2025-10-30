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

$required_fields = ['tenant_id', 'department_id', 'job_role_id', 'shift_date', 'start_time', 'end_time'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '' || $_POST[$field] === null) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode([
        'success' => false, 
        'message' => "Missing required fields: " . implode(', ', $missing_fields),
        'received_data' => $_POST
    ]);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$department_id = (int)$_POST['department_id'];
$job_role_id = (int)$_POST['job_role_id'];
$assigned_user_id = !empty($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null;
$shift_date = $_POST['shift_date'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$status = $_POST['status'] ?? 'scheduled';
$public_notes = $_POST['public_notes'] ?? null;
$private_notes = $_POST['private_notes'] ?? null;
$shift_color = isset($_POST['shift_color']) ? trim($_POST['shift_color']) : '#007bff';
$shift_text_color = isset($_POST['shift_text_color']) ? trim($_POST['shift_text_color']) : '#ffffff';

if (!preg_match('/^#[0-9A-F]{6}$/i', $shift_color)) {
    $shift_color = '#007bff';
}

if (!preg_match('/^#[0-9A-F]{6}$/i', $shift_text_color)) {
    $shift_text_color = '#ffffff';
}

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

$validation_query = "
    SELECT jr.id 
    FROM to_job_roles jr 
    JOIN to_departments d ON jr.department_id = d.id 
    WHERE jr.id = ? AND d.id = ? AND d.tenant_id = ? AND jr.active = 1 AND d.active = 1
";
$stmt = mysqli_prepare($dbc, $validation_query);
mysqli_stmt_bind_param($stmt, 'iii', $job_role_id, $department_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department or role']);
    exit();
}

if ($assigned_user_id) {
    $user_check = "
        SELECT u.id 
        FROM users u 
        JOIN to_user_tenants ut ON u.id = ut.user_id 
        WHERE u.id = ? AND ut.tenant_id = ? AND ut.active = 1 AND u.account_delete = 0
    ";
    $stmt = mysqli_prepare($dbc, $user_check);
    mysqli_stmt_bind_param($stmt, 'ii', $assigned_user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user assignment']);
        exit();
    }
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shift_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected YYYY-MM-DD']);
    exit();
}

if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format. Expected HH:MM']);
    exit();
}

if (strtotime($start_time) >= strtotime($end_time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$valid_statuses = ['scheduled', 'open', 'pending', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses)]);
    exit();
}

if ($assigned_user_id) {
    $conflict_query = "
        SELECT s.id 
        FROM to_shifts s 
        JOIN to_departments d ON s.department_id = d.id
        WHERE s.assigned_user_id = ? 
        AND s.shift_date = ? 
        AND d.tenant_id = ?
        AND s.status IN ('scheduled', 'pending')
        AND NOT (
            TIME(s.end_time) <= TIME(?) OR TIME(s.start_time) >= TIME(?)
        )
    ";
    
    $stmt = mysqli_prepare($dbc, $conflict_query);
    mysqli_stmt_bind_param($stmt, 'isiss', 
        $assigned_user_id, 
        $shift_date, 
        $tenant_id,  // CRITICAL: Add tenant_id filter
        $start_time,
        $end_time
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'User already has a conflicting shift at this time']);
        exit();
    }
}

$insert_query = "
    INSERT INTO to_shifts (
        tenant_id, department_id, job_role_id, assigned_user_id, 
        shift_date, start_time, end_time, status, shift_color, shift_text_color,
        public_notes, private_notes, created_by, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = mysqli_prepare($dbc, $insert_query);

mysqli_stmt_bind_param($stmt, 'iiiissssssssi', 
    $tenant_id, $department_id, $job_role_id, $assigned_user_id,
    $shift_date, $start_time, $end_time, $status, $shift_color, $shift_text_color,
    $public_notes, $private_notes, $user_id
);

if (mysqli_stmt_execute($stmt)) {
    $shift_id = mysqli_insert_id($dbc);
	
	$audit_query = "
        INSERT INTO to_audit_log (
            tenant_id, user_id, action, table_name, record_id, 
            new_values, ip_address, user_agent, created_at
        ) VALUES (?, ?, 'CREATE', 'to_shifts', ?, ?, ?, ?, NOW())
    ";
    
    $new_values = json_encode([
        'tenant_id' => $tenant_id,
        'department_id' => $department_id,
        'job_role_id' => $job_role_id,
        'assigned_user_id' => $assigned_user_id,
        'shift_date' => $shift_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'status' => $status,
        'shift_color' => $shift_color,
        'shift_text_color' => $shift_text_color,
        'public_notes' => $public_notes,
        'private_notes' => $private_notes
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $user_id, $shift_id, 
        $new_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Shift created successfully',
        'shift_id' => $shift_id,
        'shift_details' => [
            'date' => $shift_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => $status,
            'shift_color' => $shift_color,
            'shift_text_color' => $shift_text_color
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($dbc)
    ]);
}
?>