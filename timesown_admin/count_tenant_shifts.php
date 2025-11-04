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

if (!isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing tenant ID']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];

if ($tenant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant ID']);
    exit();
}

try {
  	$count_query = "SELECT COUNT(*) as shift_count FROM to_shifts WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $count_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare count query: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute count query: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $count_data = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$count_data['shift_count'],
        'tenant_id' => $tenant_id
    ]);
    
} catch (Exception $e) {
    error_log('Count tenant shifts error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to count shifts: ' . $e->getMessage()
    ]);
}
?>