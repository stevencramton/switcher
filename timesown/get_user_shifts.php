<?php
session_start();
ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
    include '../../templates/functions.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['action'])) {
    $input = $_POST;
}

if (!isset($input['action'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action is required']);
    exit();
}

$action = $input['action'];
$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];

switch ($action) {
    case 'get_user_future_shifts':
        getUserFutureShifts($dbc, $user_id, $input);
        break;
    
    case 'get_user_departments':
        getUserDepartments($dbc, $user_id, $input);
        break;
    
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getUserFutureShifts($dbc, $user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

    // Verify user access to tenant
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }

	$shifts_query = "
        SELECT 
            s.id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status,
            s.assigned_user_id,
            s.public_notes,
            d.name as department_name,
            jr.name as role_name,
            CASE 
                WHEN st.id IS NOT NULL THEN 1 
                ELSE 0 
            END as on_tradeboard
        FROM to_shifts s
        INNER JOIN to_departments d ON s.department_id = d.id
        INNER JOIN to_job_roles jr ON s.job_role_id = jr.id
        LEFT JOIN to_shift_trades st ON s.id = st.shift_id 
            AND st.status IN ('placed_on_tradeboard', 'pending_approval', 'approved')
        WHERE s.tenant_id = ? 
            AND s.assigned_user_id = ? 
            AND s.assigned_user_id IS NOT NULL
            AND s.status IN ('scheduled', 'confirmed')
            AND s.shift_date > CURDATE()
            AND s.shift_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY s.shift_date ASC, s.start_time ASC
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'ii', $tenant_id, $user_id);
    mysqli_stmt_execute($stmt);
    $shifts_result = mysqli_stmt_get_result($stmt);

    $shifts = [];
    while ($row = mysqli_fetch_assoc($shifts_result)) {
       	if ($row['assigned_user_id'] != $user_id) {
            continue;
        }
        
        $shifts[] = [
            'id' => intval($row['id']),
            'shift_date' => $row['shift_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'status' => $row['status'],
            'assigned_user_id' => intval($row['assigned_user_id']),
            'public_notes' => $row['public_notes'],
            'department_name' => $row['department_name'],
            'role_name' => $row['role_name'],
            'on_tradeboard' => (bool)$row['on_tradeboard']
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'shifts' => $shifts
    ]);
}

function getUserDepartments($dbc, $user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

   	$departments_query = "
        SELECT DISTINCT
            d.id as department_id,
            d.name as department_name,
            jr.id as role_id,
            jr.name as role_name
        FROM to_user_department_roles udr
        INNER JOIN to_departments d ON udr.department_id = d.id
        INNER JOIN to_job_roles jr ON udr.job_role_id = jr.id
        WHERE udr.user_id = ? 
            AND d.tenant_id = ? 
            AND udr.active = 1
            AND d.active = 1
            AND jr.active = 1
        ORDER BY d.name, jr.name
    ";
    
    $stmt = mysqli_prepare($dbc, $departments_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $departments_result = mysqli_stmt_get_result($stmt);

    $departments = [];
    while ($row = mysqli_fetch_assoc($departments_result)) {
        $departments[] = [
            'department_id' => intval($row['department_id']),
            'department_name' => $row['department_name'],
            'role_id' => intval($row['role_id']),
            'role_name' => $row['role_name']
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'departments' => $departments
    ]);
}
?>