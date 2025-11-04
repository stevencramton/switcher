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
    include 'admin_schedule_publication_helper.php';
} catch (Exception $e) {
 	error_log('Schedule Publication - Configuration Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
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

if (!isset($input['action']) || !isset($input['tenant_id'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action and tenant_id are required']);
    exit();
}

$action = $input['action'];
$tenant_id = intval($input['tenant_id']);

if ($tenant_id <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid tenant_id']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);

if (!$stmt) {
  	error_log('Schedule Publication - User Query Prepare Failed: ' . mysqli_error($dbc));
    
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

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

if (!checkRole('admin_developer')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    
    if (!$stmt) {
      	error_log('Schedule Publication - Tenant Access Query Prepare Failed: ' . mysqli_error($dbc));
        
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

ob_end_clean();

switch ($action) {
    case 'get_publications':
        try {
         	$publications = getTenantPublications($dbc, $tenant_id);
            echo json_encode(['success' => true, 'publications' => $publications]);
        } catch (Exception $e) {
          	error_log('Schedule Publication - Get Publications Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error retrieving publications']);
        }
        break;
        
    case 'check_status':
        if (!isset($input['start_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start_date is required']);
            exit();
        }
        
        $start_date = $input['start_date'];
        $end_date = isset($input['end_date']) ? $input['end_date'] : $start_date;
        
      	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid start_date format']);
            exit();
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid end_date format']);
            exit();
        }
        
        try {
          	$status = checkSchedulePublication($dbc, $tenant_id, $start_date, $end_date);
            echo json_encode(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
        	error_log('Schedule Publication - Check Status Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error checking publication status']);
        }
        break;
        
    case 'publish':
        if (!isset($input['start_date']) || !isset($input['end_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start_date and end_date are required']);
            exit();
        }
        
        $start_date = $input['start_date'];
        $end_date = $input['end_date'];
        $notes = isset($input['notes']) ? $input['notes'] : null;
        
      	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid start_date format']);
            exit();
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid end_date format']);
            exit();
        }
        
        if (strtotime($start_date) > strtotime($end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start_date cannot be after end_date']);
            exit();
        }
        
        try {
         	$result = publishSchedule($dbc, $tenant_id, $start_date, $end_date, $user_id, $notes);
            echo json_encode($result);
        } catch (Exception $e) {
          	error_log('Schedule Publication - Publish Schedule Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error publishing schedule']);
        }
        break;
        
    case 'unpublish':
        if (!isset($input['start_date']) || !isset($input['end_date'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start_date and end_date are required']);
            exit();
        }
        
        $start_date = $input['start_date'];
        $end_date = $input['end_date'];
        $notes = isset($input['notes']) ? $input['notes'] : null;
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid start_date format']);
            exit();
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid end_date format']);
            exit();
        }
        
        try {
          	$result = unpublishSchedule($dbc, $tenant_id, $start_date, $end_date, $user_id, $notes);
            echo json_encode($result);
        } catch (Exception $e) {
         	error_log('Schedule Publication - Unpublish Schedule Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error unpublishing schedule']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

?>