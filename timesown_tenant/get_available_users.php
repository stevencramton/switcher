<?php
session_start();
header('Content-Type: application/json');
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

try {
    include '../../mysqli_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$base_query = "
    SELECT u.id, u.switch_id, u.first_name, u.last_name, u.display_name, 
           u.display_agency, u.profile_pic, u.last_activity
    FROM users u
    WHERE u.account_delete = 0
    AND u.id NOT IN (
        SELECT ut.user_id 
        FROM to_user_tenants ut 
        WHERE ut.tenant_id = ? AND ut.active = 1
    )
";

$params = [$tenant_id];
$types = 'i';

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $base_query .= " AND (
        u.first_name LIKE ? OR 
        u.last_name LIKE ? OR 
        u.display_name LIKE ? OR
        CONCAT(u.first_name, ' ', u.last_name) LIKE ?
    )";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= 'ssss';
}

$base_query .= " ORDER BY u.first_name, u.last_name LIMIT 50";

$stmt = mysqli_prepare($dbc, $base_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $assignments_query = "
        SELECT t.id, t.name, ut.is_primary
        FROM to_user_tenants ut
        JOIN to_tenants t ON ut.tenant_id = t.id
        WHERE ut.user_id = ? AND ut.active = 1
        ORDER BY ut.is_primary DESC, t.name LIMIT 5
    ";
    
    $assignments_stmt = mysqli_prepare($dbc, $assignments_query);
    mysqli_stmt_bind_param($assignments_stmt, 'i', $row['id']);
    mysqli_stmt_execute($assignments_stmt);
    $assignments_result = mysqli_stmt_get_result($assignments_stmt);
    
    $current_tenants = [];
    while ($assignment = mysqli_fetch_assoc($assignments_result)) {
        $current_tenants[] = [
            'id' => $assignment['id'],
            'name' => $assignment['name'],
            'is_primary' => (bool)$assignment['is_primary']
        ];
    }
    
    $users[] = [
        'id' => $row['id'],
        'switch_id' => $row['switch_id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'display_name' => $row['display_name'],
        'agency' => $row['display_agency'],
        'profile_pic' => $row['profile_pic'] ?: 'img/profile_pic/avatar.png',
        'last_activity' => $row['last_activity'],
        'current_tenants' => $current_tenants
    ];
}

$departments = [];
$dept_query = "
    SELECT d.id, d.name, d.color
    FROM to_departments d
    WHERE d.tenant_id = ? AND d.active = 1
    ORDER BY d.sort_order, d.name
";

$stmt = mysqli_prepare($dbc, $dept_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);

while ($dept = mysqli_fetch_assoc($dept_result)) {
    $roles_query = "
        SELECT id, name, color
        FROM to_job_roles 
        WHERE department_id = ? AND active = 1
        ORDER BY sort_order, name
    ";
	
    $roles_stmt = mysqli_prepare($dbc, $roles_query);
    mysqli_stmt_bind_param($roles_stmt, 'i', $dept['id']);
    mysqli_stmt_execute($roles_stmt);
    $roles_result = mysqli_stmt_get_result($roles_stmt);
    
    $roles = [];
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $role;
    }
    
    $departments[] = [
        'id' => $dept['id'],
        'name' => $dept['name'],
        'color' => $dept['color'],
        'roles' => $roles
    ];
}

echo json_encode([
    'success' => true,
    'users' => $users,
    'departments' => $departments,
    'total_found' => count($users)
]);
?>