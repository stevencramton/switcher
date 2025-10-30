<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!checkRole('timesown_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('admin_developer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($dbc)]);
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'diagnose';

if ($action === 'diagnose') {
    try {
        $alex_query = "SELECT id, first_name, last_name, display_name FROM users 
                       WHERE (first_name LIKE '%Alexander%' OR display_name LIKE '%Alexander%') 
                       AND (last_name LIKE '%St. Pierre%' OR last_name LIKE '%Pierre%' OR display_name LIKE '%St. Pierre%')
                       AND account_delete = 0";
        $stmt = mysqli_prepare($dbc, $alex_query);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($dbc)]);
            exit();
        }
        
        mysqli_stmt_execute($stmt);
        $alex_result = mysqli_stmt_get_result($stmt);
        
        $alex_users = [];
        while ($row = mysqli_fetch_assoc($alex_result)) {
            $alex_users[] = $row;
        }
        
        if (empty($alex_users)) {
            echo json_encode(['success' => false, 'message' => 'Alexander St. Pierre not found in users table']);
            exit();
        }
        
        $diagnostic_results = [];
        foreach ($alex_users as $alex) {
            $alex_id = $alex['id'];
            
            $shifts_query = "
                SELECT s.*, 
                       d.name as department_name, 
                       jr.name as role_name,
                       t.name as tenant_name
                FROM to_shifts s
                LEFT JOIN to_departments d ON s.department_id = d.id
                LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
                LEFT JOIN to_tenants t ON s.tenant_id = t.id
                WHERE s.assigned_user_id = ? 
                AND s.tenant_id = 3
                AND s.shift_date >= '2025-07-03'
                ORDER BY s.shift_date, s.start_time
            ";
            
            $stmt = mysqli_prepare($dbc, $shifts_query);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error for shifts: ' . mysqli_error($dbc)]);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, 'i', $alex_id);
            mysqli_stmt_execute($stmt);
            $shifts_result = mysqli_stmt_get_result($stmt);
            
            $shifts = [];
            while ($row = mysqli_fetch_assoc($shifts_result)) {
                $shifts[] = $row;
            }
            
            $tenant_query = "SELECT * FROM to_user_tenants WHERE user_id = ? AND tenant_id = 3";
            $stmt = mysqli_prepare($dbc, $tenant_query);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error for tenant: ' . mysqli_error($dbc)]);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, 'i', $alex_id);
            mysqli_stmt_execute($stmt);
            $tenant_result = mysqli_stmt_get_result($stmt);
            $tenant_assignment = mysqli_fetch_assoc($tenant_result);
            
            $roles_query = "
                SELECT udr.*, d.name as dept_name, jr.name as role_name
                FROM to_user_department_roles udr
                LEFT JOIN to_departments d ON udr.department_id = d.id
                LEFT JOIN to_job_roles jr ON udr.job_role_id = jr.id
                WHERE udr.user_id = ? 
                AND d.tenant_id = 3
            ";
            $stmt = mysqli_prepare($dbc, $roles_query);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database prepare error for roles: ' . mysqli_error($dbc)]);
                exit();
            }
            
            mysqli_stmt_bind_param($stmt, 'i', $alex_id);
            mysqli_stmt_execute($stmt);
            $roles_result = mysqli_stmt_get_result($stmt);
            
            $roles = [];
            while ($row = mysqli_fetch_assoc($roles_result)) {
                $roles[] = $row;
            }
            
            $diagnostic_results[] = [
                'user' => $alex,
                'shifts' => $shifts,
                'tenant_assignment' => $tenant_assignment,
                'role_assignments' => $roles
            ];
        }
        
        echo json_encode([
            'success' => true,
            'action' => 'diagnose',
            'results' => $diagnostic_results
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Diagnostic error: ' . $e->getMessage()]);
    }
    
} elseif ($action === 'cleanup') {
    try {
        $shift_id = isset($_POST['shift_id']) ? (int)$_POST['shift_id'] : 0;
        
        if (!$shift_id) {
            echo json_encode(['success' => false, 'message' => 'Shift ID required for cleanup']);
            exit();
        }
        
        $verify_query = "
            SELECT s.*, u.first_name, u.last_name, u.display_name 
            FROM to_shifts s
            JOIN users u ON s.assigned_user_id = u.id
            WHERE s.id = ? 
            AND s.tenant_id = 3
            AND (u.first_name LIKE '%Alexander%' OR u.display_name LIKE '%Alexander%')
            AND (u.last_name LIKE '%St. Pierre%' OR u.last_name LIKE '%Pierre%' OR u.display_name LIKE '%St. Pierre%')
        ";
        
        $stmt = mysqli_prepare($dbc, $verify_query);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error for verification: ' . mysqli_error($dbc)]);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $shift_id);
        mysqli_stmt_execute($stmt);
        $verify_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($verify_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Shift not found or does not belong to Alexander St. Pierre in tenant 3']);
            exit();
        }
        
        $shift_data = mysqli_fetch_assoc($verify_result);
        
        mysqli_autocommit($dbc, false);
        
        $audit_query = "
            INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'DELETE_CLEANUP', 'to_shifts', ?, ?, ?, ?, NOW())
        ";
        
        $old_values = json_encode([
            'shift_id' => $shift_data['id'],
            'user_name' => $shift_data['display_name'] ?: $shift_data['first_name'] . ' ' . $shift_data['last_name'],
            'shift_date' => $shift_data['shift_date'],
            'start_time' => $shift_data['start_time'],
            'end_time' => $shift_data['end_time'],
            'status' => $shift_data['status'],
            'cleanup_reason' => 'Phantom shift - partial creation detected',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($dbc, $audit_query);
        if (!$stmt) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Database prepare error for audit log: ' . mysqli_error($dbc)]);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'iissss', 
            3, $user_id, $shift_id, 
            $old_values, $ip_address, $user_agent
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Failed to log cleanup action: ' . mysqli_stmt_error($stmt)]);
            exit();
        }
        
        $delete_trades = "DELETE FROM to_shift_trades WHERE shift_id = ?";
        $stmt = mysqli_prepare($dbc, $delete_trades);
        
		if (!$stmt) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Database prepare error for trades deletion: ' . mysqli_error($dbc)]);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $shift_id);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Failed to delete related trades: ' . mysqli_stmt_error($stmt)]);
            exit();
        }
        
        $delete_shift = "DELETE FROM to_shifts WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $delete_shift);
        
		if (!$stmt) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Database prepare error for shift deletion: ' . mysqli_error($dbc)]);
            exit();
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $shift_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'Failed to delete shift: ' . mysqli_stmt_error($stmt)]);
            exit();
        }
        
        if (mysqli_affected_rows($dbc) === 0) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            echo json_encode(['success' => false, 'message' => 'No shift was deleted - shift may not exist']);
            exit();
        }
        
        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);
        
        echo json_encode([
            'success' => true,
            'action' => 'cleanup',
            'message' => 'Phantom shift deleted successfully',
            'deleted_shift' => $shift_data
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        
        echo json_encode([
            'success' => false,
            'message' => 'Cleanup failed: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use "diagnose" or "cleanup"']);
}
?>