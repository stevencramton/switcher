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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_GET;
}

$employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;
$tenant_id = isset($input['tenant_id']) ? (int)$input['tenant_id'] : 0;
$start_date = isset($input['start_date']) ? $input['start_date'] : null;
$end_date = isset($input['end_date']) ? $input['end_date'] : null;

if (!$employee_id || !$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Employee ID and Tenant ID are required']);
    exit();
}

if (!$start_date) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
}
if (!$end_date) {
    $end_date = date('Y-m-d');
}

try {
    $employee_query = "SELECT first_name, last_name, display_name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $employee_query);
    mysqli_stmt_bind_param($stmt, 'i', $employee_id);
    mysqli_stmt_execute($stmt);
    $employee_result = mysqli_stmt_get_result($stmt);
    $employee_data = mysqli_fetch_assoc($employee_result);
    
    $employee_name = $employee_data['display_name'] ?: 
        trim($employee_data['first_name'] . ' ' . $employee_data['last_name']);
    
    $attendance_query = "
        SELECT 
            s.shift_date,
            s.start_time,
            s.end_time,
            s.status as shift_status,
            d.name as department_name,
            jr.name as role_name,
            COALESCE(sa.attendance_status, s.attendance_status) as attendance_status,
            sa.minutes_late,
            COALESCE(sa.attendance_notes, s.attendance_notes) as attendance_notes,
            COALESCE(sa.recorded_at, s.attendance_recorded_at) as recorded_at,
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
    $result = mysqli_stmt_get_result($stmt);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee_name) . '_' . $start_date . '_' . $end_date . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
	    'Employee Name',
	    'Date',
	    'Day of Week',
	    'Start Time',
	    'End Time',
	    'Department',
	    'Role',
	    'Attendance Status',
	    'Minutes Late',
	    'Attendance Notes',
	    'Recorded At',
	    'Recorded By'
	], ',', '"', '\\');
    
	$nyTimezone = new DateTimeZone('America/New_York');
    
	function formatTime12Hour($timeString) {
        if (!$timeString) return '';
        
        $time = DateTime::createFromFormat('H:i:s', $timeString);
        if (!$time) {
            $time = DateTime::createFromFormat('H:i', $timeString);
        }
        
        if ($time) {
            return $time->format('g:i A');
        }
        
        return $timeString;
    }
    
	while ($row = mysqli_fetch_assoc($result)) {
        $date = new DateTime($row['shift_date']);
        $dayOfWeek = $date->format('l');
    	$startTime = formatTime12Hour($row['start_time']);
        $endTime = formatTime12Hour($row['end_time']);
        
      	$recordedAt = '';
        if ($row['recorded_at']) {
            $recordedDateTime = new DateTime($row['recorded_at']);
            $recordedDateTime->setTimezone($nyTimezone);
            $recordedAt = $recordedDateTime->format('Y-m-d g:i A');
        }
            
        $recordedBy = trim($row['recorded_by_first_name'] . ' ' . $row['recorded_by_last_name']);
        
      	$statusMap = [
            'on_time' => 'On Time',
            'tardy' => 'Tardy',
            'late_arrival' => 'Late Arrival',
            'very_late_absent' => 'Very Late/Absent',
            'no_call_no_show' => 'No Call/No Show',
            'planned_out' => 'Planned Out',
            'dropped_shift' => 'Dropped Shift'
        ];
        
        $attendanceStatus = isset($statusMap[$row['attendance_status']]) ? 
            $statusMap[$row['attendance_status']] : $row['attendance_status'];
        
		fputcsv($output, [
		    $employee_name,
		    $row['shift_date'],
		    $dayOfWeek,
		    $startTime,
		    $endTime,
		    $row['department_name'],
		    $row['role_name'],
		    $attendanceStatus,
		    $row['minutes_late'] ?: '',
		    $row['attendance_notes'] ?: '',
		    $recordedAt,
		    $recordedBy
		], ',', '"', '\\');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Export error: ' . $e->getMessage()
    ]);
}
?>