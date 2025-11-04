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

if (!checkRole('timesown_admin')) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['action'])) {
    $input = $_POST;
}

if (!isset($input['action']) || !isset($input['tenant_id'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action and tenant_id are required']);
    exit();
}

$action = $input['action'];
$tenant_id = intval($input['tenant_id']);

if ($tenant_id <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid tenant_id']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);

if (!$stmt) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

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

if (!checkRole('admin_developer')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    
    if (!$stmt) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

ob_end_clean();

switch ($action) {
    case 'get_schedule_info':
        try {
            $schedule_info = getScheduleInfo($dbc, $tenant_id);
            echo json_encode(['success' => true, 'data' => $schedule_info]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error retrieving schedule information']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getScheduleInfo($dbc, $tenant_id) {
    $stats_query = "
        SELECT 
            COUNT(*) as total_shifts,
            MIN(shift_date) as first_shift_date,
            MAX(shift_date) as last_shift_date,
            COUNT(DISTINCT shift_date) as days_with_shifts,
            AVG(TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600) as avg_shift_length_hours,
            SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600) as total_hours_scheduled
        FROM to_shifts 
        WHERE tenant_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $stats_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($result);
    
    $info = [
        'total_shifts' => intval($stats['total_shifts']),
        'first_shift_date' => $stats['first_shift_date'],
        'last_shift_date' => $stats['last_shift_date'],
        'days_with_shifts' => intval($stats['days_with_shifts']),
        'avg_shift_length' => round(floatval($stats['avg_shift_length_hours']), 1),
        'total_hours_scheduled' => round(floatval($stats['total_hours_scheduled']), 1),
        'shifts_per_week' => 0,
        'shifts_per_month' => 0,
        'shifts_per_year' => 0,
        'total_days' => 0,
        'total_weeks' => 0,
        'total_months' => 0,
        'schedule_coverage' => 0,
        'avg_hours_per_week' => 0
    ];
    
    if ($stats['total_shifts'] > 0 && $stats['first_shift_date'] && $stats['last_shift_date']) {
        $first_date = new DateTime($stats['first_shift_date']);
        $last_date = new DateTime($stats['last_shift_date']);
        
       	$interval = $first_date->diff($last_date);
        $total_days = $interval->days + 1;
        
        $info['total_days'] = $total_days;
        $info['total_weeks'] = round($total_days / 7, 1);
        $info['total_months'] = round($total_days / 30.44, 1);
        
       	if ($total_days > 0) {
            $info['schedule_coverage'] = round(($stats['days_with_shifts'] / $total_days) * 100, 1);
        }
        
       	if ($info['total_weeks'] > 0) {
            $info['shifts_per_week'] = round($stats['total_shifts'] / $info['total_weeks'], 1);
            $info['avg_hours_per_week'] = round($stats['total_hours_scheduled'] / $info['total_weeks'], 1);
        }
        
        if ($info['total_months'] > 0) {
            $info['shifts_per_month'] = round($stats['total_shifts'] / $info['total_months'], 1);
        }
        
       	if ($info['shifts_per_week'] > 0) {
            $info['shifts_per_year'] = round($info['shifts_per_week'] * 52);
        }
    }
    
    return $info;
}
?>