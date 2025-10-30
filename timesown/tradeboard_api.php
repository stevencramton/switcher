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
$user_id = $user_data['id'];

switch ($action) {
    case 'place_shift_on_tradeboard':
        placeShiftOnTradeboard($dbc, $user_id, $input);
        break;
    
    case 'pickup_shift':
        pickupShift($dbc, $user_id, $input);
        break;
    
    case 'get_tradeboard_shifts':
        getTradeboardShifts($dbc, $user_id, $input);
        break;
    
    case 'get_my_trades':
        getMyTrades($dbc, $user_id, $input);
        break;
    
    case 'cancel_trade':
        cancelTrade($dbc, $user_id, $input);
        break;
    
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function placeShiftOnTradeboard($dbc, $user_id, $input) {
    if (!isset($input['shift_id']) || !isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Shift ID and tenant ID are required']);
        exit();
    }

    $shift_id = intval($input['shift_id']);
    $tenant_id = intval($input['tenant_id']);
    $notes = isset($input['notes']) ? trim($input['notes']) : '';

 	$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }

  	$shift_query = "SELECT id, shift_date, start_time, end_time FROM to_shifts WHERE id = ? AND tenant_id = ? AND assigned_user_id = ? AND status = 'scheduled' AND shift_date > NOW()";
    $stmt = mysqli_prepare($dbc, $shift_query);
    mysqli_stmt_bind_param($stmt, 'iii', $shift_id, $tenant_id, $user_id);
    mysqli_stmt_execute($stmt);
    $shift_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($shift_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Shift not found or not eligible for trading']);
        exit();
    }

  	$existing_trade_query = "SELECT id FROM to_shift_trades WHERE shift_id = ? AND status IN ('placed_on_tradeboard', 'pending_approval', 'approved')";
    $stmt = mysqli_prepare($dbc, $existing_trade_query);
    mysqli_stmt_bind_param($stmt, 'i', $shift_id);
    mysqli_stmt_execute($stmt);
    $existing_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($existing_result) > 0) {
        ob_end_clean();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Shift is already on the tradeboard']);
        exit();
    }

    try {
        mysqli_autocommit($dbc, false);

      	$trade_query = "INSERT INTO to_shift_trades (shift_id, offering_user_id, trade_type, status, notes, created_at, updated_at) VALUES (?, ?, 'offer', 'placed_on_tradeboard', ?, NOW(), NOW())";
        $stmt = mysqli_prepare($dbc, $trade_query);
        mysqli_stmt_bind_param($stmt, 'iis', $shift_id, $user_id, $notes);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create trade record');
        }

        $trade_id = mysqli_insert_id($dbc);

       	$update_shift_query = "UPDATE to_shifts SET status = 'pending', updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_shift_query);
        mysqli_stmt_bind_param($stmt, 'i', $shift_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update shift status');
        }

        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);

        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Shift successfully placed on tradeboard',
            'trade_id' => $trade_id
        ]);

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to place shift on tradeboard: ' . $e->getMessage()]);
    }
}

function getTradeboardShifts($dbc, $user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

   	$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }

  	$user_dept_query = "
        SELECT DISTINCT udr.department_id, udr.job_role_id
        FROM to_user_department_roles udr
        INNER JOIN to_departments d ON udr.department_id = d.id
        INNER JOIN to_job_roles jr ON udr.job_role_id = jr.id
        WHERE udr.user_id = ? 
            AND d.tenant_id = ? 
            AND udr.active = 1
            AND d.active = 1
            AND jr.active = 1
    ";
    
    $stmt = mysqli_prepare($dbc, $user_dept_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $user_dept_result = mysqli_stmt_get_result($stmt);

    $user_departments = [];
    while ($row = mysqli_fetch_assoc($user_dept_result)) {
        $user_departments[] = [
            'department_id' => intval($row['department_id']),
            'job_role_id' => intval($row['job_role_id'])
        ];
    }

  	$shifts_query = "
        SELECT 
            st.id as trade_id,
            st.shift_id,
            st.notes,
            st.created_at,
            st.status,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.department_id,
            s.job_role_id,
            d.name as department_name,
            jr.name as role_name,
            u.display_name as offering_user_name,
            u.first_name,
            u.last_name
        FROM to_shift_trades st
        INNER JOIN to_shifts s ON st.shift_id = s.id
        INNER JOIN to_departments d ON s.department_id = d.id
        INNER JOIN to_job_roles jr ON s.job_role_id = jr.id
        INNER JOIN users u ON st.offering_user_id = u.id
        WHERE s.tenant_id = ? 
            AND st.status = 'placed_on_tradeboard'
            AND st.offering_user_id != ?
            AND s.shift_date >= CURDATE()
            AND (st.expires_at IS NULL OR st.expires_at > NOW())
        ORDER BY s.shift_date ASC, s.start_time ASC
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'ii', $tenant_id, $user_id);
    mysqli_stmt_execute($stmt);
    $shifts_result = mysqli_stmt_get_result($stmt);

    $shifts = [];
    while ($row = mysqli_fetch_assoc($shifts_result)) {
     	$can_pickup = false;
        foreach ($user_departments as $user_dept) {
            if ($user_dept['department_id'] == $row['department_id'] && 
                $user_dept['job_role_id'] == $row['job_role_id']) {
                $can_pickup = true;
                break;
            }
        }

        $offering_user_name = $row['offering_user_name'] ?: 
                             trim($row['first_name'] . ' ' . $row['last_name']);

        $shifts[] = [
            'trade_id' => intval($row['trade_id']),
            'shift_id' => intval($row['shift_id']),
            'shift_date' => $row['shift_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'department_id' => intval($row['department_id']),
            'role_id' => intval($row['job_role_id']),
            'department_name' => $row['department_name'],
            'role_name' => $row['role_name'],
            'offering_user_name' => $offering_user_name,
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'can_pickup' => $can_pickup
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'shifts' => $shifts
    ]);
}

function pickupShift($dbc, $user_id, $input) {
    if (!isset($input['trade_id']) || !isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trade ID and tenant ID are required']);
        exit();
    }

    $trade_id = intval($input['trade_id']);
    $tenant_id = intval($input['tenant_id']);

   	$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }

  	$trade_query = "
        SELECT 
            st.id,
            st.shift_id,
            st.offering_user_id,
            st.status,
            s.shift_date,
            s.start_time,
            s.end_time,
            s.department_id,
            s.job_role_id,
            s.tenant_id
        FROM to_shift_trades st
        INNER JOIN to_shifts s ON st.shift_id = s.id
        WHERE st.id = ? AND s.tenant_id = ? AND st.status = 'placed_on_tradeboard'
    ";
    
    $stmt = mysqli_prepare($dbc, $trade_query);
    mysqli_stmt_bind_param($stmt, 'ii', $trade_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $trade_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($trade_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trade not found or no longer available']);
        exit();
    }

    $trade_data = mysqli_fetch_assoc($trade_result);

  	if ($trade_data['offering_user_id'] == $user_id) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot pick up your own shift']);
        exit();
    }

 	$user_dept_check_query = "
        SELECT id 
        FROM to_user_department_roles 
        WHERE user_id = ? 
            AND department_id = ? 
            AND job_role_id = ? 
            AND active = 1
    ";
    
    $stmt = mysqli_prepare($dbc, $user_dept_check_query);
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $trade_data['department_id'], $trade_data['job_role_id']);
    mysqli_stmt_execute($stmt);
    $dept_check_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($dept_check_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'You do not have the required job role to pick up this shift'
        ]);
        exit();
    }

   	$conflict_check_query = "
        SELECT id 
        FROM to_shifts 
        WHERE assigned_user_id = ? 
            AND tenant_id = ? 
            AND shift_date = ? 
            AND status = 'scheduled'
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
    ";
    
    $stmt = mysqli_prepare($dbc, $conflict_check_query);
    mysqli_stmt_bind_param($stmt, 'iisssssss', 
        $user_id, 
        $tenant_id, 
        $trade_data['shift_date'],
        $trade_data['start_time'], 
        $trade_data['start_time'],
        $trade_data['end_time'], 
        $trade_data['end_time'],
        $trade_data['start_time'], 
        $trade_data['end_time']
    );
    mysqli_stmt_execute($stmt);
    $conflict_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($conflict_result) > 0) {
        ob_end_clean();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You already have a shift scheduled during this time']);
        exit();
    }

  	$tenant_query = "SELECT settings FROM to_tenants WHERE id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $tenant_result = mysqli_stmt_get_result($stmt);
    $tenant_settings_data = mysqli_fetch_assoc($tenant_result);
    $settings = json_decode($tenant_settings_data['settings'], true);

  	$requires_approval = true;
    if (isset($settings['trade_settings']['auto_approval']) && $settings['trade_settings']['auto_approval']) {
        $max_hours = $settings['trade_settings']['max_weekly_hours_auto'] ?? 40;
        
        if (checkWeeklyHourLimit($dbc, $user_id, $tenant_id, $trade_data['shift_date'], $trade_data['start_time'], $trade_data['end_time'], $max_hours)) {
            $requires_approval = false;
        }
    }

    try {
        mysqli_autocommit($dbc, false);

        if ($requires_approval) {
          	$update_trade_query = "UPDATE to_shift_trades SET requesting_user_id = ?, status = 'pending_approval', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_trade_query);
            mysqli_stmt_bind_param($stmt, 'ii', $user_id, $trade_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update trade status');
            }

            mysqli_commit($dbc);
            mysqli_autocommit($dbc, true);

            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Trade request submitted for admin approval',
                'requires_approval' => true
            ]);
        } else {
           	$update_trade_query = "UPDATE to_shift_trades SET requesting_user_id = ?, status = 'completed', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_trade_query);
            mysqli_stmt_bind_param($stmt, 'ii', $user_id, $trade_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to complete trade');
            }

           	$update_shift_query = "UPDATE to_shifts SET assigned_user_id = ?, status = 'scheduled', updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_shift_query);
            mysqli_stmt_bind_param($stmt, 'ii', $user_id, $trade_data['shift_id']);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to update shift assignment');
            }

            mysqli_commit($dbc);
            mysqli_autocommit($dbc, true);

            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Shift successfully picked up!',
                'requires_approval' => false
            ]);
        }

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process trade: ' . $e->getMessage()]);
    }
}

function getMyTrades($dbc, $user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

   	$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
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
            s.shift_date,
            s.start_time,
            s.end_time,
            d.name as department_name,
            jr.name as role_name,
            CASE 
                WHEN st.offering_user_id = ? THEN 'offering'
                WHEN st.requesting_user_id = ? THEN 'requesting'
                ELSE 'unknown'
            END as user_role,
            CASE 
                WHEN st.offering_user_id = ? THEN ru.display_name
                WHEN st.requesting_user_id = ? THEN ou.display_name
                ELSE NULL
            END as other_user_name
        FROM to_shift_trades st
        INNER JOIN to_shifts s ON st.shift_id = s.id
        INNER JOIN to_departments d ON s.department_id = d.id
        INNER JOIN to_job_roles jr ON s.job_role_id = jr.id
        LEFT JOIN users ou ON st.offering_user_id = ou.id
        LEFT JOIN users ru ON st.requesting_user_id = ru.id
        WHERE s.tenant_id = ? 
            AND (st.offering_user_id = ? OR st.requesting_user_id = ?)
            AND st.status IN ('placed_on_tradeboard', 'pending_approval', 'approved', 'completed')
        ORDER BY st.created_at DESC
    ";
    
    $stmt = mysqli_prepare($dbc, $trades_query);
    mysqli_stmt_bind_param($stmt, 'iiiiiii', 
        $user_id, $user_id, $user_id, $user_id, 
        $tenant_id, $user_id, $user_id
    );
    mysqli_stmt_execute($stmt);
    $trades_result = mysqli_stmt_get_result($stmt);

    $trades = [];
    while ($row = mysqli_fetch_assoc($trades_result)) {
        $other_user_name = $row['other_user_name'];
        if (!$other_user_name && $row['user_role'] == 'offering' && $row['requesting_user_id']) {
          	$user_query = "SELECT display_name, first_name, last_name FROM users WHERE id = ?";
            $stmt2 = mysqli_prepare($dbc, $user_query);
            mysqli_stmt_bind_param($stmt2, 'i', $row['requesting_user_id']);
            mysqli_stmt_execute($stmt2);
            $user_result = mysqli_stmt_get_result($stmt2);
            if ($user_data = mysqli_fetch_assoc($user_result)) {
                $other_user_name = $user_data['display_name'] ?: 
                                 trim($user_data['first_name'] . ' ' . $user_data['last_name']);
            }
        }

        $trades[] = [
            'id' => intval($row['id']),
            'shift_id' => intval($row['shift_id']),
            'shift_date' => $row['shift_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'department_name' => $row['department_name'],
            'role_name' => $row['role_name'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'user_role' => $row['user_role'],
            'other_user_name' => $other_user_name,
            'requesting_user_name' => $row['user_role'] == 'offering' ? $other_user_name : null
        ];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'trades' => $trades
    ]);
}

function cancelTrade($dbc, $user_id, $input) {
    if (!isset($input['trade_id']) || !isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Trade ID and tenant ID are required']);
        exit();
    }

    $trade_id = intval($input['trade_id']);
    $tenant_id = intval($input['tenant_id']);

  	$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }

  	$trade_query = "
        SELECT 
            st.id,
            st.shift_id,
            st.offering_user_id,
            st.requesting_user_id,
            st.status,
            s.tenant_id
        FROM to_shift_trades st
        INNER JOIN to_shifts s ON st.shift_id = s.id
        WHERE st.id = ? AND s.tenant_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $trade_query);
    mysqli_stmt_bind_param($stmt, 'ii', $trade_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $trade_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($trade_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Trade not found']);
        exit();
    }

    $trade_data = mysqli_fetch_assoc($trade_result);

   	if ($trade_data['offering_user_id'] != $user_id) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only cancel trades for your own shifts']);
        exit();
    }

   	if (!in_array($trade_data['status'], ['placed_on_tradeboard', 'pending_approval', 'approved'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This trade cannot be cancelled in its current status']);
        exit();
    }

    try {
        mysqli_autocommit($dbc, false);

       	$update_trade_query = "UPDATE to_shift_trades SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_trade_query);
        mysqli_stmt_bind_param($stmt, 'i', $trade_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to cancel trade');
        }

       	$update_shift_query = "UPDATE to_shifts SET status = 'scheduled', updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_shift_query);
        mysqli_stmt_bind_param($stmt, 'i', $trade_data['shift_id']);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to restore shift status');
        }

        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);

        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Trade cancelled successfully'
        ]);

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel trade: ' . $e->getMessage()]);
    }
}

function checkWeeklyHourLimit($dbc, $user_id, $tenant_id, $shift_date, $start_time, $end_time, $max_hours) {
 	$week_start = date('Y-m-d', strtotime('monday this week', strtotime($shift_date)));
    $week_end = date('Y-m-d', strtotime('sunday this week', strtotime($shift_date)));
   	$start_datetime = new DateTime($shift_date . ' ' . $start_time);
    $end_datetime = new DateTime($shift_date . ' ' . $end_time);
    $shift_hours = ($end_datetime->getTimestamp() - $start_datetime->getTimestamp()) / 3600;
    
   	$hours_query = "
        SELECT SUM(
            TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 3600
        ) as total_hours
        FROM to_shifts 
        WHERE assigned_user_id = ? 
            AND tenant_id = ? 
            AND shift_date BETWEEN ? AND ?
            AND status = 'scheduled'
    ";
    
    $stmt = mysqli_prepare($dbc, $hours_query);
    mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $tenant_id, $week_start, $week_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    $current_hours = floatval($data['total_hours'] ?? 0);
    $total_with_new_shift = $current_hours + $shift_hours;
    
    return $total_with_new_shift <= $max_hours;
}

?>