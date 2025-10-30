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

if (!isset($_POST['tenant_id']) || empty($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$action = $_POST['action'] ?? 'create_department';

try {
    mysqli_autocommit($dbc, false);
    
    if ($action === 'create_department') {
        if (!isset($_POST['department_name']) || empty($_POST['department_name'])) {
            throw new Exception('Department name is required');
        }
        
        $dept_name = trim($_POST['department_name']);
        $dept_description = trim($_POST['department_description'] ?? '');
        $dept_color = $_POST['department_color'] ?? '#3498db';
        
        $dept_query = "
            INSERT INTO to_departments (tenant_id, name, description, color, active, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 0, NOW(), NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $dept_query);
        mysqli_stmt_bind_param($stmt, 'isss', $tenant_id, $dept_name, $dept_description, $dept_color);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error creating department: ' . mysqli_error($dbc));
        }
        
        $department_id = mysqli_insert_id($dbc);
        
        $role_query = "
            INSERT INTO to_job_roles (department_id, name, description, color, active, sort_order, created_at, updated_at)
            VALUES (?, 'Staff', 'General staff role', '#2ecc71', 1, 0, NOW(), NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $role_query);
        mysqli_stmt_bind_param($stmt, 'i', $department_id);
        mysqli_stmt_execute($stmt);
        
        $message = 'Department created successfully';
        $result_data = ['department_id' => $department_id];
        
    } elseif ($action === 'create_role') {
        if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
            throw new Exception('Department ID is required');
        }
        
        if (!isset($_POST['role_name']) || empty($_POST['role_name'])) {
            throw new Exception('Role name is required');
        }
        
        $department_id = (int)$_POST['department_id'];
        $role_name = trim($_POST['role_name']);
        $role_description = trim($_POST['role_description'] ?? '');
        $role_color = $_POST['role_color'] ?? '#2ecc71';
        
        $dept_check = "SELECT id FROM to_departments WHERE id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $dept_check);
        mysqli_stmt_bind_param($stmt, 'ii', $department_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception('Department not found or does not belong to this tenant');
        }
        
        // Insert role
        $role_query = "
            INSERT INTO to_job_roles (department_id, name, description, color, active, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 0, NOW(), NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $role_query);
        mysqli_stmt_bind_param($stmt, 'isss', $department_id, $role_name, $role_description, $role_color);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error creating role: ' . mysqli_error($dbc));
        }
        
        $role_id = mysqli_insert_id($dbc);
        
        $message = 'Role created successfully';
        $result_data = ['role_id' => $role_id];
        
    } else {
        throw new Exception('Invalid action specified');
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'CREATE', ?, ?, ?, ?, ?, NOW())
    ";
    
    $table_name = $action === 'create_department' ? 'to_departments' : 'to_job_roles';
    $record_id = $action === 'create_department' ? $department_id : $role_id;
    
    $new_values = json_encode($_POST);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
	
	mysqli_stmt_bind_param($stmt, 'iisisss', 
        $tenant_id, $user_id, $table_name, $record_id,
        $new_values, $ip_address, $user_agent
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