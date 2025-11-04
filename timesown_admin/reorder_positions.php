<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    $tenant_id = (int)($input['tenant_id'] ?? 0);
    $action = $input['action'] ?? '';
    
    if (!$tenant_id) {
        throw new Exception('Tenant ID is required');
    }

   	$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
    $stmt = mysqli_prepare($dbc, $user_query);
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['switch_id']);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    $actual_user_id = $user_data['id'];

   	if (!checkRole('admin_developer')) {
        $tenant_access_query = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $tenant_access_query);
        mysqli_stmt_bind_param($stmt, 'ii', $actual_user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
            throw new Exception('Access denied to this tenant');
        }
    }

    mysqli_autocommit($dbc, false);

    if ($action === 'reorder_departments') {
        $department_order = $input['department_order'] ?? [];
        
        if (empty($department_order)) {
            throw new Exception('Department order data is required');
        }

      	$placeholders = str_repeat('?,', count($department_order) - 1) . '?';
        $verify_query = "SELECT id FROM to_departments WHERE id IN ($placeholders) AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $verify_query);
        $verify_params = array_merge($department_order, [$tenant_id]);
        $verify_types = str_repeat('i', count($verify_params));
        mysqli_stmt_bind_param($stmt, $verify_types, ...$verify_params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) !== count($department_order)) {
            throw new Exception('Invalid department IDs provided');
        }

      	$update_query = "UPDATE to_departments SET sort_order = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        
        foreach ($department_order as $index => $dept_id) {
            $sort_order = $index + 1;
            mysqli_stmt_bind_param($stmt, 'iii', $sort_order, $dept_id, $tenant_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error updating department sort order: ' . mysqli_error($dbc));
            }
        }

      	$audit_query = "INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES (?, ?, 'REORDER', 'to_departments', 0, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $audit_query);
        $new_values = json_encode(['department_order' => $department_order]);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($stmt, 'iisss', $tenant_id, $actual_user_id, $new_values, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt);

        mysqli_commit($dbc);
        echo json_encode(['success' => true, 'message' => 'Department order updated successfully']);

    } elseif ($action === 'reorder_roles') {
        $department_id = (int)($input['department_id'] ?? 0);
        $role_order = $input['role_order'] ?? [];
        
        if (!$department_id || empty($role_order)) {
            throw new Exception('Department ID and role order data are required');
        }

      	$dept_query = "SELECT id FROM to_departments WHERE id = ? AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $dept_query);
        mysqli_stmt_bind_param($stmt, 'ii', $department_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 0) {
            throw new Exception('Invalid department ID');
        }

      	$placeholders = str_repeat('?,', count($role_order) - 1) . '?';
        $verify_query = "SELECT id FROM to_job_roles WHERE id IN ($placeholders) AND department_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $verify_query);
        $verify_params = array_merge($role_order, [$department_id]);
        $verify_types = str_repeat('i', count($verify_params));
        mysqli_stmt_bind_param($stmt, $verify_types, ...$verify_params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) !== count($role_order)) {
            throw new Exception('Invalid role IDs provided');
        }

   	 	$update_query = "UPDATE to_job_roles SET sort_order = ?, updated_at = NOW() WHERE id = ? AND department_id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        
        foreach ($role_order as $index => $role_id) {
            $sort_order = $index + 1;
            mysqli_stmt_bind_param($stmt, 'iii', $sort_order, $role_id, $department_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Error updating role sort order: ' . mysqli_error($dbc));
            }
        }

  	  	$audit_query = "INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent) VALUES (?, ?, 'REORDER', 'to_job_roles', ?, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $audit_query);
        $new_values = json_encode(['role_order' => $role_order]);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        mysqli_stmt_bind_param($stmt, 'iiisss', $tenant_id, $actual_user_id, $department_id, $new_values, $ip_address, $user_agent);
        mysqli_stmt_execute($stmt);

        mysqli_commit($dbc);
        echo json_encode(['success' => true, 'message' => 'Role order updated successfully']);

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    mysqli_rollback($dbc);
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_autocommit($dbc, true);
}
?>