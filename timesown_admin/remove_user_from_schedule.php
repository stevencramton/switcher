<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$admin_user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $admin_user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$admin_user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($admin_user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    exit();
}

$admin_user_data = mysqli_fetch_assoc($admin_user_result);
$admin_user_id = $admin_user_data['id'];

if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

if (!isset($_POST['tenant_id']) || empty($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$tenant_id = (int)$_POST['tenant_id'];
$date_range_start = isset($_POST['date_range_start']) ? $_POST['date_range_start'] : date('Y-m-d');
$date_range_end = isset($_POST['date_range_end']) ? $_POST['date_range_end'] : date('Y-m-d');

$user_info_query = "SELECT first_name, last_name, display_name FROM users WHERE id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_info_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user_info_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_info_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_info = mysqli_fetch_assoc($user_info_result);
$user_name = $user_info['display_name'] ?: $user_info['first_name'] . ' ' . $user_info['last_name'];

try {
 	mysqli_autocommit($dbc, false);
    
	$shifts_query = "
        SELECT s.id, s.shift_date, s.start_time, s.end_time, s.status,
               d.name as department_name, jr.name as role_name
        FROM to_shifts s
        JOIN to_departments d ON s.department_id = d.id
        JOIN to_job_roles jr ON s.job_role_id = jr.id
        WHERE s.assigned_user_id = ? 
        AND s.tenant_id = ?
        AND s.shift_date BETWEEN ? AND ?
        ORDER BY s.shift_date, s.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $tenant_id, $date_range_start, $date_range_end);
    mysqli_stmt_execute($stmt);
    $shifts_result = mysqli_stmt_get_result($stmt);
    
    $shifts_to_delete = [];
    while ($row = mysqli_fetch_assoc($shifts_result)) {
        $shifts_to_delete[] = $row;
    }
    
    $deleted_shifts_count = count($shifts_to_delete);
    
 	if (!empty($shifts_to_delete)) {
        $shift_ids = array_column($shifts_to_delete, 'id');
        $shift_ids_placeholder = str_repeat('?,', count($shift_ids) - 1) . '?';
        
        $delete_trades_query = "DELETE FROM to_shift_trades WHERE shift_id IN ($shift_ids_placeholder)";
        $stmt = mysqli_prepare($dbc, $delete_trades_query);
        $types = str_repeat('i', count($shift_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$shift_ids);
        mysqli_stmt_execute($stmt);
        
      	$delete_shifts_query = "DELETE FROM to_shifts WHERE id IN ($shift_ids_placeholder)";
        $stmt = mysqli_prepare($dbc, $delete_shifts_query);
        mysqli_stmt_bind_param($stmt, $types, ...$shift_ids);
        mysqli_stmt_execute($stmt);
    }
    
 	$deactivate_roles_query = "
        UPDATE to_user_department_roles udr
        JOIN to_departments d ON udr.department_id = d.id
        SET udr.active = 0
        WHERE udr.user_id = ? AND d.tenant_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $deactivate_roles_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $deactivated_roles_count = mysqli_affected_rows($dbc);
    
	$audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'REMOVE_FROM_SCHEDULE', 'schedule_cleanup', ?, ?, ?, ?, NOW())
    ";
    
    $old_values = json_encode([
        'removed_user_id' => $user_id,
        'removed_user_name' => $user_name,
        'date_range_start' => $date_range_start,
        'date_range_end' => $date_range_end,
        'deleted_shifts_count' => $deleted_shifts_count,
        'deactivated_roles_count' => $deactivated_roles_count,
        'deleted_shifts' => $shifts_to_delete,
        'action_timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $admin_user_id, $user_id, 
        $old_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
 	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully removed {$user_name} from schedule",
        'details' => [
            'user_name' => $user_name,
            'deleted_shifts_count' => $deleted_shifts_count,
            'deactivated_roles_count' => $deactivated_roles_count,
            'date_range' => "$date_range_start to $date_range_end"
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove user from schedule: ' . $e->getMessage()
    ]);
}
?>