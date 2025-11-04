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

$shift_id = isset($_GET['shift_id']) ? (int)$_GET['shift_id'] : 0;

if (!$shift_id) {
    echo json_encode(['success' => false, 'message' => 'Shift ID is required']);
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

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];

try {
	$query = "
        SELECT 
            s.id,
            s.attendance_status,
            s.attendance_notes,
            s.attendance_recorded_by,
            s.attendance_recorded_at,
            sa.id as attendance_record_id,
            sa.attendance_status as sa_attendance_status,
            sa.minutes_late,
            sa.attendance_notes as sa_attendance_notes,
            sa.recorded_by as sa_recorded_by,
            sa.recorded_at as sa_recorded_at,
            u1.first_name as recorded_by_first_name,
            u1.last_name as recorded_by_last_name,
            u2.first_name as sa_recorded_by_first_name,
            u2.last_name as sa_recorded_by_last_name
        FROM to_shifts s
        LEFT JOIN to_shift_attendance sa ON s.id = sa.shift_id
        LEFT JOIN users u1 ON s.attendance_recorded_by = u1.id
        LEFT JOIN users u2 ON sa.recorded_by = u2.id
        WHERE s.id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $shift_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Shift not found']);
        exit();
    }
    
    $shift_data = mysqli_fetch_assoc($result);
 	$attendance_data = null;
    
  	if ($shift_data['attendance_record_id']) {
        $attendance_data = [
            'source' => 'shift_attendance',
            'attendance_status' => $shift_data['sa_attendance_status'],
            'minutes_late' => $shift_data['minutes_late'],
            'attendance_notes' => $shift_data['sa_attendance_notes'],
            'recorded_by' => $shift_data['sa_recorded_by'],
            'recorded_by_name' => trim($shift_data['sa_recorded_by_first_name'] . ' ' . $shift_data['sa_recorded_by_last_name']),
            'recorded_at' => $shift_data['sa_recorded_at']
        ];
    } 
 	
	elseif ($shift_data['attendance_status']) {
        $attendance_data = [
            'source' => 'shifts',
            'attendance_status' => $shift_data['attendance_status'],
            'minutes_late' => null,
            'attendance_notes' => $shift_data['attendance_notes'],
            'recorded_by' => $shift_data['attendance_recorded_by'],
            'recorded_by_name' => trim($shift_data['recorded_by_first_name'] . ' ' . $shift_data['recorded_by_last_name']),
            'recorded_at' => $shift_data['attendance_recorded_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'attendance' => $attendance_data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>