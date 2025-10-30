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
$admin_user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $admin_user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$admin_user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($admin_user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    exit();
}

$admin_user_data = mysqli_fetch_assoc($admin_user_result);
$admin_user_id = $admin_user_data['id'];

if (!isset($_POST['action']) || !isset($_POST['user_id']) || !isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: action, user_id, tenant_id']);
    exit();
}

$action = $_POST['action'];
$user_id = (int)$_POST['user_id'];
$tenant_id = (int)$_POST['tenant_id'];

try {
    mysqli_autocommit($dbc, false);
    
	$user_check = "
        SELECT ut.id as assignment_id, u.first_name, u.last_name, u.display_name
        FROM to_user_tenants ut
        JOIN users u ON ut.user_id = u.id
        WHERE ut.user_id = ? AND ut.tenant_id = ? AND ut.active = 1 AND u.account_delete = 0
    ";
    $stmt = mysqli_prepare($dbc, $user_check);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($user_result) === 0) {
        throw new Exception('User not found or not assigned to this organization');
    }
    
    $user_data = mysqli_fetch_assoc($user_result);
    $user_name = $user_data['display_name'] ?: ($user_data['first_name'] . ' ' . $user_data['last_name']);
    
    if ($action === 'add_assignment') {
      	if (!isset($_POST['department_id']) || !isset($_POST['job_role_id'])) {
            throw new Exception('Department ID and Job Role ID are required for assignment');
        }
        
        $department_id = (int)$_POST['department_id'];
        $job_role_id = (int)$_POST['job_role_id'];
        
      	$dept_role_check = "
            SELECT d.name as dept_name, jr.name as role_name
            FROM to_departments d
            JOIN to_job_roles jr ON jr.department_id = d.id
            WHERE d.id = ? AND jr.id = ? AND d.tenant_id = ? AND d.active = 1 AND jr.active = 1
        ";
        $stmt = mysqli_prepare($dbc, $dept_role_check);
        mysqli_stmt_bind_param($stmt, 'iii', $department_id, $job_role_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $dept_role_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($dept_role_result) === 0) {
            throw new Exception('Invalid department or role, or they do not belong to this organization');
        }
        
        $dept_role_data = mysqli_fetch_assoc($dept_role_result);
        
      	$existing_check = "
            SELECT id, active FROM to_user_department_roles
            WHERE user_id = ? AND department_id = ? AND job_role_id = ?
        ";
        $stmt = mysqli_prepare($dbc, $existing_check);
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $department_id, $job_role_id);
        mysqli_stmt_execute($stmt);
        $existing_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($existing_result) > 0) {
            $existing = mysqli_fetch_assoc($existing_result);
            if ($existing['active']) {
              	mysqli_commit($dbc);
                echo json_encode([
                    'success' => true,
                    'message' => "{$user_name} is already assigned to {$dept_role_data['dept_name']} - {$dept_role_data['role_name']}",
                    'assignment_id' => $existing['id'],
                    'was_existing' => true
                ]);
                exit();
            } else {
             	$reactivate_query = "UPDATE to_user_department_roles SET active = 1 WHERE id = ?";
                $stmt = mysqli_prepare($dbc, $reactivate_query);
                mysqli_stmt_bind_param($stmt, 'i', $existing['id']);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Error reactivating assignment: ' . mysqli_error($dbc));
                }
                $assignment_id = $existing['id'];
                $action_taken = 'reactivated';
            }
        } else {
          	$insert_query = "
                INSERT INTO to_user_department_roles (user_id, department_id, job_role_id, is_primary, active, created_at)
                VALUES (?, ?, ?, 0, 1, NOW())
            ";
            $stmt = mysqli_prepare($dbc, $insert_query);
            mysqli_stmt_bind_param($stmt, 'iii', $user_id, $department_id, $job_role_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error creating assignment: ' . mysqli_error($dbc));
            }
            $assignment_id = mysqli_insert_id($dbc);
            $action_taken = 'created';
        }
        
        mysqli_commit($dbc);
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully {$action_taken} assignment: {$user_name} to {$dept_role_data['dept_name']} - {$dept_role_data['role_name']}",
            'assignment_id' => $assignment_id,
            'action_taken' => $action_taken
        ]);
        
    } elseif ($action === 'remove_assignment') {
      	if (!isset($_POST['department_id']) || !isset($_POST['job_role_id'])) {
            throw new Exception('Department ID and Job Role ID are required for removal');
        }
        
        $department_id = (int)$_POST['department_id'];
        $job_role_id = (int)$_POST['job_role_id'];
        
      	$assignment_query = "
            SELECT udr.id, d.name as dept_name, jr.name as role_name, udr.active
            FROM to_user_department_roles udr
            JOIN to_departments d ON udr.department_id = d.id
            JOIN to_job_roles jr ON udr.job_role_id = jr.id
            WHERE udr.user_id = ? AND udr.department_id = ? AND udr.job_role_id = ? 
            AND d.tenant_id = ?
        ";
        $stmt = mysqli_prepare($dbc, $assignment_query);
        mysqli_stmt_bind_param($stmt, 'iiii', $user_id, $department_id, $job_role_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $assignment_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($assignment_result) === 0) {
          	mysqli_commit($dbc);
            echo json_encode([
                'success' => true,
                'message' => "{$user_name} was not assigned to this position (no action needed)",
                'was_not_assigned' => true
            ]);
            exit();
        }
        
        $assignment_data = mysqli_fetch_assoc($assignment_result);
        
        if (!$assignment_data['active']) {
          	mysqli_commit($dbc);
            echo json_encode([
                'success' => true,
                'message' => "{$user_name} was already removed from {$assignment_data['dept_name']} - {$assignment_data['role_name']}",
                'was_already_inactive' => true
            ]);
            exit();
        }
        
    	$future_shifts_check = "
            SELECT COUNT(*) as shift_count, MIN(shift_date) as earliest_shift
            FROM to_shifts
            WHERE assigned_user_id = ? AND department_id = ? AND job_role_id = ?
            AND shift_date >= CURDATE() AND status IN ('scheduled', 'pending')
        ";
        $stmt = mysqli_prepare($dbc, $future_shifts_check);
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $department_id, $job_role_id);
        mysqli_stmt_execute($stmt);
        $shifts_result = mysqli_stmt_get_result($stmt);
        $shifts_data = mysqli_fetch_assoc($shifts_result);
        
        if ($shifts_data['shift_count'] > 0) {
            $update_shifts_query = "
                UPDATE to_shifts 
                SET assigned_user_id = NULL, status = 'open'
                WHERE assigned_user_id = ? AND department_id = ? AND job_role_id = ?
                AND shift_date >= CURDATE()
            ";
            $stmt = mysqli_prepare($dbc, $update_shifts_query);
            mysqli_stmt_bind_param($stmt, 'iii', $user_id, $department_id, $job_role_id);
            mysqli_stmt_execute($stmt);
            
            $shifts_message = " ({$shifts_data['shift_count']} future shifts were unassigned)";
        } else {
            $shifts_message = "";
        }
        
        $remove_query = "UPDATE to_user_department_roles SET active = 0 WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $remove_query);
        mysqli_stmt_bind_param($stmt, 'i', $assignment_data['id']);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error removing assignment: ' . mysqli_error($dbc));
        }
        
        mysqli_commit($dbc);
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully removed {$user_name} from {$assignment_data['dept_name']} - {$assignment_data['role_name']}{$shifts_message}",
            'shifts_affected' => $shifts_data['shift_count']
        ]);
        
    } else {
        throw new Exception('Invalid action. Must be "add_assignment" or "remove_assignment"');
    }
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_autocommit($dbc, true);
?>