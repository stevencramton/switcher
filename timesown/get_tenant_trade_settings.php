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

if (!$input && isset($_GET['tenant_id'])) {
    $input = ['action' => 'get_trade_settings', 'tenant_id' => $_GET['tenant_id']];
}

if (!isset($input['action'])) {
    $input['action'] = 'get_trade_settings';
}

if (!isset($input['tenant_id'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
    exit();
}

$action = $input['action'];
$tenant_id = intval($input['tenant_id']);
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
        getTradeSettings($dbc, $user_id, $tenant_id);
        break;
    
    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getTradeSettings($dbc, $user_id, $tenant_id) {
   	if (!checkRole('timesown_user')) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
        exit();
    }

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
  	$trade_settings_data = $settings['trade_settings'] ?? [];
 	
	$default_trade_settings = [
        'allow_trades' => false,
        'auto_approval' => false,
        'require_admin_approval' => true,
        'max_weekly_hours_auto' => 40,
        'max_weekly_hours_manual' => 30,
        'allow_overtime_trades' => false,
        'trade_deadline_hours' => 24
    ];

 	$trade_settings = array_merge($default_trade_settings, $trade_settings_data);

  	if (isset($settings['allow_trades']) && !isset($trade_settings_data['allow_trades'])) {
        $trade_settings['allow_trades'] = (bool)$settings['allow_trades'];
    }

   	ob_end_clean();
    echo json_encode([
        'success' => true, 
        'tenant_name' => $tenant_data['name'],
        'trade_settings' => $trade_settings
    ]);
}
?>