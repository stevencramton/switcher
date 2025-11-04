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

if (!checkRole('timesown_tenant')) {
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
$user_id = $user_data['id'];

switch ($action) {
    case 'get_trade_settings':
        getTradeSettings($dbc, $user_id, $input);
        break;
    
    case 'update_trade_settings':
        updateTradeSettings($dbc, $user_id, $input);
        break;
    
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getTradeSettings($dbc, $user_id, $input) {
    if (!isset($input['tenant_id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);

    if (!checkRole('admin_developer')) {
        $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $tenant_access_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $access_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($access_result) === 0) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
            exit();
        }
    }

    $tenant_query = "SELECT name, settings FROM to_tenants WHERE id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $tenant_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($tenant_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tenant not found']);
        exit();
    }

    $tenant_data = mysqli_fetch_assoc($tenant_result);
    $settings = json_decode($tenant_data['settings'], true) ?? [];
	
	$trade_settings = $settings['trade_settings'] ?? [];
 	$default_trade_settings = [
        'allow_trades' => false,
        'auto_approval' => false,
        'require_admin_approval' => true,
        'max_weekly_hours_auto' => 40,
        'max_weekly_hours_manual' => 30,
        'allow_overtime_trades' => false,
        'trade_deadline_hours' => 24
    ];

    $trade_settings = array_merge($default_trade_settings, $trade_settings);

    ob_end_clean();
    echo json_encode([
        'success' => true, 
        'tenant_name' => $tenant_data['name'],
        'trade_settings' => $trade_settings
    ]);
}

function updateTradeSettings($dbc, $user_id, $input) {
    if (!isset($input['tenant_id']) || !isset($input['trade_settings'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID and trade settings are required']);
        exit();
    }

    $tenant_id = intval($input['tenant_id']);
    $new_trade_settings = $input['trade_settings'];

	if (!checkRole('admin_developer')) {
        $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
        $stmt = mysqli_prepare($dbc, $tenant_access_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        $access_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($access_result) === 0) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
            exit();
        }
    }

  	$tenant_query = "SELECT settings FROM to_tenants WHERE id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $tenant_result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($tenant_result) === 0) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tenant not found']);
        exit();
    }

    $tenant_data = mysqli_fetch_assoc($tenant_result);
    $current_settings = json_decode($tenant_data['settings'], true) ?? [];

  	$validation_result = validateTradeSettings($new_trade_settings);
    if (!$validation_result['valid']) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $validation_result['message']]);
        exit();
    }

  	$current_settings['trade_settings'] = $validation_result['settings'];
    $updated_settings_json = json_encode($current_settings);

    try {
        mysqli_autocommit($dbc, false);

      	$update_query = "UPDATE to_tenants SET settings = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'si', $updated_settings_json, $tenant_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update tenant settings');
        }

      	$audit_query = "
            INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'UPDATE', 'to_tenants', ?, ?, ?, ?, ?, NOW())
        ";
        
        $old_values = json_encode(['trade_settings' => $current_settings['trade_settings'] ?? []]);
        $new_values = json_encode(['trade_settings' => $validation_result['settings']]);
     	$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'iisssss', 
            $tenant_id, $user_id, $tenant_id, 
            $old_values, $new_values, $ip_address, $user_agent
        );
        mysqli_stmt_execute($stmt);

        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);

        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Trade settings updated successfully',
            'trade_settings' => $validation_result['settings']
        ]);

    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
    }
}

function validateTradeSettings($settings) {
    $valid_settings = [];
    $errors = [];
	
	$valid_settings['allow_trades'] = isset($settings['allow_trades']) ? (bool)$settings['allow_trades'] : false;
	$valid_settings['auto_approval'] = isset($settings['auto_approval']) ? (bool)$settings['auto_approval'] : false;
	$valid_settings['require_admin_approval'] = isset($settings['require_admin_approval']) ? (bool)$settings['require_admin_approval'] : true;

  	if (isset($settings['max_weekly_hours_auto'])) {
        $hours = intval($settings['max_weekly_hours_auto']);
        if ($hours < 1 || $hours > 80) {
            $errors[] = 'Max weekly hours for auto approval must be between 1 and 80';
        } else {
            $valid_settings['max_weekly_hours_auto'] = $hours;
        }
    } else {
        $valid_settings['max_weekly_hours_auto'] = 40;
    }

  	if (isset($settings['max_weekly_hours_manual'])) {
        $hours = intval($settings['max_weekly_hours_manual']);
        if ($hours < 1 || $hours > 80) {
            $errors[] = 'Max weekly hours for manual approval must be between 1 and 80';
        } else {
            $valid_settings['max_weekly_hours_manual'] = $hours;
        }
    } else {
        $valid_settings['max_weekly_hours_manual'] = 30;
    }

 	$valid_settings['allow_overtime_trades'] = isset($settings['allow_overtime_trades']) ? (bool)$settings['allow_overtime_trades'] : false;

  	if (isset($settings['trade_deadline_hours'])) {
        $hours = intval($settings['trade_deadline_hours']);
        if ($hours < 1 || $hours > 168) {
            $errors[] = 'Trade deadline must be between 1 and 168 hours';
        } else {
            $valid_settings['trade_deadline_hours'] = $hours;
        }
    } else {
        $valid_settings['trade_deadline_hours'] = 24;
    }

  	if ($valid_settings['auto_approval'] && $valid_settings['require_admin_approval']) {
        $errors[] = 'Cannot have both auto approval and required admin approval enabled';
    }

    if ($valid_settings['max_weekly_hours_auto'] < $valid_settings['max_weekly_hours_manual']) {
        $errors[] = 'Max weekly hours for auto approval cannot be less than manual approval limit';
    }

    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => 'Validation errors: ' . implode(', ', $errors)
        ];
    }

    return [
        'valid' => true,
        'settings' => $valid_settings
    ];
}
?>