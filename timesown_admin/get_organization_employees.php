<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
    include '../../templates/functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input && isset($_POST['tenant_id'])) {
    $input = $_POST;
}

if (!isset($input['tenant_id']) || !is_numeric($input['tenant_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid tenant_id is required'
    ]);
    exit;
}

$tenant_id = intval($input['tenant_id']);
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
$actual_user_id = $user_data['id'];

if (!checkRole('timesown_admin')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $access_stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($access_stmt, 'ii', $actual_user_id, $tenant_id);
    mysqli_stmt_execute($access_stmt);
    $access_result = mysqli_stmt_get_result($access_stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

try {
    $employees_query = "
        SELECT DISTINCT
            u.id,
            u.first_name,
            u.last_name,
            u.display_name,
			u.display_agency,
            u.user as username,
            u.profile_pic,
            u.cell,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            (
                SELECT COUNT(*) 
                FROM to_shifts s 
                INNER JOIN to_departments d ON s.department_id = d.id 
                WHERE s.assigned_user_id = u.id 
                AND d.tenant_id = ?
                AND s.shift_date >= CURDATE()
                AND s.status IN ('scheduled', 'pending')
            ) as total_shifts,
            GROUP_CONCAT(
                DISTINCT CONCAT(dept.name, ' - ', jr.name) 
                ORDER BY udr.is_primary DESC, dept.name 
                SEPARATOR ', '
            ) as department_roles,
            -- Check if user has availability set (either ranges or time preferences)
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM to_user_availability ua 
                    WHERE ua.user_id = u.id AND ua.tenant_id = ?
                    AND ua.effective_date <= CURDATE()
                    AND (ua.end_date IS NULL OR ua.end_date >= CURDATE())
                ) OR EXISTS (
                    SELECT 1 FROM to_user_time_preferences tp 
                    WHERE tp.user_id = u.id AND tp.tenant_id = ?
                ) 
                THEN 1 
                ELSE 0 
            END as has_availability_set,
            -- NEW: Check if user has notes set
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM to_user_availability ua 
                    WHERE ua.user_id = u.id AND ua.tenant_id = ?
                    AND ua.effective_date <= CURDATE()
                    AND (ua.end_date IS NULL OR ua.end_date >= CURDATE())
                    AND ua.notes IS NOT NULL 
                    AND ua.notes != ''
                ) 
                THEN 1 
                ELSE 0 
            END as has_notes
        FROM users u
        INNER JOIN to_user_tenants ut ON u.id = ut.user_id
        LEFT JOIN to_user_department_roles udr ON u.id = udr.user_id AND udr.active = 1
        LEFT JOIN to_departments dept ON udr.department_id = dept.id AND dept.tenant_id = ?
        LEFT JOIN to_job_roles jr ON udr.job_role_id = jr.id AND jr.active = 1
        WHERE ut.tenant_id = ? 
        AND ut.active = 1
        AND u.account_delete = 0
        GROUP BY u.id, u.first_name, u.last_name, u.display_name, u.user, u.profile_pic, u.cell
        ORDER BY u.display_name IS NULL, u.display_name, u.first_name, u.last_name
    ";
    
    $stmt = mysqli_prepare($dbc, $employees_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'iiiiii', $tenant_id, $tenant_id, $tenant_id, $tenant_id, $tenant_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $profile_pic = $row['profile_pic'];
        if ($profile_pic && !str_starts_with($profile_pic, 'http')) {
            if (str_starts_with($profile_pic, '/')) {
                $profile_pic = substr($profile_pic, 1);
            }
        }
        
        $employees[] = [
            'id' => intval($row['id']),
            'name' => trim($row['name']),
            'display_name' => $row['display_name'],
			'agency' => $row['display_agency'],
            'username' => $row['username'],
            'profile_pic' => $profile_pic ?: 'img/profile_pic/avatar.png',
            'cell' => $row['cell'],
            'total_shifts' => intval($row['total_shifts']),
            'department_roles' => $row['department_roles'] ?: 'No role assigned',
            'has_availability_set' => intval($row['has_availability_set']) === 1,
            'has_availability' => intval($row['has_availability_set']) === 1,
            'has_notes' => intval($row['has_notes']) === 1
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'total_count' => count($employees)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>