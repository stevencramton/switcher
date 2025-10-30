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

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['user_id'])) {
    $input = $_POST;
}

if (!$input && isset($_GET['user_id'])) {
    $input = $_GET;
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
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? 
                           AND tenant_id = ? AND active = 1";
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
    $time_prefs_query = "
        SELECT 
            day_of_week,
            time_slot,
            preference_level
        FROM to_user_time_preferences
        WHERE user_id = ? 
        AND tenant_id = ?
        ORDER BY day_of_week, time_slot
    ";
    
    $stmt = mysqli_prepare($dbc, $time_prefs_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $time_preferences = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $time_preferences[] = [
            'day_of_week' => intval($row['day_of_week']),
            'time_slot' => substr($row['time_slot'], 0, 5),
            'preference_level' => $row['preference_level']
        ];
    }
    mysqli_stmt_close($stmt);
    
    $availability_query = "
        SELECT 
            day_of_week,
            start_time,
            end_time,
            preference_level,
            notes
        FROM to_user_availability
        WHERE user_id = ? 
        AND tenant_id = ?
        AND effective_date <= CURDATE()
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY day_of_week, start_time
    ";
    
    $stmt2 = mysqli_prepare($dbc, $availability_query);
    if (!$stmt2) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt2, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    
    $availability_ranges = [];
    while ($row = mysqli_fetch_assoc($result2)) {
     	$start_time = substr($row['start_time'], 0, 5);
        $end_time = substr($row['end_time'], 0, 5);
        
        $availability_ranges[] = [
            'day_of_week' => intval($row['day_of_week']),
            'start_time' => date('g:i A', strtotime($start_time)),
            'end_time' => date('g:i A', strtotime($end_time)),
            'preference_level' => $row['preference_level'],
            'notes' => $row['notes']
        ];
    }
    mysqli_stmt_close($stmt2);
    
	$stats_query = "
        SELECT 
            COUNT(*) as total_shifts,
            SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as total_hours
        FROM to_shifts 
        WHERE assigned_user_id = ? 
        AND tenant_id = ?
        AND shift_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status IN ('scheduled', 'completed')
    ";
    
    $stmt3 = mysqli_prepare($dbc, $stats_query);
    mysqli_stmt_bind_param($stmt3, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt3);
    $stats_result = mysqli_stmt_get_result($stmt3);
    $stats = mysqli_fetch_assoc($stats_result);
    mysqli_stmt_close($stmt3);
    
    echo json_encode([
        'success' => true,
        'time_preferences' => $time_preferences,
        'availability_ranges' => $availability_ranges,
        'stats' => [
            'shifts_this_month' => $stats['total_shifts'] ?: 0,
            'hours_this_month' => $stats['total_hours'] ?: 0
        ],
        'user_id' => $user_id,
        'tenant_id' => $tenant_id,
        'has_detailed_prefs' => !empty($time_preferences),
        'has_availability_ranges' => !empty($availability_ranges)
    ]);
    
} catch (Exception $e) {
    // Log the detailed error server-side for debugging
    error_log('Employee preferences query error (User ID: ' . $user_id . ', Tenant ID: ' . $tenant_id . '): ' . $e->getMessage());
    
	http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving employee preferences. Please try again or contact support.'
    ]);
}
?>