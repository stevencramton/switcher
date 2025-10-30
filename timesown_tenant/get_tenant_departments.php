<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

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

if (!isset($_GET['tenant_id']) || empty($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$tenant_id = (int)$_GET['tenant_id'];
$departments_query = "
    SELECT d.id, d.name, d.description, d.color, d.active, d.sort_order, d.created_at, d.updated_at
    FROM to_departments d
    WHERE d.tenant_id = ? AND d.active = 1
    ORDER BY d.sort_order, d.name
";

$stmt = mysqli_prepare($dbc, $departments_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);

$departments = [];
while ($dept = mysqli_fetch_assoc($dept_result)) {
    $roles_query = "
        SELECT jr.id, jr.name, jr.description, jr.color, jr.active, jr.sort_order, jr.created_at, jr.updated_at
        FROM to_job_roles jr
        WHERE jr.department_id = ?
        ORDER BY jr.sort_order, jr.name
    ";
    
    $roles_stmt = mysqli_prepare($dbc, $roles_query);
    mysqli_stmt_bind_param($roles_stmt, 'i', $dept['id']);
    mysqli_stmt_execute($roles_stmt);
    $roles_result = mysqli_stmt_get_result($roles_stmt);
    
    $roles = [];
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $user_count_query = "
            SELECT COUNT(DISTINCT udr.user_id) as user_count
            FROM to_user_department_roles udr
            WHERE udr.department_id = ? AND udr.job_role_id = ? AND udr.active = 1
        ";
        
        $count_stmt = mysqli_prepare($dbc, $user_count_query);
        mysqli_stmt_bind_param($count_stmt, 'ii', $dept['id'], $role['id']);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_data = mysqli_fetch_assoc($count_result);
        
        $shift_count_query = "
            SELECT COUNT(*) as shift_count
            FROM to_shifts s
            WHERE s.department_id = ? AND s.job_role_id = ? AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        
        $shift_stmt = mysqli_prepare($dbc, $shift_count_query);
        mysqli_stmt_bind_param($shift_stmt, 'ii', $dept['id'], $role['id']);
        mysqli_stmt_execute($shift_stmt);
        $shift_result = mysqli_stmt_get_result($shift_stmt);
        $shift_data = mysqli_fetch_assoc($shift_result);
        
        $roles[] = [
            'id' => $role['id'],
            'name' => $role['name'],
            'description' => $role['description'],
            'color' => $role['color'],
            'active' => (bool)$role['active'],
            'sort_order' => $role['sort_order'],
            'user_count' => $count_data['user_count'],
            'recent_shifts' => $shift_data['shift_count'],
            'created_at' => $role['created_at'],
            'updated_at' => $role['updated_at']
        ];
    }
    
    $dept_user_count_query = "
        SELECT COUNT(DISTINCT udr.user_id) as user_count
        FROM to_user_department_roles udr
        WHERE udr.department_id = ? AND udr.active = 1
    ";
    
    $dept_count_stmt = mysqli_prepare($dbc, $dept_user_count_query);
    mysqli_stmt_bind_param($dept_count_stmt, 'i', $dept['id']);
    mysqli_stmt_execute($dept_count_stmt);
    $dept_count_result = mysqli_stmt_get_result($dept_count_stmt);
    $dept_count_data = mysqli_fetch_assoc($dept_count_result);
    
    $dept_shift_count_query = "
        SELECT COUNT(*) as shift_count
        FROM to_shifts s
        WHERE s.department_id = ? AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $dept_shift_stmt = mysqli_prepare($dbc, $dept_shift_count_query);
    mysqli_stmt_bind_param($dept_shift_stmt, 'i', $dept['id']);
    mysqli_stmt_execute($dept_shift_stmt);
    $dept_shift_result = mysqli_stmt_get_result($dept_shift_stmt);
    $dept_shift_data = mysqli_fetch_assoc($dept_shift_result);
    
    $departments[] = [
        'id' => $dept['id'],
        'name' => $dept['name'],
        'description' => $dept['description'],
        'color' => $dept['color'],
        'active' => (bool)$dept['active'],
        'sort_order' => $dept['sort_order'],
        'user_count' => $dept_count_data['user_count'],
        'recent_shifts' => $dept_shift_data['shift_count'],
        'roles' => $roles,
        'created_at' => $dept['created_at'],
        'updated_at' => $dept['updated_at']
    ];
}

echo json_encode([
    'success' => true,
    'departments' => $departments,
    'tenant_id' => $tenant_id,
    'total_departments' => count($departments)
]);
?>