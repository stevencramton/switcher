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

$info_query = "
    SELECT u.first_name, u.last_name, u.display_name, t.name as tenant_name
    FROM users u, to_tenants t
    WHERE u.id = ? AND t.id = ?
";
$stmt = mysqli_prepare($dbc, $info_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User or tenant not found']);
    exit();
}

$info = mysqli_fetch_assoc($result);
$assignment_check = "SELECT id, is_primary FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $assignment_check);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User is not assigned to this organization']);
    exit();
}

$assignment = mysqli_fetch_assoc($result);
$future_shifts_check = "
    SELECT COUNT(*) as shift_count, MIN(shift_date) as earliest_shift
    FROM to_shifts 
    WHERE assigned_user_id = ? AND tenant_id = ? AND shift_date >= CURDATE()
    AND status IN ('scheduled', 'pending')
";
$stmt = mysqli_prepare($dbc, $future_shifts_check);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$shifts_data = mysqli_fetch_assoc($result);

if ($shifts_data['shift_count'] > 0) {
    echo json_encode([
        'success' => false, 
        'message' => "Cannot remove user: they have {$shifts_data['shift_count']} future shifts scheduled (starting {$shifts_data['earliest_shift']}). Please reassign or cancel these shifts first."
    ]);
    exit();
}

try {
	mysqli_autocommit($dbc, false);
	$original_is_primary = $assignment['is_primary'];
  	$deactivate_query = "UPDATE to_user_tenants SET active = 0 WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $deactivate_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error removing user from tenant: ' . mysqli_error($dbc));
    }
    
    $affected_rows = mysqli_affected_rows($dbc);
    if ($affected_rows === 0) {
        throw new Exception('No assignment found to remove');
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
    
    $roles_affected = mysqli_affected_rows($dbc);
	$clear_availability_query = "DELETE FROM to_user_availability WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $clear_availability_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $availability_cleared = mysqli_affected_rows($dbc);
	$clear_preferences_query = "DELETE FROM to_user_time_preferences WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $clear_preferences_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    
    $preferences_cleared = mysqli_affected_rows($dbc);
 	$new_primary_assigned = false;
    if ($original_is_primary) {
        $other_tenant_query = "
            SELECT id FROM to_user_tenants 
            WHERE user_id = ? AND tenant_id != ? AND active = 1 
            ORDER BY created_at ASC LIMIT 1
        ";
        $stmt = mysqli_prepare($dbc, $other_tenant_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $other_assignment = mysqli_fetch_assoc($result);
            $make_primary_query = "UPDATE to_user_tenants SET is_primary = 1 WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $make_primary_query);
            mysqli_stmt_bind_param($stmt, 'i', $other_assignment['id']);
            mysqli_stmt_execute($stmt);
            $new_primary_assigned = true;
        }
    }
    
	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
	$audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'DELETE', 'to_user_tenants', ?, ?, ?, ?, NOW())
    ";
    
    $old_values = json_encode([
        'user_id' => $user_id,
        'tenant_id' => $tenant_id,
        'user_name' => $info['display_name'] ?: ($info['first_name'] . ' ' . $info['last_name']),
        'tenant_name' => $info['tenant_name'],
        'was_primary' => $original_is_primary,
        'roles_removed' => $roles_affected,
        'availability_cleared' => $availability_cleared,
        'preferences_cleared' => $preferences_cleared,
        'new_primary_assigned' => $new_primary_assigned,
        'action' => 'removed_from_tenant'
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $tenant_id, $admin_user_id, $assignment['id'], 
        $old_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
	$summary = [];
    $summary[] = "User removed from organization";
    if ($roles_affected > 0) {
        $summary[] = "{$roles_affected} role assignment(s) removed";
    }
    if ($availability_cleared > 0) {
        $summary[] = "Availability cleared";
    }
    if ($preferences_cleared > 0) {
        $summary[] = "Time preferences cleared";
    }
    if ($new_primary_assigned) {
        $summary[] = "Another organization set as primary";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'User successfully removed from organization',
        'details' => implode(', ', $summary),
        'stats' => [
            'roles_removed' => $roles_affected,
            'availability_cleared' => $availability_cleared,
            'preferences_cleared' => $preferences_cleared,
            'new_primary_assigned' => $new_primary_assigned
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'database_error'
    ]);
}
?>