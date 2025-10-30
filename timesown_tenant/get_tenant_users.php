<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!checkRole('timesown_tenant')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['tenant_id']) || empty($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

try {
    include '../../mysqli_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$tenant_id = (int)$_GET['tenant_id'];
$users_query = "
    SELECT u.id, u.switch_id, u.first_name, u.last_name, u.display_name, 
           u.display_agency, u.profile_pic, u.last_activity,
           ut.is_primary, ut.active as assignment_active, ut.created_at as assigned_date
    FROM users u
    JOIN to_user_tenants ut ON u.id = ut.user_id
    WHERE ut.tenant_id = ? AND ut.active = 1 AND u.account_delete = 0
    ORDER BY ut.is_primary DESC, u.first_name, u.last_name
";

$stmt = mysqli_prepare($dbc, $users_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $assignments_query = "
        SELECT udr.id as assignment_id, udr.department_id, udr.job_role_id, udr.is_primary,
               d.name as department_name, d.color as department_color,
               jr.name as role_name, jr.color as role_color
        FROM to_user_department_roles udr
        JOIN to_departments d ON udr.department_id = d.id
        JOIN to_job_roles jr ON udr.job_role_id = jr.id
        WHERE udr.user_id = ? AND d.tenant_id = ? AND udr.active = 1
        ORDER BY d.name, jr.name
    ";
    
    $assignments_stmt = mysqli_prepare($dbc, $assignments_query);
    mysqli_stmt_bind_param($assignments_stmt, 'ii', $row['id'], $tenant_id);
    mysqli_stmt_execute($assignments_stmt);
    $assignments_result = mysqli_stmt_get_result($assignments_stmt);
    
    $departments = [];
    $roles = [];
    $departments_seen = [];
    $roles_seen = [];
    
    while ($assignment = mysqli_fetch_assoc($assignments_result)) {
        // Add department if not already added
        if (!in_array($assignment['department_id'], $departments_seen)) {
            $departments[] = [
                'id' => $assignment['department_id'],
                'name' => $assignment['department_name'],
                'color' => $assignment['department_color']
            ];
            $departments_seen[] = $assignment['department_id'];
        }
        
        if (!in_array($assignment['job_role_id'], $roles_seen)) {
            $roles[] = [
                'id' => $assignment['job_role_id'],
                'name' => $assignment['role_name'],
                'color' => $assignment['role_color']
            ];
            $roles_seen[] = $assignment['job_role_id'];
        }
    }
    
    $shifts_query = "
        SELECT COUNT(*) as shift_count
        FROM to_shifts s
        WHERE s.assigned_user_id = ? AND s.tenant_id = ? 
        AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $shifts_stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($shifts_stmt, 'ii', $row['id'], $tenant_id);
    mysqli_stmt_execute($shifts_stmt);
    $shifts_result = mysqli_stmt_get_result($shifts_stmt);
    $shifts_data = mysqli_fetch_assoc($shifts_result);
    
    $users[] = [
        'id' => $row['id'],
        'switch_id' => $row['switch_id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'display_name' => $row['display_name'],
        'agency' => $row['display_agency'],
        'profile_pic' => $row['profile_pic'] ?: 'img/profile_pic/avatar.png',
        'last_activity' => $row['last_activity'],
        'is_primary' => (bool)$row['is_primary'],
        'assignment_active' => (bool)$row['assignment_active'],
        'assigned_date' => $row['assigned_date'],
        'recent_shifts' => (int)$shifts_data['shift_count'],
        'departments' => $departments,
        'roles' => $roles
    ];
}

echo json_encode([
    'success' => true,
    'users' => $users
]);
?>