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

if (!isset($_POST['tenant_id']) || !isset($_POST['confirm'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$confirm = $_POST['confirm'];

if ($tenant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant ID']);
    exit();
}

if ($confirm !== 'yes') {
    echo json_encode(['success' => false, 'message' => 'Confirmation required']);
    exit();
}

try {
  	mysqli_autocommit($dbc, false);
    
    $deleted_count = 0;
    
   	$select_query = "SELECT id, shift_date, start_time, end_time FROM to_shifts WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $select_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare select query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute select query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $shifts_to_delete = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $shifts_to_delete[] = $row;
    }
    
    $total_shifts = count($shifts_to_delete);
    
    if ($total_shifts === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No shifts found to delete',
            'deleted_count' => 0
        ]);
        exit();
    }
    
   	$delete_attendance_query = "DELETE FROM to_shift_attendance WHERE shift_id IN (SELECT id FROM to_shifts WHERE tenant_id = ?)";
    $stmt = mysqli_prepare($dbc, $delete_attendance_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
        mysqli_stmt_execute($stmt);
    }
    
  	$delete_trades_query = "DELETE FROM to_shift_trades WHERE shift_id IN (SELECT id FROM to_shifts WHERE tenant_id = ?)";
    $stmt = mysqli_prepare($dbc, $delete_trades_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
        mysqli_stmt_execute($stmt);
    }
    
  	$delete_shifts_query = "DELETE FROM to_shifts WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_shifts_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare delete query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute delete query: ' . mysqli_stmt_error($stmt));
    }
    
    $deleted_count = mysqli_affected_rows($dbc);
    
   	mysqli_commit($dbc);
    
   	echo json_encode([
        'success' => true,
        'message' => "Successfully deleted all shifts for tenant",
        'deleted_count' => $deleted_count,
        'tenant_id' => $tenant_id
    ]);
    
} catch (Exception $e) {
  	mysqli_rollback($dbc);
    
  	echo json_encode([
        'success' => false,
        'message' => 'Failed to delete shifts: ' . $e->getMessage(),
        'deleted_count' => $deleted_count
    ]);
} finally {
  	mysqli_autocommit($dbc, true);
}
?>