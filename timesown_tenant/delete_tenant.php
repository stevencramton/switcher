<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('timesown_tenant')) {
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

if (!isset($_POST['tenant_id']) || empty($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$tenant_id = (int)$_POST['tenant_id'];
$tenant_query = "SELECT * FROM to_tenants WHERE id = ?";
$stmt = mysqli_prepare($dbc, $tenant_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Tenant not found']);
    exit();
}

$tenant_data = mysqli_fetch_assoc($result);
$users_check = "SELECT COUNT(*) as user_count FROM to_user_tenants WHERE tenant_id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $users_check);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users_data = mysqli_fetch_assoc($result);

if ($users_data['user_count'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot delete organization: ' . $users_data['user_count'] . ' user(s) are still assigned. Please remove all users first.'
    ]);
    exit();
}

$shifts_check = "SELECT COUNT(*) as shift_count FROM to_shifts WHERE tenant_id = ? AND shift_date >= CURDATE()";
$stmt = mysqli_prepare($dbc, $shifts_check);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$shifts_data = mysqli_fetch_assoc($result);

if ($shifts_data['shift_count'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Cannot delete organization: ' . $shifts_data['shift_count'] . ' future shift(s) exist. Please delete all shifts first.'
    ]);
    exit();
}

try {
    mysqli_autocommit($dbc, false);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'DELETE', 'to_tenants', ?, ?, ?, ?, NOW())
    ";
    
    $old_values = json_encode([
        'name' => $tenant_data['name'],
        'slug' => $tenant_data['slug'],
        'logo' => $tenant_data['logo'],
        'settings' => $tenant_data['settings'],
        'active' => $tenant_data['active'],
        'deleted_at' => date('Y-m-d H:i:s')
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $user_id, $tenant_id, 
        $old_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    $delete_trades = "DELETE FROM to_shift_trades WHERE shift_id IN (SELECT id FROM to_shifts WHERE tenant_id = ?)";
    $stmt = mysqli_prepare($dbc, $delete_trades);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_shifts = "DELETE FROM to_shifts WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_shifts);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_templates = "DELETE FROM to_schedule_templates WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_templates);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_time_prefs = "DELETE FROM to_user_time_preferences WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_time_prefs);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_availability = "DELETE FROM to_user_availability WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_availability);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_user_roles = "
        DELETE udr FROM to_user_department_roles udr
        JOIN to_departments d ON udr.department_id = d.id
        WHERE d.tenant_id = ?
    ";
    $stmt = mysqli_prepare($dbc, $delete_user_roles);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_user_tenants = "DELETE FROM to_user_tenants WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_user_tenants);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_roles = "
        DELETE jr FROM to_job_roles jr
        JOIN to_departments d ON jr.department_id = d.id
        WHERE d.tenant_id = ?
    ";
    $stmt = mysqli_prepare($dbc, $delete_roles);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_departments = "DELETE FROM to_departments WHERE tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_departments);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $delete_tenant = "DELETE FROM to_tenants WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $delete_tenant);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error deleting tenant: ' . mysqli_error($dbc));
    }
    
    if ($tenant_data['logo'] && file_exists('../../' . $tenant_data['logo'])) {
        unlink('../../' . $tenant_data['logo']);
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Organization deleted successfully'
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