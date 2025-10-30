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

try {
  	if (!isset($dbc) || !$dbc) {
        throw new Exception('Database connection not available');
    }
    
   	if ($end_date === null) {
        $end_date = $start_date;
    }
    
    $query = "
        SELECT sp.id, sp.tenant_id, sp.start_date, sp.end_date, sp.is_published, sp.published_by, sp.unpublished_by, sp.notes, sp.created_at, sp.updated_at,
               DATE_FORMAT(DATE_SUB(COALESCE(sp.published_at, sp.created_at), INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as published_at,
               DATE_FORMAT(DATE_SUB(sp.unpublished_at, INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as unpublished_at,
               pub_user.first_name as published_by_first, 
               pub_user.last_name as published_by_last,
               unpub_user.first_name as unpublished_by_first, 
               unpub_user.last_name as unpublished_by_last
        FROM to_schedule_publication sp
        LEFT JOIN users pub_user ON sp.published_by = pub_user.id
        LEFT JOIN users unpub_user ON sp.unpublished_by = unpub_user.id
        WHERE sp.tenant_id = ? 
        AND sp.start_date <= ? 
        AND sp.end_date >= ?
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
    
    $is_published = false;
    $published_ranges = [];
    $active_publication = null;
    
    foreach ($publications as $pub) {
        if ($pub['is_published'] == 1) {
            $is_published = true;
            $published_ranges[] = $pub;
            if ($active_publication === null) {
                $active_publication = $pub;
            }
        }
    }
    
	echo json_encode([
        'success' => true,
        'is_published' => $is_published,
        'publications' => $publications,
        'active_publication' => $active_publication,
        'published_ranges' => $published_ranges,
        'published_count' => count($published_ranges),
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'checked_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Publication status check error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred while checking publication status'
    ]);
}
?>