<?php
/**
 * Captain's Log - Read Log Entries
 * 
 * AJAX endpoint to fetch and filter audit log entries
 * Place in: ajax/lh_captains_log/read_captains_log.php
 */
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

// Check role
if (!checkRole('lighthouse_captain')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(10, (int)$_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$event_type = isset($_GET['event_type']) ? trim($_GET['event_type']) : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT cl.*, 
          CONCAT(u.first_name, ' ', u.last_name) as user_name,
          u.user as username,
          CONCAT(tu.first_name, ' ', tu.last_name) as target_user_name
          FROM lh_captains_log cl
          LEFT JOIN users u ON cl.user_id = u.id
          LEFT JOIN users tu ON cl.target_user_id = tu.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM lh_captains_log cl WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if (!empty($category)) {
    $query .= " AND cl.event_category = ?";
    $count_query .= " AND cl.event_category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($event_type)) {
    $query .= " AND cl.event_type = ?";
    $count_query .= " AND cl.event_type = ?";
    $params[] = $event_type;
    $types .= 's';
}

if ($user_id > 0) {
    $query .= " AND cl.user_id = ?";
    $count_query .= " AND cl.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($from_date)) {
    $query .= " AND DATE(cl.created_date) >= ?";
    $count_query .= " AND DATE(cl.created_date) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $query .= " AND DATE(cl.created_date) <= ?";
    $count_query .= " AND DATE(cl.created_date) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $query .= " AND (cl.event_type LIKE ? OR cl.entity_reference LIKE ? OR cl.old_value LIKE ? OR cl.new_value LIKE ? OR cl.details LIKE ?)";
    $count_query .= " AND (cl.event_type LIKE ? OR cl.entity_reference LIKE ? OR cl.old_value LIKE ? OR cl.new_value LIKE ? OR cl.details LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sssss';
}

// Get total count
$count_stmt = mysqli_prepare($dbc, $count_query);
if ($count_stmt) {
    if (!empty($params)) {
        $bind_params = [$types];
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$count_stmt, 'bind_param'], $bind_params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = mysqli_fetch_assoc($count_result)['total'];
    mysqli_stmt_close($count_stmt);
} else {
    $total = 0;
}

$total_pages = ceil($total / $per_page);

// Add ordering and pagination
$query .= " ORDER BY cl.created_date DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Execute main query
$stmt = mysqli_prepare($dbc, $query);
$data = [];

if ($stmt) {
    if (!empty($params)) {
        $bind_params = [$types];
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'log_id' => $row['log_id'],
            'event_type' => $row['event_type'],
            'event_category' => $row['event_category'],
            'entity_type' => $row['entity_type'],
            'entity_id' => $row['entity_id'],
            'entity_reference' => $row['entity_reference'],
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'username' => $row['username'],
            'target_user_id' => $row['target_user_id'],
            'target_user_name' => $row['target_user_name'],
            'old_value' => $row['old_value'],
            'new_value' => $row['new_value'],
            'details' => $row['details'],
            'ip_address' => $row['ip_address'],
            'created_date' => $row['created_date'],
            'created_date_formatted' => date('M d, Y g:i A', strtotime($row['created_date']))
        ];
    }
    
    mysqli_stmt_close($stmt);
}

// Get statistics
$stats = getLogStats($dbc);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $data,
    'total' => (int)$total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => (int)$total_pages,
    'stats' => $stats
]);

/**
 * Get log statistics
 */
function getLogStats($dbc) {
    $stats = [
        'total' => 0,
        'signals' => 0,
        'config' => 0,
        'today' => 0
    ];
    
    // Total count
    $result = mysqli_query($dbc, "SELECT COUNT(*) as count FROM lh_captains_log");
    if ($result) {
        $stats['total'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // Signal events count
    $result = mysqli_query($dbc, "SELECT COUNT(*) as count FROM lh_captains_log WHERE event_category = 'signal'");
    if ($result) {
        $stats['signals'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // Configuration changes count
    $result = mysqli_query($dbc, "SELECT COUNT(*) as count FROM lh_captains_log WHERE event_category = 'configuration'");
    if ($result) {
        $stats['config'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // Today's events count
    $result = mysqli_query($dbc, "SELECT COUNT(*) as count FROM lh_captains_log WHERE DATE(created_date) = CURDATE()");
    if ($result) {
        $stats['today'] = mysqli_fetch_assoc($result)['count'];
    }
    
    return $stats;
}
?>