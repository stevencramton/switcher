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

if (!isset($_GET['role_id']) || empty($_GET['role_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing role ID']);
    exit();
}

$role_id = (int)$_GET['role_id'];
$role_query = "
    SELECT jr.*, d.tenant_id, d.name as department_name, d.color as department_color,
           COUNT(DISTINCT udr.user_id) as user_count,
           COUNT(DISTINCT s.id) as shift_count
    FROM to_job_roles jr 
    JOIN to_departments d ON jr.department_id = d.id 
    LEFT JOIN to_user_department_roles udr ON jr.id = udr.job_role_id AND udr.active = 1
    LEFT JOIN to_shifts s ON jr.id = s.job_role_id AND s.shift_date >= CURDATE()
    WHERE jr.id = ?
    GROUP BY jr.id
";

$stmt = mysqli_prepare($dbc, $role_query);
mysqli_stmt_bind_param($stmt, 'i', $role_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$role_data = mysqli_fetch_assoc($result);

if (!$role_data) {
    echo json_encode(['success' => false, 'message' => 'Role not found']);
    exit();
}

$tenant_id = $role_data['tenant_id'];
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

echo json_encode([
    'success' => true,
    'role' => [
        'id' => $role_data['id'],
        'name' => $role_data['name'],
        'description' => $role_data['description'],
        'color' => $role_data['color'],
        'sort_order' => $role_data['sort_order'],
        'active' => $role_data['active'],
        'department_id' => $role_data['department_id'],
        'department_name' => $role_data['department_name'],
        'department_color' => $role_data['department_color'],
        'tenant_id' => $role_data['tenant_id'],
        'user_count' => $role_data['user_count'],
        'shift_count' => $role_data['shift_count'],
        'created_at' => $role_data['created_at'],
        'updated_at' => $role_data['updated_at']
    ]
]);
?>