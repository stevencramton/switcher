<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_GET;
}

$employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;
$tenant_id = isset($input['tenant_id']) ? (int)$input['tenant_id'] : 0;
$start_date = isset($input['start_date']) ? $input['start_date'] : null;
$end_date = isset($input['end_date']) ? $input['end_date'] : null;

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

if (!$start_date) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
}
if (!$end_date) {
    $end_date = date('Y-m-d');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid start date format']);
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid end date format']);
    exit();
}

try {
	$attendance_query = "
        SELECT 
            s.id as shift_id,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status as shift_status,
            s.attendance_status,
            s.attendance_notes,
            s.attendance_recorded_at,
            sa.minutes_late,
            sa.attendance_notes as detailed_notes,
            sa.recorded_at as detailed_recorded_at,
            d.name as department_name,
            jr.name as role_name,
            COALESCE(sa.attendance_status, s.attendance_status) as final_status,
            COALESCE(sa.attendance_notes, s.attendance_notes) as final_notes,
            COALESCE(sa.recorded_at, s.attendance_recorded_at) as final_recorded_at,
            recorder.first_name as recorded_by_first_name,
            recorder.last_name as recorded_by_last_name
        FROM to_shifts s
        LEFT JOIN to_shift_attendance sa ON s.id = sa.shift_id
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
        LEFT JOIN users recorder ON COALESCE(sa.recorded_by, s.attendance_recorded_by) = recorder.id
        WHERE s.assigned_user_id = ?
        AND s.tenant_id = ?
        AND s.shift_date BETWEEN ? AND ?
        AND (s.attendance_status IS NOT NULL OR sa.attendance_status IS NOT NULL)
        ORDER BY s.shift_date DESC, s.start_time DESC
    ";
    
    $stmt = mysqli_prepare($dbc, $attendance_query);
    mysqli_stmt_bind_param($stmt, 'iiss', $employee_id, $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $attendance_result = mysqli_stmt_get_result($stmt);
    
    $attendance_records = [];
    $statistics = [
        'on_time' => 0,
        'tardy' => 0,
        'late_arrival' => 0,
        'very_late_absent' => 0,
        'no_call_no_show' => 0,
        'planned_out' => 0,
        'dropped_shift' => 0,
        'total_shifts' => 0,
        'total_minutes_late' => 0,
        'late_instances' => 0
    ];
    
    while ($row = mysqli_fetch_assoc($attendance_result)) {
        $statistics['total_shifts']++;
        
        $status = $row['final_status'];
        if (isset($statistics[$status])) {
            $statistics[$status]++;
        }
        
        // Track minutes late for statistics
        if ($row['minutes_late'] && in_array($status, ['tardy', 'late_arrival', 'very_late_absent'])) {
            $statistics['total_minutes_late'] += $row['minutes_late'];
            $statistics['late_instances']++;
        }
        
        $attendance_records[] = [
            'shift_id' => $row['shift_id'],
            'shift_date' => $row['shift_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'shift_status' => $row['shift_status'],
            'department_name' => $row['department_name'],
            'role_name' => $row['role_name'],
            'attendance_status' => $status,
            'minutes_late' => $row['minutes_late'],
            'attendance_notes' => $row['final_notes'],
            'recorded_at' => $row['final_recorded_at'],
            'recorded_by_name' => trim($row['recorded_by_first_name'] . ' ' . $row['recorded_by_last_name'])
        ];
    }
    
	$on_time_rate = $statistics['total_shifts'] > 0 
        ? round(($statistics['on_time'] / $statistics['total_shifts']) * 100, 1) 
        : 0;
        
    $avg_minutes_late = $statistics['late_instances'] > 0 
        ? round($statistics['total_minutes_late'] / $statistics['late_instances'], 1) 
        : 0;
    
	$employee_query = "SELECT first_name, last_name, display_name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $employee_query);
    mysqli_stmt_bind_param($stmt, 'i', $employee_id);
    mysqli_stmt_execute($stmt);
    $employee_result = mysqli_stmt_get_result($stmt);
    $employee_data = mysqli_fetch_assoc($employee_result);
    
    $employee_name = $employee_data['display_name'] ?: 
        trim($employee_data['first_name'] . ' ' . $employee_data['last_name']);
    
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'employee_name' => $employee_name,
        'date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ],
        'statistics' => array_merge($statistics, [
            'on_time_rate' => $on_time_rate,
            'avg_minutes_late' => $avg_minutes_late
        ]),
        'attendance_records' => $attendance_records,
        'total_records' => count($attendance_records)
    ]);
    
} catch (Exception $e) {
    // Log the detailed error server-side for debugging
    error_log('Employee attendance query error (Employee ID: ' . $employee_id . ', Tenant ID: ' . $tenant_id . '): ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving attendance data. Please try again or contact support.'
    ]);
}
?>