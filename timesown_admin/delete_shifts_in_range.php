<?php
session_start();
include_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
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

if (!isset($_POST['tenant_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date']) || !isset($_POST['confirm'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$confirm = $_POST['confirm'];

if ($tenant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant ID']);
    exit();
}

if ($confirm !== 'yes') {
    echo json_encode(['success' => false, 'message' => 'Confirmation required']);
    exit();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit();
}

if ($start_date > $end_date) {
    echo json_encode(['success' => false, 'message' => 'Start date must be before or equal to end date']);
    exit();
}

try {
  	mysqli_autocommit($dbc, false);
    
    $deleted_count = 0;
    
   	$select_query = "
        SELECT s.id, s.shift_date, s.start_time, s.end_time, s.assigned_user_id,
               d.name as department_name, r.name as role_name,
               CONCAT(COALESCE(u.display_name, CONCAT(u.first_name, ' ', u.last_name)), '') as user_name
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles r ON s.job_role_id = r.id
        LEFT JOIN users u ON s.assigned_user_id = u.id
        WHERE s.tenant_id = ? 
        AND s.shift_date >= ? 
        AND s.shift_date <= ?
    ";
    
    $stmt = mysqli_prepare($dbc, $select_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare select query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute select query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $shifts_to_delete = [];
    $shift_ids = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $shifts_to_delete[] = $row;
        $shift_ids[] = $row['id'];
    }
    
    $total_shifts = count($shifts_to_delete);
    
    if ($total_shifts === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No shifts found in the specified date range',
            'deleted_count' => 0
        ]);
        exit();
    }
    
  	if (!empty($shift_ids)) {
        $placeholders = implode(',', array_fill(0, count($shift_ids), '?'));
        
       	$delete_attendance_query = "DELETE FROM to_shift_attendance WHERE shift_id IN ($placeholders)";
        $stmt = mysqli_prepare($dbc, $delete_attendance_query);
        
        if ($stmt) {
            $types = str_repeat('i', count($shift_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$shift_ids);
            mysqli_stmt_execute($stmt);
        }
        
       	$delete_trades_query = "DELETE FROM to_shift_trades WHERE shift_id IN ($placeholders)";
        $stmt = mysqli_prepare($dbc, $delete_trades_query);
        
        if ($stmt) {
            $types = str_repeat('i', count($shift_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$shift_ids);
            mysqli_stmt_execute($stmt);
        }
    }
    
   	$delete_shifts_query = "
        DELETE FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date >= ? 
        AND shift_date <= ?
    ";
    
    $stmt = mysqli_prepare($dbc, $delete_shifts_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare delete query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute delete query: ' . mysqli_stmt_error($stmt));
    }
    
    $deleted_count = mysqli_affected_rows($dbc);
    
  	mysqli_commit($dbc);
    
   	$log_message = "Date range deletion: User ID {$user_id} deleted {$deleted_count} shifts for tenant {$tenant_id} from {$start_date} to {$end_date}";
    error_log($log_message);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully deleted shifts in date range",
        'deleted_count' => $deleted_count,
        'tenant_id' => $tenant_id,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'date_range' => "{$start_date} to {$end_date}"
    ]);
    
} catch (Exception $e) {
  	mysqli_rollback($dbc);
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete shifts: ' . $e->getMessage(),
        'deleted_count' => 0
    ]);
} finally {
   	mysqli_autocommit($dbc, true);
}
?>