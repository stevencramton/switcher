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
$schedule_type = isset($input['type']) ? $input['type'] : 'daily';
$date = isset($input['date']) ? $input['date'] : date('Y-m-d');
$week_start = isset($input['week_start']) ? $input['week_start'] : null;

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
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $actual_user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
        exit();
    }
}

$target_user_query = "
    SELECT u.id, u.first_name, u.last_name, u.display_name 
    FROM users u
    INNER JOIN to_user_tenants ut ON u.id = ut.user_id
    WHERE u.id = ? AND ut.tenant_id = ? AND u.account_delete = 0
";
$stmt = mysqli_prepare($dbc, $target_user_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$target_user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($target_user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Target user not found or not assigned to this tenant']);
    exit();
}

$target_user = mysqli_fetch_assoc($target_user_result);

try {
    if ($schedule_type === 'daily') {
        $shifts = getDailySchedule($user_id, $date, $tenant_id, $dbc);
    } else {
        $shifts = getWeeklySchedule($user_id, $week_start, $tenant_id, $dbc);
    }
    
    echo json_encode([
        'success' => true,
        'shifts' => $shifts,
        'type' => $schedule_type,
        'date' => $date,
        'week_start' => $week_start,
        'user_id' => $user_id,
        'user_name' => trim($target_user['first_name'] . ' ' . $target_user['last_name']),
        'user_display_name' => $target_user['display_name']
    ]);
    
} catch (Exception $e) {
    // Log the detailed error server-side for debugging
    error_log('Employee schedule query error (User ID: ' . $user_id . ', Tenant ID: ' . $tenant_id . ', Type: ' . $schedule_type . '): ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while retrieving schedule data. Please try again or contact support.'
    ]);
}

function getDailySchedule($user_id, $date, $tenant_id, $dbc) {
    $query = "
        SELECT 
            s.id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.public_notes,
            s.private_notes,
            s.status,
            d.name as department_name,
            d.color as department_color,
            jr.name as role_name,
            jr.color as role_color,
            s.department_id,
            s.job_role_id
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
        WHERE s.assigned_user_id = ? 
            AND s.shift_date = ? 
            AND s.tenant_id = ?
        ORDER BY s.start_time ASC
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'isi', $user_id, $date, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $shifts = [];
    while ($row = mysqli_fetch_assoc($result)) {
      	$row['start_time'] = substr($row['start_time'], 0, 5);
        $row['end_time'] = substr($row['end_time'], 0, 5);
        $shifts[] = $row;
    }
    
    return $shifts;
}

function getWeeklySchedule($user_id, $week_start, $tenant_id, $dbc) {
	if (!$week_start) {
        $week_start = date('Y-m-d', strtotime('monday this week'));
    }
    
	$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
    
    $query = "
        SELECT 
            s.id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.public_notes,
            s.private_notes,
            s.status,
            d.name as department_name,
            d.color as department_color,
            jr.name as role_name,
            jr.color as role_color,
            s.department_id,
            s.job_role_id,
            DAYOFWEEK(s.shift_date) as day_of_week
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
        WHERE s.assigned_user_id = ? 
            AND s.shift_date BETWEEN ? AND ?
            AND s.tenant_id = ?
        ORDER BY s.shift_date ASC, s.start_time ASC
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'issi', $user_id, $week_start, $week_end, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $shifts = [];
    while ($row = mysqli_fetch_assoc($result)) {
     	$row['start_time'] = substr($row['start_time'], 0, 5);
        $row['end_time'] = substr($row['end_time'], 0, 5);
      	$row['date'] = $row['shift_date'];
        $shifts[] = $row;
    }
    
    return $shifts;
}
?>