<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
    include '../../templates/functions.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['tenant_id'])) {
    $input = $_POST;
}

if (!$input && isset($_GET['tenant_id'])) {
    $input = $_GET;
}

if (!isset($input['tenant_id']) || !is_numeric($input['tenant_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid tenant_id is required',
        'debug' => [
            'received_input' => $input,
            'post_data' => $_POST,
            'get_data' => $_GET
        ]
    ]);
    exit;
}

$tenant_id = intval($input['tenant_id']);
$report_date = isset($input['report_date']) ? $input['report_date'] : date('Y-m-d');
$report_type = isset($input['report_type']) ? $input['report_type'] : 'both';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $report_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit();
}

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
    $access_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

try {
 	$daily_date = $report_date;
 	$date_obj = new DateTime($report_date);
    $week_start = clone $date_obj;
	$day_of_week = $date_obj->format('N');
    if ($day_of_week != 1) {
        $week_start->modify('-' . ($day_of_week - 1) . ' days');
    }
    
    $week_end = clone $week_start;
    $week_end->modify('+6 days');
    
    $response = [
        'success' => true,
        'report_date' => $report_date,
        'week_start' => $week_start->format('Y-m-d'),
        'week_end' => $week_end->format('Y-m-d')
    ];
    
 	if ($report_type === 'daily' || $report_type === 'both') {
        $daily_metrics = getDailyStaffingMetrics($dbc, $tenant_id, $daily_date);
        $response['daily_metrics'] = $daily_metrics;
    }
    
	if ($report_type === 'weekly' || $report_type === 'both') {
        $weekly_metrics = getWeeklyStaffingMetrics($dbc, $tenant_id, $week_start->format('Y-m-d'), $week_end->format('Y-m-d'));
        $response['weekly_metrics'] = $weekly_metrics;
    }
    
 	if ($report_type === 'both' || $report_type === 'daily') {
        $department_breakdown = getDepartmentBreakdown($dbc, $tenant_id, $daily_date);
        $response['department_breakdown'] = $department_breakdown;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error generating reports: ' . $e->getMessage()]);
    exit();
}

function getDailyStaffingMetrics($dbc, $tenant_id, $date) {
    $query = "
        SELECT 
            COUNT(*) as total_shifts,
            SUM(CASE WHEN (status = 'open' OR assigned_user_id IS NULL) THEN 1 ELSE 0 END) as open_shifts,
            SUM(CASE WHEN (status != 'open' AND assigned_user_id IS NOT NULL) THEN 1 ELSE 0 END) as filled_shifts
        FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date = ? 
        AND status IN ('scheduled', 'open', 'pending')
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'is', $tenant_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    $total_shifts = (int)$data['total_shifts'];
    $open_shifts = (int)$data['open_shifts'];
    $filled_shifts = (int)$data['filled_shifts'];
    $filled_percentage = $total_shifts > 0 ? round(($filled_shifts / $total_shifts) * 100, 1) : 0;
    $open_percentage = $total_shifts > 0 ? round(($open_shifts / $total_shifts) * 100, 1) : 0;
    
    return [
        'total_shifts' => $total_shifts,
        'open_shifts' => $open_shifts,
        'filled_shifts' => $filled_shifts,
        'filled_percentage' => $filled_percentage,
        'open_percentage' => $open_percentage
    ];
}

function getWeeklyStaffingMetrics($dbc, $tenant_id, $start_date, $end_date) {
    $query = "
        SELECT 
            COUNT(*) as total_shifts,
            SUM(CASE WHEN (status = 'open' OR assigned_user_id IS NULL) THEN 1 ELSE 0 END) as open_shifts,
            SUM(CASE WHEN (status != 'open' AND assigned_user_id IS NOT NULL) THEN 1 ELSE 0 END) as filled_shifts
        FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date BETWEEN ? AND ?
        AND status IN ('scheduled', 'open', 'pending')
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    $total_shifts = (int)$data['total_shifts'];
    $open_shifts = (int)$data['open_shifts'];
    $filled_shifts = (int)$data['filled_shifts'];
 	$filled_percentage = $total_shifts > 0 ? round(($filled_shifts / $total_shifts) * 100, 1) : 0;
    $open_percentage = $total_shifts > 0 ? round(($open_shifts / $total_shifts) * 100, 1) : 0;
    
    return [
        'total_shifts' => $total_shifts,
        'open_shifts' => $open_shifts,
        'filled_shifts' => $filled_shifts,
        'filled_percentage' => $filled_percentage,
        'open_percentage' => $open_percentage
    ];
}

function getDepartmentBreakdown($dbc, $tenant_id, $date) {
    $query = "
        SELECT 
            d.id as department_id,
            d.name as department_name,
            d.color as department_color,
            COUNT(s.id) as total_shifts,
            SUM(CASE WHEN (s.status = 'open' OR s.assigned_user_id IS NULL) THEN 1 ELSE 0 END) as open_shifts,
            SUM(CASE WHEN (s.status != 'open' AND s.assigned_user_id IS NOT NULL) THEN 1 ELSE 0 END) as filled_shifts
        FROM to_departments d
        LEFT JOIN to_shifts s ON d.id = s.department_id 
            AND s.tenant_id = ? 
            AND s.shift_date = ?
            AND s.status IN ('scheduled', 'open', 'pending')
        WHERE d.tenant_id = ? AND d.active = 1
        GROUP BY d.id, d.name, d.color
        HAVING total_shifts > 0
        ORDER BY d.sort_order, d.name
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'isi', $tenant_id, $date, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $total = (int)$row['total_shifts'];
        $open = (int)$row['open_shifts'];
        $filled = (int)$row['filled_shifts'];
        
        $departments[] = [
            'department_id' => (int)$row['department_id'],
            'department_name' => $row['department_name'],
            'department_color' => $row['department_color'] ?: '#007bff',
            'total_shifts' => $total,
            'open_shifts' => $open,
            'filled_shifts' => $filled,
            'filled_percentage' => $total > 0 ? round(($filled / $total) * 100, 1) : 0,
            'open_percentage' => $total > 0 ? round(($open / $total) * 100, 1) : 0
        ];
    }
    
    return $departments;
}

?>