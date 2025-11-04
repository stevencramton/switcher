<?php
session_start();
ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['switch_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
    include '../../templates/functions.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit();
}

if (!checkRole('timesown_admin')) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['action'])) {
    $input = $_POST;
}

if (!isset($input['action'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action is required']);
    exit();
}

$action = $input['action'];
$switch_id = $_SESSION['switch_id'];

$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$admin_user_id = $user_data['id'];

switch ($action) {
    case 'get_tradeboard_status':
        getTradeboardStatus($dbc, $admin_user_id, $input);
        break;
    
    case 'get_pending_trades':
        getPendingTrades($dbc, $admin_user_id, $input);
        break;
    
    case 'get_all_trades':
        getAllTrades($dbc, $admin_user_id, $input);
        break;
    
    case 'approve_trade':
        approveTrade($dbc, $admin_user_id, $input);
        break;
    
    case 'get_trade_analytics':
        getTradeAnalytics($dbc, $admin_user_id, $input);
        break;
    
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getTradeboardStatus($dbc, $admin_user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

	$settings_query = "SELECT settings FROM to_tenants WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $settings_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $settings_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($settings_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tenant not found']);
        exit();
    }
    
    $tenant_data = mysqli_fetch_assoc($settings_result);
    $settings = json_decode($tenant_data['settings'], true);
    $trade_settings = $settings['trade_settings'] ?? [];
    $is_enabled = $trade_settings['allow_trades'] ?? false;

 	$pending_query = "
        SELECT COUNT(*) as count 
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE s.tenant_id = ? AND st.status = 'pending_approval'
    ";
    $stmt = mysqli_prepare($dbc, $pending_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $pending_result = mysqli_stmt_get_result($stmt);
    $pending_count = mysqli_fetch_assoc($pending_result)['count'];

    $active_query = "
        SELECT COUNT(*) as count 
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE s.tenant_id = ? AND st.status IN ('placed_on_tradeboard', 'pending_approval')
    ";
    $stmt = mysqli_prepare($dbc, $active_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $active_result = mysqli_stmt_get_result($stmt);
    $active_trades = mysqli_fetch_assoc($active_result)['count'];

    $monthly_query = "
        SELECT COUNT(*) as count 
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE s.tenant_id = ? 
        AND st.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        AND st.created_at < DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH), '%Y-%m-01')
    ";
    $stmt = mysqli_prepare($dbc, $monthly_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $monthly_result = mysqli_stmt_get_result($stmt);
    $monthly_trades = mysqli_fetch_assoc($monthly_result)['count'];

    $status = [
        'is_enabled' => $is_enabled,
        'pending_count' => $pending_count,
        'active_trades' => $active_trades,
        'monthly_trades' => $monthly_trades
    ];

    ob_end_clean();
    echo json_encode(['success' => true, 'status' => $status]);
}

function getPendingTrades($dbc, $admin_user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

   	if (!checkRole('admin_developer')) {
        $tenant_access_query = "
            SELECT ut.id 
            FROM to_user_tenants ut 
            WHERE ut.user_id = ? AND ut.tenant_id = ?
        ";
        $stmt = mysqli_prepare($dbc, $tenant_access_query);
        mysqli_stmt_bind_param($stmt, 'ii', $admin_user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $access_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($access_result) === 0) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
            exit();
        }
    }

   	$trades_query = "
        SELECT 
            st.id,
            st.shift_id,
            st.offering_user_id,
            st.requesting_user_id,
            st.status,
            st.notes,
            st.created_at,
            st.updated_at,
            
            -- Shift details
            s.shift_date,
            s.start_time,
            s.end_time,
            TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time))/3600 as shift_hours,
            
            -- Department and role info
            d.name as department_name,
            jr.name as role_name,
            
            -- Requesting employee info
            req_u.first_name as req_first_name,
            req_u.last_name as req_last_name,
            req_u.user as req_username,
            CONCAT(req_u.first_name, ' ', req_u.last_name) as employee_name,
            req_u.profile_pic as employee_photo,
            
            -- Offering employee info (original shift holder)
            off_u.first_name as off_first_name,
            off_u.last_name as off_last_name,
            off_u.user as off_username,
            CONCAT(off_u.first_name, ' ', off_u.last_name) as offering_employee_name
            
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        JOIN to_departments d ON s.department_id = d.id
        JOIN to_job_roles jr ON s.job_role_id = jr.id
        LEFT JOIN users req_u ON st.requesting_user_id = req_u.id
        LEFT JOIN users off_u ON st.offering_user_id = off_u.id
        
        WHERE s.tenant_id = ? 
        AND st.status = 'pending_approval'
        AND st.requesting_user_id IS NOT NULL
        
        ORDER BY st.created_at ASC
    ";

    $stmt = mysqli_prepare($dbc, $trades_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $trades_result = mysqli_stmt_get_result($stmt);

    $pending_trades = [];
    while ($row = mysqli_fetch_assoc($trades_result)) {
      	$user_id = $row['requesting_user_id'];
        if ($user_id) {
            $date = new DateTime($row['shift_date']);
            $week_start = clone $date;
            $week_start->modify('monday this week');
            $week_end = clone $date;
            $week_end->modify('sunday this week');

            $hours_query = "
                SELECT SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time))/3600) as total_hours
                FROM to_shifts s
                WHERE s.assigned_user_id = ? 
                AND s.tenant_id = ? 
                AND s.shift_date BETWEEN ? AND ?
                AND s.status = 'scheduled'
                AND s.id != ?
            ";
            
            $hours_stmt = mysqli_prepare($dbc, $hours_query);
            $week_start_str = $week_start->format('Y-m-d');
            $week_end_str = $week_end->format('Y-m-d');
            mysqli_stmt_bind_param($hours_stmt, 'iissi', $user_id, $tenant_id, $week_start_str, $week_end_str, $row['shift_id']);
            mysqli_stmt_execute($hours_stmt);
            $hours_result = mysqli_stmt_get_result($hours_stmt);
            $hours_data = mysqli_fetch_assoc($hours_result);
            
            $row['current_weekly_hours'] = $hours_data['total_hours'] ?? 0;
            $row['total_hours_after_trade'] = $row['current_weekly_hours'] + $row['shift_hours'];
        } else {
            $row['current_weekly_hours'] = 0;
            $row['total_hours_after_trade'] = 0;
        }
        
      	$role_query = "
            SELECT jr.name as current_role
            FROM to_user_department_roles udr
            JOIN to_job_roles jr ON udr.job_role_id = jr.id
            JOIN to_departments d ON jr.department_id = d.id
            WHERE udr.user_id = ? AND d.tenant_id = ?
            ORDER BY udr.created_at DESC
            LIMIT 1
        ";
        $role_stmt = mysqli_prepare($dbc, $role_query);
        mysqli_stmt_bind_param($role_stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($role_stmt);
        $role_result = mysqli_stmt_get_result($role_stmt);
        if ($role_row = mysqli_fetch_assoc($role_result)) {
            $row['current_role'] = $role_row['current_role'];
        } else {
            $row['current_role'] = 'Staff';
        }
        
        $pending_trades[] = $row;
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'pending_trades' => $pending_trades]);
}

function getAllTrades($dbc, $admin_user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);
    
  	$status_filter = isset($input['status']) ? $input['status'] : '';
    $date_filter = isset($input['date']) ? $input['date'] : '';
    $limit = isset($input['limit']) ? intval($input['limit']) : 50;
    $offset = isset($input['offset']) ? intval($input['offset']) : 0;

  	$where_conditions = ['s.tenant_id = ?'];
    $params = [$tenant_id];
    $param_types = 'i';

    if (!empty($status_filter)) {
        $where_conditions[] = 'st.status = ?';
        $params[] = $status_filter;
        $param_types .= 's';
    }

    if (!empty($date_filter)) {
        $where_conditions[] = 'DATE(st.created_at) = ?';
        $params[] = $date_filter;
        $param_types .= 's';
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

  	$trades_query = "
        SELECT 
            st.id,
            st.shift_id,
            st.offering_user_id,
            st.requesting_user_id,
            st.status,
            st.notes,
            st.created_at,
            st.updated_at,
            st.approved_at,
            st.rejected_at,
            st.rejection_reason,
            
            -- Shift details
            s.shift_date,
            s.start_time,
            s.end_time,
            TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time))/3600 as shift_hours,
            
            -- Department and role info
            d.name as department_name,
            jr.name as role_name,
            
            -- Offering employee info (original shift holder)
            off_u.first_name as off_first_name,
            off_u.last_name as off_last_name,
            off_u.user as off_username,
            CONCAT(off_u.first_name, ' ', off_u.last_name) as offering_employee_name,
            off_u.profile_pic as offering_employee_photo,
            
            -- Requesting employee info
            req_u.first_name as req_first_name,
            req_u.last_name as req_last_name,
            req_u.user as req_username,
            CONCAT(req_u.first_name, ' ', req_u.last_name) as requesting_employee_name,
            req_u.profile_pic as requesting_employee_photo,
            
            -- Admin action info
            app_u.first_name as approved_by_first_name,
            app_u.last_name as approved_by_last_name,
            CONCAT(app_u.first_name, ' ', app_u.last_name) as approved_by_name,
            
            rej_u.first_name as rejected_by_first_name,
            rej_u.last_name as rejected_by_last_name,
            CONCAT(rej_u.first_name, ' ', rej_u.last_name) as rejected_by_name
            
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        JOIN to_departments d ON s.department_id = d.id
        JOIN to_job_roles jr ON s.job_role_id = jr.id
        LEFT JOIN users off_u ON st.offering_user_id = off_u.id
        LEFT JOIN users req_u ON st.requesting_user_id = req_u.id
        LEFT JOIN users app_u ON st.approved_by = app_u.id
        LEFT JOIN users rej_u ON st.rejected_by = rej_u.id
        
        $where_clause
        
        ORDER BY st.created_at DESC
        LIMIT ? OFFSET ?
    ";

   	$params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = mysqli_prepare($dbc, $trades_query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $trades_result = mysqli_stmt_get_result($stmt);

    $all_trades = [];
    while ($row = mysqli_fetch_assoc($trades_result)) {
        $all_trades[] = $row;
    }

   	$count_query = "
        SELECT COUNT(*) as total
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        $where_clause
    ";

    $count_params = array_slice($params, 0, -2);
    $count_param_types = substr($param_types, 0, -2);

    $stmt = mysqli_prepare($dbc, $count_query);
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($stmt, $count_param_types, ...$count_params);
    }
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $total_count = mysqli_fetch_assoc($count_result)['total'];

    ob_end_clean();
    echo json_encode([
        'success' => true, 
        'all_trades' => $all_trades,
        'total_count' => $total_count,
        'has_more' => ($offset + $limit) < $total_count
    ]);
}

function approveTrade($dbc, $admin_user_id, $input) {
    if (!isset($input['trade_id']) || !isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trade ID and tenant ID are required']);
        exit();
    }

    $trade_id = intval($input['trade_id']);
    $tenant_id = intval($input['tenant_id']);
    $notes = $input['notes'] ?? '';

    // Get trade details
    $trade_query = "
        SELECT st.*, s.id as shift_id, s.tenant_id
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE st.id = ? AND s.tenant_id = ? AND st.status = 'pending_approval'
    ";
    
    $stmt = mysqli_prepare($dbc, $trade_query);
    mysqli_stmt_bind_param($stmt, 'ii', $trade_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $trade_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($trade_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trade not found or not pending approval']);
        exit();
    }

    $trade_data = mysqli_fetch_assoc($trade_result);

    try {
        mysqli_autocommit($dbc, false);

      	$approve_query = "
            UPDATE to_shift_trades 
            SET status = 'completed', 
                approved_by = ?, 
                approved_at = CURRENT_TIMESTAMP, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ";
        $stmt = mysqli_prepare($dbc, $approve_query);
        mysqli_stmt_bind_param($stmt, 'ii', $admin_user_id, $trade_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to approve trade');
        }

      	$update_shift_query = "
            UPDATE to_shifts 
            SET assigned_user_id = ?, 
                status = 'scheduled', 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ";
        $stmt = mysqli_prepare($dbc, $update_shift_query);
        mysqli_stmt_bind_param($stmt, 'ii', $trade_data['requesting_user_id'], $trade_data['shift_id']);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update shift assignment');
        }

      	$audit_query = "
            INSERT INTO to_audit_log 
            (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'APPROVE', 'to_shift_trades', ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ";
        
        $new_values = json_encode([
            'trade_id' => $trade_id,
            'approved_by' => $admin_user_id,
            'notes' => $notes,
            'action' => 'trade_approved'
        ]);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'iissss', 
            $tenant_id, $admin_user_id, $trade_id, 
            $new_values, $ip_address, $user_agent
        );
        mysqli_stmt_execute($stmt);

        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Trade approved successfully']);

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to approve trade: ' . $e->getMessage()]);
    }
}

function rejectTrade($dbc, $admin_user_id, $input) {
    if (!isset($input['trade_id']) || !isset($input['tenant_id']) || !isset($input['rejection_reason'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trade ID, tenant ID, and rejection reason are required']);
        exit();
    }

    $trade_id = intval($input['trade_id']);
    $tenant_id = intval($input['tenant_id']);
    $rejection_reason = trim($input['rejection_reason']);

    if (empty($rejection_reason)) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rejection reason cannot be empty']);
        exit();
    }

  	$trade_query = "
        SELECT st.*, s.id as shift_id, s.tenant_id
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE st.id = ? AND s.tenant_id = ? AND st.status = 'pending_approval'
    ";
    $stmt = mysqli_prepare($dbc, $trade_query);
    mysqli_stmt_bind_param($stmt, 'ii', $trade_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $trade_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($trade_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trade not found or not pending approval']);
        exit();
    }

    $trade_data = mysqli_fetch_assoc($trade_result);

    try {
        mysqli_autocommit($dbc, false);

       	$reject_query = "
            UPDATE to_shift_trades 
            SET status = 'placed_on_tradeboard', 
                rejected_by = ?, 
                rejected_at = CURRENT_TIMESTAMP, 
                rejection_reason = ?, 
                requesting_user_id = NULL, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ";
        $stmt = mysqli_prepare($dbc, $reject_query);
        mysqli_stmt_bind_param($stmt, 'isi', $admin_user_id, $rejection_reason, $trade_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to reject trade');
        }

       	$audit_query = "
            INSERT INTO to_audit_log 
            (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'REJECT', 'to_shift_trades', ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ";
        
        $new_values = json_encode([
            'trade_id' => $trade_id,
            'rejected_by' => $admin_user_id,
            'rejection_reason' => $rejection_reason,
            'action' => 'trade_rejected'
        ]);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'iissss', 
            $tenant_id, $admin_user_id, $trade_id, 
            $new_values, $ip_address, $user_agent
        );
        mysqli_stmt_execute($stmt);

        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Trade rejected successfully']);

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reject trade: ' . $e->getMessage()]);
    }
}

function getTradeAnalytics($dbc, $admin_user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);
    $period = isset($input['period']) ? $input['period'] : 'month';

   	$date_conditions = '';
    switch ($period) {
        case 'week':
            $date_conditions = "AND st.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $date_conditions = "AND st.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'quarter':
            $date_conditions = "AND st.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'year':
            $date_conditions = "AND st.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_conditions = "AND st.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }

  	$stats_query = "
        SELECT 
            COUNT(*) as total_trades,
            SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as completed_trades,
            SUM(CASE WHEN st.status = 'rejected' THEN 1 ELSE 0 END) as rejected_trades,
            SUM(CASE WHEN st.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_trades,
            SUM(CASE WHEN st.status = 'pending_approval' THEN 1 ELSE 0 END) as pending_trades,
            SUM(CASE WHEN st.status = 'placed_on_tradeboard' THEN 1 ELSE 0 END) as available_trades,
            AVG(CASE WHEN st.approved_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, st.created_at, st.approved_at) 
                ELSE NULL END) as avg_approval_time_hours
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE s.tenant_id = ? $date_conditions
    ";

    $stmt = mysqli_prepare($dbc, $stats_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $stats_result = mysqli_stmt_get_result($stmt);
    $overall_stats = mysqli_fetch_assoc($stats_result);

   	$dept_query = "
        SELECT 
            d.name as department_name,
            COUNT(*) as trade_count,
            SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN st.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        JOIN to_departments d ON s.department_id = d.id
        WHERE s.tenant_id = ? $date_conditions
        GROUP BY d.id, d.name
        ORDER BY trade_count DESC
    ";

    $stmt = mysqli_prepare($dbc, $dept_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $dept_result = mysqli_stmt_get_result($stmt);
    
    $department_breakdown = [];
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $department_breakdown[] = $row;
    }

  	$activity_query = "
        SELECT 
            DATE(st.created_at) as trade_date,
            COUNT(*) as trades_created,
            SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as trades_completed
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        WHERE s.tenant_id = ? $date_conditions
        GROUP BY DATE(st.created_at)
        ORDER BY trade_date DESC
        LIMIT 30
    ";

    $stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $activity_result = mysqli_stmt_get_result($stmt);
    
    $daily_activity = [];
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $daily_activity[] = $row;
    }

  	$top_traders_query = "
        SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            u.profile_pic,
            COUNT(*) as total_trades,
            SUM(CASE WHEN st.status = 'completed' THEN 1 ELSE 0 END) as successful_trades
        FROM to_shift_trades st
        JOIN to_shifts s ON st.shift_id = s.id
        LEFT JOIN users u ON (st.offering_user_id = u.id OR st.requesting_user_id = u.id)
        WHERE s.tenant_id = ? $date_conditions AND u.id IS NOT NULL
        GROUP BY u.id, u.first_name, u.last_name, u.profile_pic
        ORDER BY total_trades DESC
        LIMIT 10
    ";

    $stmt = mysqli_prepare($dbc, $top_traders_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $traders_result = mysqli_stmt_get_result($stmt);
    
    $top_traders = [];
    while ($row = mysqli_fetch_assoc($traders_result)) {
        $top_traders[] = $row;
    }

   	$total_trades = intval($overall_stats['total_trades']);
    $completed_trades = intval($overall_stats['completed_trades']);
    $success_rate = $total_trades > 0 ? round(($completed_trades / $total_trades) * 100, 1) : 0;

    $analytics = [
        'period' => $period,
        'overall_stats' => [
            'total_trades' => $total_trades,
            'completed_trades' => $completed_trades,
            'rejected_trades' => intval($overall_stats['rejected_trades']),
            'cancelled_trades' => intval($overall_stats['cancelled_trades']),
            'pending_trades' => intval($overall_stats['pending_trades']),
            'available_trades' => intval($overall_stats['available_trades']),
            'success_rate' => $success_rate,
            'avg_approval_time_hours' => round(floatval($overall_stats['avg_approval_time_hours'] ?? 0), 1)
        ],
        'department_breakdown' => $department_breakdown,
        'daily_activity' => array_reverse($daily_activity),
        'top_traders' => $top_traders
    ];

    ob_end_clean();
    echo json_encode(['success' => true, 'analytics' => $analytics]);
}
?>