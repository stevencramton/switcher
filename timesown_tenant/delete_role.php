<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('timesown_tenant')) {
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
$user_id = $admin_user_data['id'];

if (!isset($_POST['role_id']) || !isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Role ID and Tenant ID are required']);
    exit();
}

$role_id = (int)$_POST['role_id'];
$tenant_id = (int)$_POST['tenant_id'];
$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$hard_delete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === '1';

try {
    mysqli_autocommit($dbc, false);
    
    $role_query = "
        SELECT jr.*, d.name as department_name, d.tenant_id
        FROM to_job_roles jr
        JOIN to_departments d ON jr.department_id = d.id
        WHERE jr.id = ? AND d.tenant_id = ?
    ";
    $stmt = mysqli_prepare($dbc, $role_query);
    mysqli_stmt_bind_param($stmt, 'ii', $role_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $role_result = mysqli_stmt_get_result($stmt);
    $role_data = mysqli_fetch_assoc($role_result);
    
    if (!$role_data) {
        throw new Exception('Role not found or access denied');
    }
    
    $assignment_check = "
        SELECT COUNT(*) as assignment_count
        FROM to_user_department_roles 
        WHERE job_role_id = ? AND active = 1
    ";
    $stmt = mysqli_prepare($dbc, $assignment_check);
    mysqli_stmt_bind_param($stmt, 'i', $role_id);
    mysqli_stmt_execute($stmt);
    $assignment_result = mysqli_stmt_get_result($stmt);
    $assignment_data = mysqli_fetch_assoc($assignment_result);
    
    $shifts_check = "
        SELECT COUNT(*) as shift_count
        FROM to_shifts 
        WHERE job_role_id = ? AND shift_date >= CURDATE()
    ";
    $stmt = mysqli_prepare($dbc, $shifts_check);
    mysqli_stmt_bind_param($stmt, 'i', $role_id);
    mysqli_stmt_execute($stmt);
    $shifts_result = mysqli_stmt_get_result($stmt);
    $shifts_data = mysqli_fetch_assoc($shifts_result);
    
    if ($hard_delete) {
        if ($assignment_data['assignment_count'] > 0) {
            $remove_assignments = "DELETE FROM to_user_department_roles WHERE job_role_id = ?";
            $stmt = mysqli_prepare($dbc, $remove_assignments);
            mysqli_stmt_bind_param($stmt, 'i', $role_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to remove user assignments: ' . mysqli_error($dbc));
            }
        }
        
        if ($shifts_data['shift_count'] > 0) {
            $delete_shifts = "DELETE FROM to_shifts WHERE job_role_id = ? AND shift_date >= CURDATE()";
            $stmt = mysqli_prepare($dbc, $delete_shifts);
            mysqli_stmt_bind_param($stmt, 'i', $role_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete future shifts: ' . mysqli_error($dbc));
            }
        }
        
        $delete_role = "DELETE FROM to_job_roles WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $delete_role);
        mysqli_stmt_bind_param($stmt, 'i', $role_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to delete role: ' . mysqli_error($dbc));
        }
        
        $deletion_type = 'HARD_DELETE';
        $message = 'Role permanently deleted';
        
    } else {
        if ($assignment_data['assignment_count'] > 0) {
            $deactivate_assignments = "UPDATE to_user_department_roles SET active = 0 WHERE job_role_id = ?";
            $stmt = mysqli_prepare($dbc, $deactivate_assignments);
            mysqli_stmt_bind_param($stmt, 'i', $role_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to deactivate user assignments: ' . mysqli_error($dbc));
            }
        }
        
        if ($shifts_data['shift_count'] > 0) {
            $update_shifts = "
                UPDATE to_shifts 
                SET assigned_user_id = NULL, status = 'open', updated_at = NOW()
                WHERE job_role_id = ? AND shift_date >= CURDATE()
            ";
            $stmt = mysqli_prepare($dbc, $update_shifts);
            mysqli_stmt_bind_param($stmt, 'i', $role_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update future shifts: ' . mysqli_error($dbc));
            }
        }
        
        $delete_role = "UPDATE to_job_roles SET active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $delete_role);
        mysqli_stmt_bind_param($stmt, 'i', $role_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to deactivate role: ' . mysqli_error($dbc));
        }
        
        $deletion_type = 'SOFT_DELETE';
        $message = 'Role deactivated successfully';
    }
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, 'DELETE', 'to_job_roles', ?, ?, ?, ?, ?)
    ";
    
    $old_values = json_encode([
        'id' => $role_data['id'],
        'name' => $role_data['name'],
        'department_id' => $role_data['department_id'],
        'department_name' => $role_data['department_name'],
        'assignments_affected' => $assignment_data['assignment_count'],
        'shifts_affected' => $shifts_data['shift_count']
    ]);
    
    $new_values = json_encode([
        'deletion_type' => $deletion_type,
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
	
    if ($stmt) {
        $bind_success = mysqli_stmt_bind_param($stmt, 'iisssss', 
 	   	$tenant_id, $user_id, $role_id, $old_values, $new_values, $ip_address, $user_agent);
        
        if ($bind_success) {
            if (!mysqli_stmt_execute($stmt)) {
                error_log('Failed to execute audit log: ' . mysqli_error($dbc));
            }
        } else {
            error_log('Failed to bind audit parameters: ' . mysqli_error($dbc));
        }
    } else {
        error_log('Failed to prepare audit query: ' . mysqli_error($dbc));
    }
    
    mysqli_commit($dbc);
    
    if ($assignment_data['assignment_count'] > 0) {
        $message .= '. ' . $assignment_data['assignment_count'] . ' user assignment(s) affected';
    }
    if ($shifts_data['shift_count'] > 0) {
        $message .= '. ' . $shifts_data['shift_count'] . ' future shift(s) updated';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'deletion_type' => $deletion_type,
        'assignments_affected' => $assignment_data['assignment_count'],
        'shifts_affected' => $shifts_data['shift_count']
    ]);
    
} catch (Exception $e) {
	mysqli_rollback($dbc);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>