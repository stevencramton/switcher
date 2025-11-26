<?php
/**
 * Captain's Log - Export to CSV
 * 
 * Endpoint to export audit log entries to CSV format
 * Place in: ajax/lh_captains_log/export_captains_log.php
 */
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die('Not authenticated');
}

// Check role
if (!checkRole('lighthouse_captain')) {
    http_response_code(403);
    die('Access denied');
}

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

$params = [];
$types = '';

// Apply filters
if (!empty($category)) {
    $query .= " AND cl.event_category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($event_type)) {
    $query .= " AND cl.event_type = ?";
    $params[] = $event_type;
    $types .= 's';
}

if ($user_id > 0) {
    $query .= " AND cl.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($from_date)) {
    $query .= " AND DATE(cl.created_date) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $query .= " AND DATE(cl.created_date) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $query .= " AND (cl.event_type LIKE ? OR cl.entity_reference LIKE ? OR cl.old_value LIKE ? OR cl.new_value LIKE ? OR cl.details LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sssss';
}

$query .= " ORDER BY cl.created_date DESC";

// Limit export to 10,000 records for performance
$query .= " LIMIT 10000";

// Execute query
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
    
    // Set headers for CSV download
    $filename = 'captains_log_export_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header row
    fputcsv($output, [
        'Log ID',
        'Date/Time',
        'Event Type',
        'Category',
        'Entity Type',
        'Entity Reference',
        'User',
        'Username',
        'Target User',
        'Old Value',
        'New Value',
        'Details',
        'IP Address'
    ]);
    
    // Write data rows
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['log_id'],
            date('Y-m-d H:i:s', strtotime($row['created_date'])),
            ucwords(str_replace('_', ' ', $row['event_type'])),
            ucfirst($row['event_category']),
            $row['entity_type'],
            $row['entity_reference'],
            $row['user_name'],
            $row['username'],
            $row['target_user_name'],
            $row['old_value'],
            $row['new_value'],
            $row['details'],
            $row['ip_address']
        ]);
    }
    
    fclose($output);
    mysqli_stmt_close($stmt);
    
    // Log the export action
    logCaptainsEvent(
        $dbc,
        'export_generated',
        'system',
        null,
        null,
        'Captains Log Export',
        $_SESSION['id'],
        null,
        null,
        $filename,
        json_encode(['filters' => [
            'category' => $category,
            'event_type' => $event_type,
            'user_id' => $user_id,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'search' => $search
        ]])
    );
    
} else {
    http_response_code(500);
    die('Failed to prepare query');
}

/**
 * Log an event to the Captain's Log
 */
function logCaptainsEvent($dbc, $event_type, $event_category, $entity_type, $entity_id, $entity_reference, $user_id, $target_user_id, $old_value, $new_value, $details) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;
    
    $query = "INSERT INTO lh_captains_log 
              (event_type, event_category, entity_type, entity_id, entity_reference, user_id, target_user_id, old_value, new_value, details, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssississsss',
            $event_type,
            $event_category,
            $entity_type,
            $entity_id,
            $entity_reference,
            $user_id,
            $target_user_id,
            $old_value,
            $new_value,
            $details,
            $ip_address,
            $user_agent
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>