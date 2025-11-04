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

if (!isset($_POST['action']) || empty($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action is required']);
    exit();
}

$action = $_POST['action'];

try {
    mysqli_autocommit($dbc, false);
    
    if ($action === 'update_department') {
        if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
            throw new Exception('Department ID is required');
        }
        
        if (!isset($_POST['department_name']) || empty($_POST['department_name'])) {
            throw new Exception('Department name is required');
        }
        
        $department_id = (int)$_POST['department_id'];
        $dept_name = trim($_POST['department_name']);
        $dept_description = trim($_POST['department_description'] ?? '');
        $dept_color = $_POST['department_color'] ?? '#3498db';
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        $tenant_id = (int)$_POST['tenant_id'];
        
      	$current_query = "SELECT * FROM to_departments WHERE id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $current_query);
        mysqli_stmt_bind_param($stmt, 'ii', $department_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception('Department not found or access denied');
        }
        
        $current_dept = mysqli_fetch_assoc($result);
        
      	$update_query = "
            UPDATE to_departments 
            SET name = ?, description = ?, color = ?, active = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
        ";
        
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'sssiii', $dept_name, $dept_description, $dept_color, $active, $department_id, $tenant_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error updating department: ' . mysqli_error($dbc));
        }
        
        $message = 'Department updated successfully';
        $result_data = ['department_id' => $department_id];
        
      	$old_values = json_encode([
            'name' => $current_dept['name'],
            'description' => $current_dept['description'],
            'color' => $current_dept['color'],
            'active' => $current_dept['active']
        ]);
        
        $new_values = json_encode([
            'name' => $dept_name,
            'description' => $dept_description,
            'color' => $dept_color,
            'active' => $active
        ]);
        
    } elseif ($action === 'update_role') {
        if (!isset($_POST['role_id']) || empty($_POST['role_id'])) {
            throw new Exception('Role ID is required');
        }
        
        if (!isset($_POST['role_name']) || empty($_POST['role_name'])) {
            throw new Exception('Role name is required');
        }
        
        $role_id = (int)$_POST['role_id'];
        $role_name = trim($_POST['role_name']);
        $role_description = trim($_POST['role_description'] ?? '');
        $role_color = $_POST['role_color'] ?? '#2ecc71';
        $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
        
     	$current_query = "
            SELECT jr.*, d.tenant_id 
            FROM to_job_roles jr 
            JOIN to_departments d ON jr.department_id = d.id 
            WHERE jr.id = ?
        ";
        $stmt = mysqli_prepare($dbc, $current_query);
        mysqli_stmt_bind_param($stmt, 'i', $role_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception('Role not found');
        }
        
        $current_role = mysqli_fetch_assoc($result);
        $tenant_id = $current_role['tenant_id'];
        
      	$update_query = "
            UPDATE to_job_roles 
            SET name = ?, description = ?, color = ?, active = ?, updated_at = NOW()
            WHERE id = ?
        ";
        
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'sssii', $role_name, $role_description, $role_color, $active, $role_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error updating role: ' . mysqli_error($dbc));
        }
        
        $message = 'Role updated successfully';
        $result_data = ['role_id' => $role_id];
        
      	$old_values = json_encode([
            'name' => $current_role['name'],
            'description' => $current_role['description'],
            'color' => $current_role['color'],
            'active' => $current_role['active']
        ]);
        
        $new_values = json_encode([
            'name' => $role_name,
            'description' => $role_description,
            'color' => $role_color,
            'active' => $active
        ]);
        
    } else {
        throw new Exception('Invalid action specified');
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
	$audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'UPDATE', ?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $table_name = $action === 'update_department' ? 'to_departments' : 'to_job_roles';
    $record_id = $action === 'update_department' ? $department_id : $role_id;
 	$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iississs', 
        $tenant_id, $user_id, $table_name, $record_id,
        $old_values, $new_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $result_data
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