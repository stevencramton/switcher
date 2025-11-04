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
$admin_user_id = $admin_user_data['id'];

if (!isset($_POST['user_id']) || !isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID and Tenant ID are required']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$tenant_id = (int)$_POST['tenant_id'];
$is_primary = isset($_POST['is_primary']) ? (int)$_POST['is_primary'] : 0;

$assignments = [];
if (isset($_POST['assignments']) && is_array($_POST['assignments'])) {
    foreach ($_POST['assignments'] as $assignment) {
        if (isset($assignment['department_id']) && isset($assignment['job_role_id'])) {
            $assignments[] = [
                'department_id' => (int)$assignment['department_id'],
                'job_role_id' => (int)$assignment['job_role_id'],
                'is_primary' => isset($assignment['is_primary']) ? (int)$assignment['is_primary'] : 0
            ];
        }
    }
}

$remove_assignments = [];
if (isset($_POST['remove_assignments']) && is_array($_POST['remove_assignments'])) {
    foreach ($_POST['remove_assignments'] as $assignment_id) {
        $remove_assignments[] = (int)$assignment_id;
    }
}

try {
    mysqli_autocommit($dbc, false);
    
    $assignment_check = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $assignment_check);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('User is not assigned to this tenant');
    }
    
    $tenant_assignment = mysqli_fetch_assoc($result);
    
    if ($is_primary) {
        $remove_primary_query = "UPDATE to_user_tenants SET is_primary = 0 WHERE user_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $remove_primary_query);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        if (!mysqli_stmt_execute($stmt)) {
           	error_log('Update User Assignment - Remove Primary Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id);
          	throw new Exception('Error updating primary status');
        }
        
        $set_primary_query = "UPDATE to_user_tenants SET is_primary = 1 WHERE user_id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $set_primary_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        if (!mysqli_stmt_execute($stmt)) {
          	error_log('Update User Assignment - Set Primary Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Tenant ID: ' . $tenant_id);
          	throw new Exception('Error setting primary tenant');
        }
    } else {
        $remove_primary_query = "UPDATE to_user_tenants SET is_primary = 0 WHERE user_id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $remove_primary_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        if (!mysqli_stmt_execute($stmt)) {
          	error_log('Update User Assignment - Remove Primary Status Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Tenant ID: ' . $tenant_id);
          	throw new Exception('Error removing primary status');
        }
    }
    
    if (!empty($remove_assignments)) {
        $remove_placeholders = str_repeat('?,', count($remove_assignments) - 1) . '?';
        $remove_query = "
            UPDATE to_user_department_roles udr
            JOIN to_departments d ON udr.department_id = d.id
            SET udr.active = 0
            WHERE udr.id IN ($remove_placeholders) AND udr.user_id = ? AND d.tenant_id = ?
        ";
        
        $stmt = mysqli_prepare($dbc, $remove_query);
        $remove_params = array_merge($remove_assignments, [$user_id, $tenant_id]);
        $remove_types = str_repeat('i', count($remove_params));
        mysqli_stmt_bind_param($stmt, $remove_types, ...$remove_params);
        
        if (!mysqli_stmt_execute($stmt)) {
          	error_log('Update User Assignment - Remove Dept Assignments Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Assignments: ' . implode(',', $remove_assignments));
            
        	throw new Exception('Error removing department assignments');
        }
    }
    
    foreach ($assignments as $assignment) {
        $dept_id = $assignment['department_id'];
        $role_id = $assignment['job_role_id'];
        $is_role_primary = $assignment['is_primary'];
        
        $dept_check = "SELECT id FROM to_departments WHERE id = ? AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $dept_check);
        mysqli_stmt_bind_param($stmt, 'ii', $dept_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
            throw new Exception("Invalid department ID: $dept_id");
        }
        
        $role_check = "SELECT id FROM to_job_roles WHERE id = ? AND department_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $role_check);
        mysqli_stmt_bind_param($stmt, 'ii', $role_id, $dept_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
            throw new Exception("Invalid role ID: $role_id for department: $dept_id");
        }
        
        $existing_check = "
            SELECT id, active FROM to_user_department_roles 
            WHERE user_id = ? AND department_id = ? AND job_role_id = ?
        ";
        $stmt = mysqli_prepare($dbc, $existing_check);
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $dept_id, $role_id);
        mysqli_stmt_execute($stmt);
        $existing_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($existing_result) > 0) {
            $existing = mysqli_fetch_assoc($existing_result);
            $update_query = "
                UPDATE to_user_department_roles 
                SET active = 1, is_primary = ?
                WHERE id = ?
            ";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'ii', $is_role_primary, $existing['id']);
        } else {
            $insert_query = "
                INSERT INTO to_user_department_roles (user_id, department_id, job_role_id, is_primary, active, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ";
            $stmt = mysqli_prepare($dbc, $insert_query);
            mysqli_stmt_bind_param($stmt, 'iiii', $user_id, $dept_id, $role_id, $is_role_primary);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
           	error_log('Update User Assignment - Dept Role Assignment Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Dept ID: ' . $dept_id . ' | Role ID: ' . $role_id);
            
          	throw new Exception('Error updating department role assignment');
        }
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'UPDATE', 'to_user_tenants', ?, ?, ?, ?, NOW())
    ";
    
    $new_values = json_encode([
        'user_id' => $user_id,
        'tenant_id' => $tenant_id,
        'is_primary' => $is_primary,
        'assignments_added' => count($assignments),
        'assignments_removed' => count($remove_assignments),
        'updated_by' => $admin_user_id
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $admin_user_id, $tenant_assignment['id'],
        $new_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'User assignment updated successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
 	error_log('Update User Assignment - Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
  	$error_message = 'Failed to update user assignment';
    $http_code = 500;
    
	if ($e->getMessage() === 'User is not assigned to this tenant') {
        $error_message = 'User is not assigned to this organization';
        $http_code = 400;
    } elseif (strpos($e->getMessage(), 'Invalid department ID:') === 0) {
        $error_message = 'Invalid department selected';
        $http_code = 400;
    } elseif (strpos($e->getMessage(), 'Invalid role ID:') === 0) {
        $error_message = 'Invalid role selected for the department';
        $http_code = 400;
    }
    
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'message' => $error_message
    ]);
}
?>