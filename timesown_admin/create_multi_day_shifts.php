<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

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

$required_fields = ['tenant_id', 'shift_dates', 'start_time', 'end_time', 'department_id', 'job_role_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$tenant_id = (int)$_POST['tenant_id'];
$shift_dates = $_POST['shift_dates'];
$start_time = trim($_POST['start_time']);
$end_time = trim($_POST['end_time']);
$department_id = (int)$_POST['department_id'];
$job_role_id = (int)$_POST['job_role_id'];
$assigned_user_id = !empty($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null;
$status = !empty($_POST['status']) ? trim($_POST['status']) : 'scheduled';
$shift_color = !empty($_POST['shift_color']) ? trim($_POST['shift_color']) : '#007bff';
$shift_text_color = !empty($_POST['shift_text_color']) ? trim($_POST['shift_text_color']) : '#ffffff';
$public_notes = !empty($_POST['public_notes']) ? trim($_POST['public_notes']) : '';
$private_notes = !empty($_POST['private_notes']) ? trim($_POST['private_notes']) : '';

$tenant_check = "SELECT id FROM to_tenants WHERE id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $tenant_check);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant']);
    exit();
}

$dept_role_check = "
    SELECT d.id as dept_id, r.id as role_id 
    FROM to_departments d
    JOIN to_job_roles r ON d.id = r.department_id
    WHERE d.id = ? AND r.id = ? AND d.tenant_id = ? AND d.active = 1 AND r.active = 1
";
$stmt = mysqli_prepare($dbc, $dept_role_check);
mysqli_stmt_bind_param($stmt, 'iii', $department_id, $job_role_id, $tenant_id);
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

if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
    !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit();
}

$start_datetime = new DateTime("2000-01-01 $start_time");
$end_datetime = new DateTime("2000-01-01 $end_time");

if ($start_datetime >= $end_datetime) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit();
}

$valid_statuses = ['scheduled', 'open', 'pending', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses)]);
    exit();
}

if (!is_array($shift_dates) || empty($shift_dates)) {
    echo json_encode(['success' => false, 'message' => 'No dates provided']);
    exit();
}

foreach ($shift_dates as $date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format: ' . $date]);
        exit();
    }
}

mysqli_autocommit($dbc, false);

$created_count = 0;
$skipped_count = 0;
$created_shift_ids = [];
$errors = [];

try {
    foreach ($shift_dates as $shift_date) {
       	if ($assigned_user_id) {
            $conflict_query = "
                SELECT id 
                FROM to_shifts 
                WHERE assigned_user_id = ? 
                AND shift_date = ? 
                AND tenant_id = ?
                AND status IN ('scheduled', 'pending')
                AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ";
            
            $stmt = mysqli_prepare($dbc, $conflict_query);
            if (!$stmt) {
                $errors[] = "Failed to prepare conflict query for $shift_date: " . mysqli_error($dbc);
                $skipped_count++;
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'isissssss', 
                $assigned_user_id, $shift_date, $tenant_id,
                $end_time, $start_time,
                $start_time, $end_time,
                $start_time, $end_time
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                $errors[] = "Failed to execute conflict query for $shift_date: " . mysqli_stmt_error($stmt);
                $skipped_count++;
                continue;
            }
            
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "User already has a conflicting shift on $shift_date";
                $skipped_count++;
                continue;
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
        if (!$stmt) {
            $errors[] = "Failed to prepare insert query for $shift_date: " . mysqli_error($dbc);
            $skipped_count++;
            continue;
        }
        
      	if ($assigned_user_id === null) {
          	mysqli_stmt_bind_param($stmt, 'iiisssssssssi', 
                $tenant_id, $department_id, $job_role_id, $assigned_user_id,
                $shift_date, $start_time, $end_time, $status, $shift_color, $shift_text_color,
                $public_notes, $private_notes, $user_id
            );
        } else {
            mysqli_stmt_bind_param($stmt, 'iiiissssssssi', 
                $tenant_id, $department_id, $job_role_id, $assigned_user_id,
                $shift_date, $start_time, $end_time, $status, $shift_color, $shift_text_color,
                $public_notes, $private_notes, $user_id
            );
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $shift_id = mysqli_insert_id($dbc);
            $created_shift_ids[] = $shift_id;
            $created_count++;
            
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
                'private_notes' => $private_notes,
                'multi_day_creation' => true
            ]);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $audit_stmt = mysqli_prepare($dbc, $audit_query);
            if ($audit_stmt) {
                mysqli_stmt_bind_param($audit_stmt, 'iissss', 
                    $tenant_id, $user_id, $shift_id, 
                    $new_values, $ip_address, $user_agent
                );
                mysqli_stmt_execute($audit_stmt);
            }
            
        } else {
            $mysql_error = mysqli_error($dbc);
            $stmt_error = mysqli_stmt_error($stmt);
            $errors[] = "Failed to create shift for $shift_date: MySQL error: $mysql_error | Stmt error: $stmt_error";
            $skipped_count++;
     	}
    }
    
    if ($created_count === 0) {
        mysqli_rollback($dbc);
        echo json_encode([
            'success' => false, 
            'message' => 'No shifts could be created. ' . implode(', ', $errors)
        ]);
        exit();
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully created $created_count shift(s)" . 
                    ($skipped_count > 0 ? " ($skipped_count skipped due to conflicts)" : ""),
        'created_count' => $created_count,
        'skipped_count' => $skipped_count,
        'created_shift_ids' => $created_shift_ids,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>