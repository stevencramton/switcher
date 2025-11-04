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

if (!isset($_POST['role_id']) || empty($_POST['role_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing role ID']);
    exit();
}

if (!isset($_POST['name']) || trim($_POST['name']) === '') {
    echo json_encode(['success' => false, 'message' => 'Role name is required']);
    exit();
}

$role_id = (int)$_POST['role_id'];
$name = trim($_POST['name']);
$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$color = isset($_POST['color']) ? trim($_POST['color']) : '#2ecc71';
$sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$active = isset($_POST['active']) ? 1 : 0;
$tenant_id = (int)$_POST['tenant_id'];

if (strlen($name) < 2 || strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Role name must be between 2 and 100 characters']);
    exit();
}

if ($description && strlen($description) > 500) {
    echo json_encode(['success' => false, 'message' => 'Description cannot exceed 500 characters']);
    exit();
}

if (!preg_match('/^#[0-9A-F]{6}$/i', $color)) {
    $color = '#2ecc71';
}

$current_role_query = "
    SELECT jr.*, d.tenant_id 
    FROM to_job_roles jr 
    JOIN to_departments d ON jr.department_id = d.id 
    WHERE jr.id = ?
";

$stmt = mysqli_prepare($dbc, $current_role_query);
mysqli_stmt_bind_param($stmt, 'i', $role_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_role = mysqli_fetch_assoc($result);

if (!$current_role) {
    echo json_encode(['success' => false, 'message' => 'Role not found']);
    exit();
}

if ($current_role['tenant_id'] != $tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Role does not belong to specified tenant']);
    exit();
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

$duplicate_check = "
    SELECT id FROM to_job_roles 
    WHERE department_id = ? AND name = ? AND id != ? AND active = 1
";

$stmt = mysqli_prepare($dbc, $duplicate_check);
mysqli_stmt_bind_param($stmt, 'isi', $current_role['department_id'], $name, $role_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'A role with this name already exists in this department']);
    exit();
}

mysqli_begin_transaction($dbc);

try {
    $update_query = "
        UPDATE to_job_roles 
        SET name = ?, description = ?, color = ?, sort_order = ?, active = ?, updated_at = NOW()
        WHERE id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, 'sssiii', $name, $description, $color, $sort_order, $active, $role_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update role: ' . mysqli_error($dbc));
    }
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, 'UPDATE', 'to_job_roles', ?, ?, ?, ?, ?)
    ";
    
    $old_values = json_encode([
        'name' => $current_role['name'],
        'description' => $current_role['description'],
        'color' => $current_role['color'],
        'sort_order' => $current_role['sort_order'],
        'active' => $current_role['active']
    ]);
    
    $new_values = json_encode([
        'name' => $name,
        'description' => $description,
        'color' => $color,
        'sort_order' => $sort_order,
        'active' => $active
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iisssss', 
        $tenant_id, $user_id, $role_id, $old_values, $new_values, $ip_address, $user_agent);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log('Failed to log role update: ' . mysqli_error($dbc));
    }
    
    mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true,
        'message' => 'Role updated successfully',
        'role' => [
            'id' => $role_id,
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'sort_order' => $sort_order,
            'active' => $active
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>