<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_tenant')) {
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

if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

if (!isset($_POST['tenant_id']) || empty($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$tenant_id = (int)$_POST['tenant_id'];
$is_primary = isset($_POST['is_primary']) ? (int)$_POST['is_primary'] : 0;
$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$job_role_id = isset($_POST['job_role_id']) ? (int)$_POST['job_role_id'] : null;

$user_check = "SELECT id, first_name, last_name, display_name FROM users WHERE id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_check);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found or inactive']);
    exit();
}

$user_data = mysqli_fetch_assoc($result);

$tenant_check = "SELECT id, name FROM to_tenants WHERE id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $tenant_check);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Tenant not found or inactive']);
    exit();
}

$tenant_data = mysqli_fetch_assoc($result);

$existing_check = "SELECT id, active FROM to_user_tenants WHERE user_id = ? AND tenant_id = ?";
$stmt = mysqli_prepare($dbc, $existing_check);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $existing = mysqli_fetch_assoc($result);
    if ($existing['active']) {
        echo json_encode(['success' => false, 'message' => 'User is already assigned to this organization']);
        exit();
    } else {
        $reactivate_query = "UPDATE to_user_tenants SET active = 1, is_primary = ? WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $reactivate_query);
        mysqli_stmt_bind_param($stmt, 'ii', $is_primary, $existing['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'User assignment reactivated successfully'
            ]);
        } else {
         	error_log('Assign User to Tenant - Reactivation Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Tenant ID: ' . $tenant_id);
         	http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error reactivating user assignment'
            ]);
        }
        exit();
    }
}

if ($department_id && $job_role_id) {
    $dept_role_check = "
        SELECT jr.id 
        FROM to_job_roles jr 
        JOIN to_departments d ON jr.department_id = d.id 
        WHERE jr.id = ? AND d.id = ? AND d.tenant_id = ? AND jr.active = 1 AND d.active = 1
    ";
    $stmt = mysqli_prepare($dbc, $dept_role_check);
    mysqli_stmt_bind_param($stmt, 'iii', $job_role_id, $department_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department or role for this organization']);
        exit();
    }
}

try {
    mysqli_autocommit($dbc, false);
    
	if ($is_primary) {
        $remove_primary_query = "UPDATE to_user_tenants SET is_primary = 0 WHERE user_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $remove_primary_query);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
    }
    
	$assign_query = "
        INSERT INTO to_user_tenants (user_id, tenant_id, is_primary, active, created_at)
        VALUES (?, ?, ?, 1, NOW())
    ";
    
    $stmt = mysqli_prepare($dbc, $assign_query);
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $tenant_id, $is_primary);
    
    if (!mysqli_stmt_execute($stmt)) {
    	error_log('Assign User to Tenant - Assignment Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Tenant ID: ' . $tenant_id);
        
     	throw new Exception('Error assigning user to tenant');
    }
    
    $assignment_id = mysqli_insert_id($dbc);
    
	if ($department_id && $job_role_id) {
        $role_assign_query = "
            INSERT INTO to_user_department_roles (user_id, department_id, job_role_id, is_primary, active, created_at)
            VALUES (?, ?, ?, 1, 1, NOW())
            ON DUPLICATE KEY UPDATE active = 1, is_primary = 1
        ";
        
        $stmt = mysqli_prepare($dbc, $role_assign_query);
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $department_id, $job_role_id);
        
        if (!mysqli_stmt_execute($stmt)) {
         	error_log('Assign User to Tenant - Department Role Error: ' . mysqli_error($dbc) . ' | User ID: ' . $user_id . ' | Dept ID: ' . $department_id . ' | Role ID: ' . $job_role_id);
            
         	throw new Exception('Error assigning user to department role');
        }
    }
    
	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
	$audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'CREATE', 'to_user_tenants', ?, ?, ?, ?, NOW())
    ";
    
    $new_values = json_encode([
        'user_id' => $user_id,
        'tenant_id' => $tenant_id,
        'is_primary' => $is_primary,
        'department_id' => $department_id,
        'job_role_id' => $job_role_id,
        'user_name' => $user_data['display_name'] ?: ($user_data['first_name'] . ' ' . $user_data['last_name']),
        'tenant_name' => $tenant_data['name']
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $admin_user_id, $assignment_id, 
        $new_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'User assigned to organization successfully',
        'assignment_id' => $assignment_id
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
  	
	error_log('Assign User to Tenant - Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
 	http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to assign user to organization'
    ]);
}
?>