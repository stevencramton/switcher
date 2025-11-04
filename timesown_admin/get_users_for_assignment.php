<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : null;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$role_id = isset($_GET['role_id']) ? (int)$_GET['role_id'] : null;

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$base_query = "
    SELECT DISTINCT u.id, u.switch_id, u.first_name, u.last_name, u.display_name, 
           u.display_agency, u.profile_pic, u.last_activity
    FROM users u
    JOIN to_user_tenants ut ON u.id = ut.user_id
    WHERE ut.tenant_id = ? AND ut.active = 1 AND u.account_delete = 0
";

$params = [$tenant_id];
$types = 'i';

if ($department_id && $role_id) {
    $base_query .= "
        AND EXISTS (
            SELECT 1 FROM to_user_department_roles udr
            JOIN to_departments d ON udr.department_id = d.id
            WHERE udr.user_id = u.id 
            AND udr.department_id = ? 
            AND udr.job_role_id = ? 
            AND udr.active = 1
            AND d.tenant_id = ?
        )
    ";
    $params[] = $department_id;
    $params[] = $role_id;
    $params[] = $tenant_id;
    $types .= 'iii';
}

elseif ($role_id) {
    $base_query .= "
        AND EXISTS (
            SELECT 1 FROM to_user_department_roles udr
            JOIN to_departments d ON udr.department_id = d.id
            WHERE udr.user_id = u.id 
            AND udr.job_role_id = ? 
            AND udr.active = 1
            AND d.tenant_id = ?
        )
    ";
    $params[] = $role_id;
    $params[] = $tenant_id;
    $types .= 'ii';
}

elseif ($department_id) {
    $base_query .= "
        AND EXISTS (
            SELECT 1 FROM to_user_department_roles udr
            JOIN to_departments d ON udr.department_id = d.id
            WHERE udr.user_id = u.id 
            AND udr.department_id = ? 
            AND udr.active = 1
            AND d.tenant_id = ?
        )
    ";
    $params[] = $department_id;
    $params[] = $tenant_id;
    $types .= 'ii';
}

$base_query .= " ORDER BY u.display_name ASC, u.first_name ASC, u.last_name ASC";

$stmt = mysqli_prepare($dbc, $base_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

$stmt->bind_param($types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $role_info_query = "
        SELECT d.name as dept_name, d.color as dept_color,
               jr.name as role_name, jr.color as role_color
        FROM to_user_department_roles udr
        JOIN to_departments d ON udr.department_id = d.id
        JOIN to_job_roles jr ON udr.job_role_id = jr.id
        WHERE udr.user_id = ? AND d.tenant_id = ? AND udr.active = 1
    ";
    
    $role_stmt = mysqli_prepare($dbc, $role_info_query);
    mysqli_stmt_bind_param($role_stmt, 'ii', $row['id'], $tenant_id);
    mysqli_stmt_execute($role_stmt);
    $role_result = mysqli_stmt_get_result($role_stmt);
    
    $roles = [];
    while ($role = mysqli_fetch_assoc($role_result)) {
        $roles[] = [
            'department' => $role['dept_name'],
            'department_color' => $role['dept_color'],
            'role' => $role['role_name'],
            'role_color' => $role['role_color']
        ];
    }
    
    $availability_query = "
        SELECT COUNT(*) as has_availability
        FROM to_user_time_preferences 
        WHERE user_id = ? AND tenant_id = ?
    ";
    
    $avail_stmt = mysqli_prepare($dbc, $availability_query);
    mysqli_stmt_bind_param($avail_stmt, 'ii', $row['id'], $tenant_id);
    mysqli_stmt_execute($avail_stmt);
    $avail_result = mysqli_stmt_get_result($avail_stmt);
    $avail_data = mysqli_fetch_assoc($avail_result);
    $display_name = $row['display_name'] ?: trim($row['first_name'] . ' ' . $row['last_name']);
    
    $users[] = [
        'id' => $row['id'],
        'switch_id' => $row['switch_id'],
        'name' => $display_name,
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'agency' => $row['display_agency'],
        'profile_pic' => $row['profile_pic'] ?: 'img/profile_pic/avatar.png',
        'last_activity' => $row['last_activity'],
        'roles' => $roles,
        'has_availability' => $avail_data['has_availability'] > 0
    ];
}

echo json_encode([
    'success' => true,
    'users' => $users,
    'total_users' => count($users),
    'filters' => [
        'tenant_id' => $tenant_id,
        'department_id' => $department_id,
        'role_id' => $role_id
    ]
]);
?>