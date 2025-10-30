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

if (!isset($_GET['user_id']) || !isset($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID and Tenant ID are required']);
    exit();
}

$user_id = (int)$_GET['user_id'];
$tenant_id = (int)$_GET['tenant_id'];

try {
	$user_query = "
        SELECT u.id, u.first_name, u.last_name, u.display_name, u.profile_pic
        FROM users u
        WHERE u.id = ? AND u.account_delete = 0
    ";
    
    $stmt = mysqli_prepare($dbc, $user_query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($user_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $user_data = mysqli_fetch_assoc($user_result);
    
	$assignment_query = "
        SELECT ut.id as assignment_id, ut.is_primary, ut.active, ut.created_at
        FROM to_user_tenants ut
        WHERE ut.user_id = ? AND ut.tenant_id = ? AND ut.active = 1
    ";
    
    $stmt = mysqli_prepare($dbc, $assignment_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $assignment_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($assignment_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'User is not assigned to this tenant']);
        exit();
    }
    
    $assignment_data = mysqli_fetch_assoc($assignment_result);
    
	$roles_query = "
        SELECT udr.id, udr.department_id, udr.job_role_id, udr.is_primary,
               d.name as department_name, d.color as department_color,
               jr.name as role_name, jr.color as role_color
        FROM to_user_department_roles udr
        JOIN to_departments d ON udr.department_id = d.id
        JOIN to_job_roles jr ON udr.job_role_id = jr.id
        WHERE udr.user_id = ? AND d.tenant_id = ? AND udr.active = 1
        ORDER BY d.name, jr.name
    ";
    
    $stmt = mysqli_prepare($dbc, $roles_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $roles_result = mysqli_stmt_get_result($stmt);
    
    $current_assignments = [];
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $current_assignments[] = [
            'assignment_id' => $role['id'],
            'department_id' => $role['department_id'],
            'job_role_id' => $role['job_role_id'],
            'is_primary' => $role['is_primary'],
            'department_name' => $role['department_name'],
            'department_color' => $role['department_color'],
            'role_name' => $role['role_name'],
            'role_color' => $role['role_color']
        ];
    }
    
	$departments_query = "
        SELECT d.id, d.name, d.description, d.color, d.sort_order
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
   	 	$dept_roles_query = "
            SELECT id, name, description, color, sort_order
            FROM to_job_roles 
            WHERE department_id = ? AND active = 1
            ORDER BY sort_order, name
        ";
        $roles_stmt = mysqli_prepare($dbc, $dept_roles_query);
        mysqli_stmt_bind_param($roles_stmt, 'i', $dept['id']);
        mysqli_stmt_execute($roles_stmt);
        $dept_roles_result = mysqli_stmt_get_result($roles_stmt);
        
        $roles = [];
        while ($role = mysqli_fetch_assoc($dept_roles_result)) {
            $roles[] = $role;
        }
        
        $departments[] = [
            'id' => $dept['id'],
            'name' => $dept['name'],
            'description' => $dept['description'],
            'color' => $dept['color'],
            'sort_order' => $dept['sort_order'],
            'roles' => $roles
        ];
    }
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user_data['id'],
            'name' => trim($user_data['first_name'] . ' ' . $user_data['last_name']),
            'display_name' => $user_data['display_name'],
            'profile_pic' => $user_data['profile_pic'] ?: 'img/profile_pic/avatar.png'
        ],
        'assignment' => [
            'id' => $assignment_data['assignment_id'],
            'is_primary' => $assignment_data['is_primary'],
            'created_at' => $assignment_data['created_at']
        ],
        'current_assignments' => $current_assignments,
        'departments' => $departments
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving user assignment: ' . $e->getMessage()
    ]);
}
?>