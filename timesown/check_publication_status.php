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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$required_params = ['tenant_id', 'start_date', 'end_date'];
foreach ($required_params as $param) {
    if (!isset($_POST[$param]) || empty($_POST[$param])) {
        echo json_encode([
            'success' => false, 
            'message' => "Missing required parameter: {$param}"
        ]);
        exit;
    }
}

$tenant_id = intval($_POST['tenant_id']);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || 
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid date format. Expected YYYY-MM-DD'
    ]);
    exit;
}

if ($tenant_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid tenant ID'
    ]);
    exit;
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

$tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $tenant_access_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$tenant_access_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($tenant_access_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
    exit();
}

try {
 	if (!isset($dbc) || !$dbc) {
        throw new Exception('Database connection not available');
    }
    
 	$query = "
        SELECT sp.id, sp.tenant_id, sp.start_date, sp.end_date, sp.is_published, sp.published_by, sp.notes, sp.created_at,
               DATE_FORMAT(DATE_SUB(COALESCE(sp.published_at, sp.created_at), INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as published_at,
               pub_user.first_name as published_by_first, 
               pub_user.last_name as published_by_last
        FROM to_schedule_publication sp
        LEFT JOIN users pub_user ON sp.published_by = pub_user.id
        WHERE sp.tenant_id = ? 
        AND sp.start_date <= ? 
        AND sp.end_date >= ?
        AND sp.is_published = 1
        ORDER BY sp.start_date, sp.end_date
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $end_date, $start_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $publications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $publications[] = $row;
    }
    
    $is_published = count($publications) > 0;
    $active_publication = null;
    
    if ($is_published) {
        $active_publication = $publications[0];
    }
    
 	echo json_encode([
        'success' => true,
        'is_published' => $is_published,
        'publications' => $publications,
        'active_publication' => $active_publication,
        'published_count' => count($publications),
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'checked_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Publication status check error (user view): " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred while checking publication status'
    ]);
}
?>