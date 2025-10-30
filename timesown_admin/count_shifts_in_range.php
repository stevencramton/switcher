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

if (!isset($_POST['tenant_id']) || !isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

if ($tenant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant ID']);
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
  	$count_query = "
        SELECT COUNT(*) as shift_count 
        FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date >= ? 
        AND shift_date <= ?
    ";
    
    $stmt = mysqli_prepare($dbc, $count_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare count query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute count query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $count_data = mysqli_fetch_assoc($result);
    
	$details_query = "
        SELECT 
            MIN(shift_date) as earliest_date,
            MAX(shift_date) as latest_date,
            COUNT(DISTINCT assigned_user_id) as unique_users,
            COUNT(DISTINCT department_id) as unique_departments
        FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date >= ? 
        AND shift_date <= ?
        AND assigned_user_id IS NOT NULL
    ";
    
    $stmt2 = mysqli_prepare($dbc, $details_query);
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 'iss', $tenant_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt2);
        $details_result = mysqli_stmt_get_result($stmt2);
        $details_data = mysqli_fetch_assoc($details_result);
    } else {
        $details_data = null;
    }
    
    $response = [
        'success' => true,
        'count' => (int)$count_data['shift_count'],
        'tenant_id' => $tenant_id,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
	if ($details_data && $count_data['shift_count'] > 0) {
        $response['details'] = [
            'earliest_date' => $details_data['earliest_date'],
            'latest_date' => $details_data['latest_date'],
            'unique_users' => (int)$details_data['unique_users'],
            'unique_departments' => (int)$details_data['unique_departments']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Count shifts in range error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to count shifts: ' . $e->getMessage()
    ]);
}
?>