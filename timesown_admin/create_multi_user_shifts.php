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

// Required fields
$required_fields = ['tenant_id', 'start_time', 'end_time', 'department_id', 'job_role_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$tenant_id = (int)$_POST['tenant_id'];
$start_time = trim($_POST['start_time']);
$end_time = trim($_POST['end_time']);
$department_id = (int)$_POST['department_id'];
$job_role_id = (int)$_POST['job_role_id'];
$status = !empty($_POST['status']) ? trim($_POST['status']) : 'scheduled';
$shift_color = !empty($_POST['shift_color']) ? trim($_POST['shift_color']) : '#007bff';
$shift_text_color = !empty($_POST['shift_text_color']) ? trim($_POST['shift_text_color']) : '#ffffff';
$public_notes = !empty($_POST['public_notes']) ? trim($_POST['public_notes']) : '';
$private_notes = !empty($_POST['private_notes']) ? trim($_POST['private_notes']) : '';

// Handle dates - can be single date or multiple dates
$shift_dates = [];

// Check if we have selected days (multi-day functionality)
if (isset($_POST['selected_days']) && is_array($_POST['selected_days']) && !empty($_POST['selected_days'])) {
    // Multi-day mode: convert day indices to actual dates
    $base_date = isset($_POST['shift_date']) ? $_POST['shift_date'] : date('Y-m-d');
    $base_date_obj = new DateTime($base_date . ' 00:00:00');
    
    // Calculate week start (Monday)
    $day_of_week = $base_date_obj->format('w'); // 0 = Sunday, 1 = Monday, etc.
    $days_since_monday = ($day_of_week == 0) ? 6 : ($day_of_week - 1);
    $week_start = clone $base_date_obj;
    $week_start->sub(new DateInterval('P' . $days_since_monday . 'D'));
    
    // Convert selected day indices to actual dates
    foreach ($_POST['selected_days'] as $day_index) {
        $target_date = clone $week_start;
        $target_date->add(new DateInterval('P' . intval($day_index) . 'D'));
        $shift_dates[] = $target_date->format('Y-m-d');
    }
} elseif (isset($_POST['shift_dates']) && is_array($_POST['shift_dates'])) {
    // Direct date array (backward compatibility)
    $shift_dates = $_POST['shift_dates'];
} elseif (isset($_POST['shift_date']) && !empty($_POST['shift_date'])) {
    // Single date
    $shift_dates = [$_POST['shift_date']];
} else {
    echo json_encode(['success' => false, 'message' => 'No dates provided']);
    exit();
}

// Handle users - can be multiple users or leave open
$selected_users = [];
$leave_open = isset($_POST['leave_open']) && $_POST['leave_open'] === '1';

if (!$leave_open) {
    if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
        // Remove duplicates and convert to integers
        $selected_users = array_values(array_unique(array_map('intval', $_POST['selected_users'])));
    } elseif (isset($_POST['assigned_user_id']) && !empty($_POST['assigned_user_id'])) {
        $selected_users = [(int)$_POST['assigned_user_id']];
    }
    
    if (empty($selected_users)) {
        echo json_encode(['success' => false, 'message' => 'No users selected and not marked as open shift']);
        exit();
    }
}

// Validate tenant
$tenant_check = "SELECT id FROM to_tenants WHERE id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $tenant_check);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant']);
    exit();
}

// Validate department and role
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

// Validate users if not leaving open
if (!$leave_open && !empty($selected_users)) {
    $user_placeholders = str_repeat('?,', count($selected_users) - 1) . '?';
    $user_check = "
        SELECT u.id, u.first_name, u.last_name, ut.active as tenant_active, ut.tenant_id
        FROM users u
        JOIN to_user_tenants ut ON u.id = ut.user_id
        WHERE u.id IN ($user_placeholders) 
        AND ut.tenant_id = ? 
        AND ut.active = 1 
        AND u.account_delete = 0
    ";
    
    $stmt = mysqli_prepare($dbc, $user_check);
    $bind_params = array_merge($selected_users, [$tenant_id]);
    $bind_types = str_repeat('i', count($bind_params));
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
    
    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => false, 'message' => 'User validation query failed: ' . mysqli_stmt_error($stmt)]);
        exit();
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $found_users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $found_users[] = $row['id'];
    }
    
    if (count($found_users) !== count($selected_users)) {
        // Find which users are missing
        $missing_users = array_diff($selected_users, $found_users);
        
        $debug_info = [
            'selected_users' => $selected_users,
            'found_users' => $found_users,
            'missing_users' => array_values($missing_users),
            'tenant_id' => $tenant_id
        ];
        
        // Only check missing users if there are any
        if (!empty($missing_users)) {
            // Check if missing users exist at all
            $missing_ids = implode(',', array_map('intval', $missing_users));
            $missing_check = "SELECT id, first_name, last_name FROM users WHERE id IN ($missing_ids) AND account_delete = 0";
            $missing_result = mysqli_query($dbc, $missing_check);
            $existing_missing = [];
            if ($missing_result) {
                while ($row = mysqli_fetch_assoc($missing_result)) {
                    $existing_missing[] = $row;
                }
            }
            $debug_info['existing_missing_users'] = $existing_missing;
            
            // Check tenant assignments for missing users
            $tenant_check = "
                SELECT ut.user_id, ut.tenant_id, ut.active, u.first_name, u.last_name
                FROM to_user_tenants ut
                JOIN users u ON ut.user_id = u.id
                WHERE ut.user_id IN ($missing_ids)
            ";
            $tenant_result = mysqli_query($dbc, $tenant_check);
            $tenant_info = [];
            if ($tenant_result) {
                while ($row = mysqli_fetch_assoc($tenant_result)) {
                    $tenant_info[] = $row;
                }
            }
            $debug_info['tenant_assignments'] = $tenant_info;
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'User validation failed - users may not be assigned to this tenant or may be inactive',
            'debug' => $debug_info
        ]);
        exit();
    }
}

try {
    mysqli_autocommit($dbc, false);
    
    // Prepare shift creation query
    $shift_query = "
        INSERT INTO to_shifts (
            tenant_id, department_id, job_role_id, assigned_user_id,
            shift_date, start_time, end_time, status, shift_color, shift_text_color,
            public_notes, private_notes, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $stmt = mysqli_prepare($dbc, $shift_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare shift creation statement');
    }
    
    $created_count = 0;
    $skipped_count = 0;
    $created_shift_ids = [];
    $errors = [];
    
    // Create shifts for each date and user combination
    foreach ($shift_dates as $shift_date) {
        if ($leave_open) {
            // Create one open shift per date
            $assigned_user_id = null;
            
            if ($assigned_user_id === null) {
                mysqli_stmt_bind_param($stmt, 'iiisssssssssi', 
                    $tenant_id, $department_id, $job_role_id, $assigned_user_id,
                    $shift_date, $start_time, $end_time, $status, $shift_color, $shift_text_color,
                    $public_notes, $private_notes, $user_id
                );
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $shift_id = mysqli_insert_id($dbc);
                $created_shift_ids[] = $shift_id;
                $created_count++;
                
                // Add audit log
                logShiftCreation($dbc, $tenant_id, $user_id, $shift_id, [
                    'tenant_id' => $tenant_id,
                    'department_id' => $department_id,
                    'job_role_id' => $job_role_id,
                    'assigned_user_id' => null,
                    'shift_date' => $shift_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'status' => $status,
                    'shift_color' => $shift_color,
                    'shift_text_color' => $shift_text_color,
                    'public_notes' => $public_notes,
                    'private_notes' => $private_notes,
                    'multi_user_creation' => true,
                    'leave_open' => true
                ]);
            } else {
                $errors[] = "Failed to create open shift for $shift_date";
                $skipped_count++;
            }
        } else {
            // Create one shift per user per date
            foreach ($selected_users as $assigned_user_id) {
                mysqli_stmt_bind_param($stmt, 'iiiissssssssi', 
                    $tenant_id, $department_id, $job_role_id, $assigned_user_id,
                    $shift_date, $start_time, $end_time, $status, $shift_color, $shift_text_color,
                    $public_notes, $private_notes, $user_id
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $shift_id = mysqli_insert_id($dbc);
                    $created_shift_ids[] = $shift_id;
                    $created_count++;
                    
                    // Add audit log
                    logShiftCreation($dbc, $tenant_id, $user_id, $shift_id, [
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
                        'multi_user_creation' => true
                    ]);
                } else {
                    $errors[] = "Failed to create shift for user $assigned_user_id on $shift_date";
                    $skipped_count++;
                }
            }
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
        'errors' => $errors,
        'details' => [
            'dates' => count($shift_dates),
            'users' => $leave_open ? 'open' : count($selected_users),
            'leave_open' => $leave_open
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

function logShiftCreation($dbc, $tenant_id, $user_id, $shift_id, $data) {
    $audit_query = "
        INSERT INTO to_audit_log (
            tenant_id, user_id, action, table_name, record_id, 
            new_values, ip_address, user_agent, created_at
        ) VALUES (?, ?, 'CREATE', 'to_shifts', ?, ?, ?, ?, NOW())
    ";
    
    $new_values = json_encode($data);
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
}
?>