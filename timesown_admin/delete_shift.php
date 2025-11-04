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
$user_query = "SELECT id, role FROM users WHERE switch_id = ? AND account_delete = 0";
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

if ($user_data['role'] < 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_POST['shift_id']) || empty($_POST['shift_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing shift ID']);
    exit();
}

$shift_id = (int)$_POST['shift_id'];
$shift_query = "
    SELECT s.*, d.tenant_id,
           CONCAT(u.first_name, ' ', u.last_name) as assigned_user_name,
           d.name as department_name,
           jr.name as role_name
    FROM to_shifts s 
    JOIN to_departments d ON s.department_id = d.id 
    JOIN to_job_roles jr ON s.job_role_id = jr.id
    LEFT JOIN users u ON s.assigned_user_id = u.id
    WHERE s.id = ?
";
$stmt = mysqli_prepare($dbc, $shift_query);
mysqli_stmt_bind_param($stmt, 'i', $shift_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$shift_data = mysqli_fetch_assoc($result);

if (!$shift_data) {
    echo json_encode(['success' => false, 'message' => 'Shift not found']);
    exit();
}

$tenant_id = $shift_data['tenant_id'];

if ($user_data['role'] < 3) {
    $tenant_check = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_check);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
        exit();
    }
}

$trades_check = "SELECT COUNT(*) as trade_count FROM to_shift_trades WHERE shift_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($dbc, $trades_check);
mysqli_stmt_bind_param($stmt, 'i', $shift_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$trades_data = mysqli_fetch_assoc($result);

if ($trades_data['trade_count'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot delete shift: ' . $trades_data['trade_count'] . ' pending trade request(s) exist. Please resolve trades first.'
    ]);
    exit();
}

try {
    mysqli_autocommit($dbc, false);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'DELETE', 'to_shifts', ?, ?, ?, ?, NOW())
    ";
    
    $old_values = json_encode([
        'shift_date' => $shift_data['shift_date'],
        'start_time' => $shift_data['start_time'],
        'end_time' => $shift_data['end_time'],
        'department_name' => $shift_data['department_name'],
        'role_name' => $shift_data['role_name'],
        'assigned_user_name' => $shift_data['assigned_user_name'],
        'status' => $shift_data['status'],
        'public_notes' => $shift_data['public_notes'],
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $user_id, $shift_id, 
        $old_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    $delete_trades = "DELETE FROM to_shift_trades WHERE shift_id = ? AND status IN ('completed', 'cancelled', 'rejected')";
    $stmt = mysqli_prepare($dbc, $delete_trades);
    mysqli_stmt_bind_param($stmt, 'i', $shift_id);
    mysqli_stmt_execute($stmt);
    
    $delete_shift = "DELETE FROM to_shifts WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $delete_shift);
    mysqli_stmt_bind_param($stmt, 'i', $shift_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error deleting shift: ' . mysqli_error($dbc));
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift deleted successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>