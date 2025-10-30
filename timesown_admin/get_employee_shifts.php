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
    // Log the detailed error server-side
    error_log('Database connection failed: ' . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Unable to connect to the database. Please try again or contact support.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input && isset($_POST['user_id'])) {
    $input = $_POST;
}

if (!isset($input['user_id']) || !is_numeric($input['user_id']) || 
    !isset($input['tenant_id']) || !is_numeric($input['tenant_id'])) {
    
	http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid user_id and tenant_id are required'
    ]);
    exit;
}

$user_id = intval($input['user_id']);
$tenant_id = intval($input['tenant_id']);
$switch_id = $_SESSION['switch_id'];
$current_user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $current_user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$current_user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($current_user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Current user not found']);
    exit();
}

$current_user_data = mysqli_fetch_assoc($current_user_result);
$actual_user_id = $current_user_data['id'];

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
	$shifts_query = "
        SELECT 
            s.id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status,
            s.public_notes,
            s.shift_color,
            d.name as department_name,
            d.color as department_color,
            jr.name as role_name,
            jr.color as role_color
        FROM to_shifts s
        INNER JOIN to_departments d ON s.department_id = d.id
        INNER JOIN to_job_roles jr ON s.job_role_id = jr.id
        WHERE s.assigned_user_id = ?
        AND d.tenant_id = ?
        AND s.shift_date >= CURDATE()
        AND s.status IN ('scheduled', 'pending', 'completed')
        ORDER BY s.shift_date ASC, s.start_time ASC
        LIMIT 20
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $shifts = [];
    while ($row = mysqli_fetch_assoc($result)) {
     	$start_time = date('g:i A', strtotime($row['start_time']));
        $end_time = date('g:i A', strtotime($row['end_time']));
     	$start_timestamp = strtotime($row['shift_date'] . ' ' . $row['start_time']);
        $end_timestamp = strtotime($row['shift_date'] . ' ' . $row['end_time']);
        $duration_hours = round(($end_timestamp - $start_timestamp) / 3600, 1);
        
        $shifts[] = [
            'id' => intval($row['id']),
            'shift_date' => $row['shift_date'],
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => ucfirst($row['status']),
            'department_name' => $row['department_name'],
            'department_color' => $row['department_color'],
            'role_name' => $row['role_name'],
            'role_color' => $row['role_color'],
            'shift_color' => $row['shift_color'],
            'duration_hours' => $duration_hours,
            'public_notes' => $row['public_notes'],
            'formatted_date' => date('l, M j, Y', strtotime($row['shift_date']))
        ];
    }
    
    mysqli_stmt_close($stmt);
    
 	$stats_query = "
        SELECT 
            COUNT(*) as total_upcoming_shifts,
            SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time))/3600) as total_hours_upcoming,
            COUNT(CASE WHEN s.shift_date = CURDATE() THEN 1 END) as shifts_today
        FROM to_shifts s
        INNER JOIN to_departments d ON s.department_id = d.id
        WHERE s.assigned_user_id = ?
        AND d.tenant_id = ?
        AND s.shift_date >= CURDATE()
        AND s.status IN ('scheduled', 'pending')
    ";
    
    $stats_stmt = mysqli_prepare($dbc, $stats_query);
    mysqli_stmt_bind_param($stats_stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $stats = mysqli_fetch_assoc($stats_result);
    mysqli_stmt_close($stats_stmt);
    
    echo json_encode([
        'success' => true,
        'shifts' => $shifts,
        'statistics' => [
            'total_upcoming_shifts' => intval($stats['total_upcoming_shifts']),
            'total_hours_upcoming' => round(floatval($stats['total_hours_upcoming']), 1),
            'shifts_today' => intval($stats['shifts_today'])
        ]
    ]);
    
} catch (Exception $e) {
    // Log the detailed error server-side for debugging
    error_log('Employee shifts query error (User ID: ' . $user_id . ', Tenant ID: ' . $tenant_id . '): ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving shift data. Please try again or contact support.'
    ]);
}
?>