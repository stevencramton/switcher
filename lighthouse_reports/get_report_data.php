<?php
session_start();
date_default_timezone_set('America/New_York');

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // Allow direct access for testing, but in production you might want to restrict this
}

include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Check authentication
if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check role
if (!checkRole('lighthouse_maritime')) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

// Input validation helper functions
function validateDate($date) {
    if (empty($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInteger($value, $default = null, $min = null, $max = null) {
    if ($value === '' || $value === null) {
        return $default;
    }
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false) {
        return $default;
    }
    if ($min !== null && $int < $min) {
        return $default;
    }
    if ($max !== null && $int > $max) {
        return $default;
    }
    return $int;
}

function sanitizeIntegerArray($csv_string) {
    $result = [];
    if (empty($csv_string)) {
        return $result;
    }
    $parts = explode(',', $csv_string);
    foreach ($parts as $part) {
        $trimmed = trim($part);
        if ($trimmed !== '' && is_numeric($trimmed)) {
            $int = (int)$trimmed;
            if ($int > 0) {
                $result[] = $int;
            }
        }
    }
    return $result;
}

// Get and validate parameters
$type = $_GET['type'] ?? 'stats';
$allowed_types = ['stats', 'timeline', 'by_status', 'by_dock', 'by_dock_detail', 'by_priority', 
                  'by_service', 'by_signal_type', 'by_keeper', 'by_user', 'table', 'debug'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    exit();
}

// Validate and sanitize date inputs
$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end = date('Y-m-d');

$start_date = $_GET['start_date'] ?? $default_start;
if (!validateDate($start_date)) {
    $start_date = $default_start;
}

$end_date = $_GET['end_date'] ?? $default_end;
if (!validateDate($end_date)) {
    $end_date = $default_end;
}

// Validate integer parameters
$dock_id = sanitizeInteger($_GET['dock_id'] ?? null, null, 1);
$priority_id = sanitizeInteger($_GET['priority_id'] ?? null, null, 1);
$service_id = sanitizeInteger($_GET['service_id'] ?? null, null, 1);

// Validate status parameter
$status = $_GET['status'] ?? '';
$allowed_status_values = ['open', 'closed', ''];
if (!in_array($status, $allowed_status_values) && !is_numeric($status)) {
    $status = '';
}
if (is_numeric($status)) {
    $status = sanitizeInteger($status, '', 1);
}

// Sanitize search input - limit length and remove potentially dangerous characters
$search = $_GET['search'] ?? '';
if ($search !== '') {
    $search = substr($search, 0, 200); // Limit length
    // Note: mysqli_real_escape_string is not needed with prepared statements
}

// Multi-select filters with validation
$user_ids = [];
if (isset($_GET['user_ids']) && trim($_GET['user_ids']) !== '') {
    $user_ids = sanitizeIntegerArray($_GET['user_ids']);
}

$keeper_ids = [];
$include_unassigned = false;
if (isset($_GET['keeper_ids']) && trim($_GET['keeper_ids']) !== '') {
    $keeper_parts = explode(',', $_GET['keeper_ids']);
    foreach ($keeper_parts as $part) {
        $part = trim($part);
        if ($part === 'unassigned') {
            $include_unassigned = true;
        } elseif ($part !== '' && is_numeric($part) && (int)$part > 0) {
            $keeper_ids[] = (int)$part;
        }
    }
}

// Get closed state IDs using prepared statement for consistency
$closed_states = [];
$closed_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_closed_resolution = 1";
$closed_stmt = mysqli_prepare($dbc, $closed_query);
if ($closed_stmt) {
    mysqli_stmt_execute($closed_stmt);
    $closed_result = mysqli_stmt_get_result($closed_stmt);
    while ($row = mysqli_fetch_assoc($closed_result)) {
        $closed_states[] = (int)$row['sea_state_id'];
    }
    mysqli_stmt_close($closed_stmt);
}

// Build WHERE clause with proper parameterization
function buildWhereClause($start_date, $end_date, $dock_id, $status, $priority_id, $service_id, $search, $closed_states, $user_ids = [], $keeper_ids = [], $include_unassigned = false, $prefix = 's') {
    $where = ["{$prefix}.is_deleted = 0"];
    $params = [];
    $types = '';
    
    // Date range - already validated
    if ($start_date) {
        $where[] = "DATE({$prefix}.sent_date) >= ?";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date) {
        $where[] = "DATE({$prefix}.sent_date) <= ?";
        $params[] = $end_date;
        $types .= 's';
    }
    
    // Dock filter - already validated as integer
    if ($dock_id !== null) {
        $where[] = "{$prefix}.dock_id = ?";
        $params[] = $dock_id;
        $types .= 'i';
    }
    
    // Status filter - use parameterized IN clause for closed_states
    if ($status === 'open' && !empty($closed_states)) {
        $placeholders = implode(',', array_fill(0, count($closed_states), '?'));
        $where[] = "{$prefix}.sea_state_id NOT IN ($placeholders)";
        foreach ($closed_states as $state_id) {
            $params[] = $state_id;
            $types .= 'i';
        }
    } elseif ($status === 'closed' && !empty($closed_states)) {
        $placeholders = implode(',', array_fill(0, count($closed_states), '?'));
        $where[] = "{$prefix}.sea_state_id IN ($placeholders)";
        foreach ($closed_states as $state_id) {
            $params[] = $state_id;
            $types .= 'i';
        }
    } elseif (is_numeric($status)) {
        $where[] = "{$prefix}.sea_state_id = ?";
        $params[] = (int)$status;
        $types .= 'i';
    }
    
    // Priority filter - already validated
    if ($priority_id !== null) {
        $where[] = "{$prefix}.priority_id = ?";
        $params[] = $priority_id;
        $types .= 'i';
    }
    
    // Service filter - already validated
    if ($service_id !== null) {
        $where[] = "{$prefix}.service_id = ?";
        $params[] = $service_id;
        $types .= 'i';
    }
    
    // User IDs filter (multi-select) - already validated as integers
    if (!empty($user_ids)) {
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $where[] = "{$prefix}.sent_by IN ($placeholders)";
        foreach ($user_ids as $uid) {
            $params[] = $uid;
            $types .= 'i';
        }
    }
    
    // Keeper IDs filter (multi-select) - already validated
    if (!empty($keeper_ids) || $include_unassigned) {
        $keeper_conditions = [];
        if (!empty($keeper_ids)) {
            $placeholders = implode(',', array_fill(0, count($keeper_ids), '?'));
            $keeper_conditions[] = "{$prefix}.keeper_assigned IN ($placeholders)";
            foreach ($keeper_ids as $kid) {
                $params[] = $kid;
                $types .= 'i';
            }
        }
        if ($include_unassigned) {
            $keeper_conditions[] = "{$prefix}.keeper_assigned IS NULL";
        }
        if (!empty($keeper_conditions)) {
            $where[] = "(" . implode(' OR ', $keeper_conditions) . ")";
        }
    }
    
    // Search filter - already length-limited
    if ($search !== '') {
        $where[] = "({$prefix}.signal_number LIKE ? OR {$prefix}.title LIKE ? OR {$prefix}.message LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    return [
        'where' => implode(' AND ', $where),
        'params' => $params,
        'types' => $types
    ];
}

// Helper function to create parameterized IN clause for closed states in SELECT
function buildClosedStatesCondition($closed_states, &$params, &$types, $prefix = 's') {
    if (empty($closed_states)) {
        return '0'; // Safe fallback
    }
    $placeholders = implode(',', array_fill(0, count($closed_states), '?'));
    foreach ($closed_states as $state_id) {
        $params[] = $state_id;
        $types .= 'i';
    }
    return $placeholders;
}

$whereData = buildWhereClause($start_date, $end_date, $dock_id, $status, $priority_id, $service_id, $search, $closed_states, $user_ids, $keeper_ids, $include_unassigned);

// Debug mode - add ?debug=1 to see filter info
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

try {
    // If debug mode, return filter information
    if ($debug_mode && $type === 'debug') {
        echo json_encode([
            'success' => true,
            'debug' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'user_ids_raw' => $_GET['user_ids'] ?? 'not set',
                'user_ids_parsed' => $user_ids,
                'keeper_ids_raw' => $_GET['keeper_ids'] ?? 'not set',
                'keeper_ids_parsed' => $keeper_ids,
                'include_unassigned' => $include_unassigned,
                'where_clause' => $whereData['where'],
                'param_types' => $whereData['types'],
                'param_count' => count($whereData['params']),
                'closed_states_count' => count($closed_states)
            ]
        ]);
        exit;
    }
    
    switch ($type) {
        case 'stats':
            // Get high priority IDs (top 2 by order)
            $high_priority_ids = [];
            $hp_query = "SELECT priority_id FROM lh_priorities WHERE is_active = 1 ORDER BY priority_order DESC LIMIT 2";
            $hp_stmt = mysqli_prepare($dbc, $hp_query);
            if ($hp_stmt) {
                mysqli_stmt_execute($hp_stmt);
                $hp_result = mysqli_stmt_get_result($hp_stmt);
                while ($row = mysqli_fetch_assoc($hp_result)) {
                    $high_priority_ids[] = (int)$row['priority_id'];
                }
                mysqli_stmt_close($hp_stmt);
            }
            
            // Build query with parameterized closed states
            // Each use of closed_states in the query needs its own set of placeholders and params
            $stats_params = $whereData['params'];
            $stats_types = $whereData['types'];
            
            // First use: open_count (NOT IN)
            $closed_not_in_1 = '';
            if (!empty($closed_states)) {
                $closed_not_in_1 = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $stats_params[] = $state_id;
                    $stats_types .= 'i';
                }
            } else {
                $closed_not_in_1 = '0';
            }
            
            // Second use: closed_count (IN)
            $closed_in = '';
            if (!empty($closed_states)) {
                $closed_in = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $stats_params[] = $state_id;
                    $stats_types .= 'i';
                }
            } else {
                $closed_in = '0';
            }
            
            // Third use: high_priority condition (NOT IN)
            $closed_not_in_2 = '';
            if (!empty($closed_states)) {
                $closed_not_in_2 = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $stats_params[] = $state_id;
                    $stats_types .= 'i';
                }
            } else {
                $closed_not_in_2 = '0';
            }
            
            // High priority placeholders
            $hp_in = '';
            if (!empty($high_priority_ids)) {
                $hp_in = implode(',', array_fill(0, count($high_priority_ids), '?'));
                foreach ($high_priority_ids as $hp_id) {
                    $stats_params[] = $hp_id;
                    $stats_types .= 'i';
                }
            } else {
                $hp_in = '0';
            }
            
            $stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN ($closed_not_in_1) THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN ($closed_in) THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN s.sea_state_id NOT IN ($closed_not_in_2) AND s.priority_id IN ($hp_in) THEN 1 ELSE 0 END) as `high_priority`,
                AVG(CASE WHEN s.resolved_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, s.sent_date, s.resolved_date) ELSE NULL END) as avg_resolution_hours
            FROM lh_signals s
            WHERE " . $whereData['where'];
            
            $stmt = mysqli_prepare($dbc, $stats_query);
            if (!$stmt) {
                error_log("Stats query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($stats_params)) {
                mysqli_stmt_bind_param($stmt, $stats_types, ...$stats_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Stats query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            $stats = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total' => (int)$stats['total'],
                    'open' => (int)$stats['open_count'],
                    'closed' => (int)$stats['closed_count'],
                    'high_priority' => (int)$stats['high_priority'],
                    'avg_resolution_hours' => round($stats['avg_resolution_hours'] ?? 0, 1)
                ]
            ]);
            break;
            
        case 'timeline':
            // Determine grouping based on date range
            $date1 = new DateTime($start_date);
            $date2 = new DateTime($end_date);
            $diff = $date1->diff($date2)->days;
            
            if ($diff <= 31) {
                $date_format = '%Y-%m-%d';
                $php_format = 'M j';
            } elseif ($diff <= 90) {
                $date_format = '%Y-%u';
                $php_format = 'Week W';
            } else {
                $date_format = '%Y-%m';
                $php_format = 'M Y';
            }
            
            $timeline_params = $whereData['params'];
            $timeline_types = $whereData['types'];
            $closed_in = buildClosedStatesCondition($closed_states, $timeline_params, $timeline_types);
            
            $timeline_query = "SELECT 
                DATE_FORMAT(s.sent_date, ?) as period,
                MIN(DATE(s.sent_date)) as period_date,
                COUNT(*) as created,
                SUM(CASE WHEN s.sea_state_id IN ($closed_in) THEN 1 ELSE 0 END) as closed
            FROM lh_signals s
            WHERE " . $whereData['where'] . "
            GROUP BY period
            ORDER BY period_date ASC";
            
            // Add date_format as first parameter
            array_unshift($timeline_params, $date_format);
            $timeline_types = 's' . $timeline_types;
            
            $stmt = mysqli_prepare($dbc, $timeline_query);
            if (!$stmt) {
                error_log("Timeline query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($timeline_params)) {
                mysqli_stmt_bind_param($stmt, $timeline_types, ...$timeline_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Timeline query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = [];
            $created = [];
            $closed = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $date = new DateTime($row['period_date']);
                $labels[] = $date->format($php_format);
                $created[] = (int)$row['created'];
                $closed[] = (int)$row['closed'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'created' => $created, 'closed' => $closed]]);
            break;
            
        case 'by_status':
            $status_query = "SELECT 
                ss.sea_state_name as label,
                ss.sea_state_color as color,
                COUNT(s.signal_id) as value
            FROM lh_sea_states ss
            LEFT JOIN lh_signals s ON s.sea_state_id = ss.sea_state_id AND " . $whereData['where'] . "
            WHERE ss.is_active = 1
            GROUP BY ss.sea_state_id
            HAVING value > 0
            ORDER BY ss.sea_state_order ASC";
            
            $stmt = mysqli_prepare($dbc, $status_query);
            if (!$stmt) {
                error_log("Status query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Status query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_dock':
            $dock_query = "SELECT 
                d.dock_name as label,
                d.dock_color as color,
                COUNT(s.signal_id) as value
            FROM lh_docks d
            LEFT JOIN lh_signals s ON s.dock_id = d.dock_id AND " . $whereData['where'] . "
            WHERE d.is_active = 1
            GROUP BY d.dock_id
            HAVING value > 0
            ORDER BY value DESC";
            
            $stmt = mysqli_prepare($dbc, $dock_query);
            if (!$stmt) {
                error_log("Dock query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Dock query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_dock_detail':
            $dock_params = $whereData['params'];
            $dock_types = $whereData['types'];
            
            // First use: open_count (NOT IN)
            $closed_not_in = '';
            if (!empty($closed_states)) {
                $closed_not_in = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $dock_params[] = $state_id;
                    $dock_types .= 'i';
                }
            } else {
                $closed_not_in = '0';
            }
            
            // Second use: closed_count (IN)
            $closed_in = '';
            if (!empty($closed_states)) {
                $closed_in = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $dock_params[] = $state_id;
                    $dock_types .= 'i';
                }
            } else {
                $closed_in = '0';
            }
            
            $dock_query = "SELECT 
                d.dock_name as label,
                d.dock_color as color,
                COUNT(s.signal_id) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN ($closed_not_in) THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN ($closed_in) THEN 1 ELSE 0 END) as closed_count
            FROM lh_docks d
            LEFT JOIN lh_signals s ON s.dock_id = d.dock_id AND " . $whereData['where'] . "
            WHERE d.is_active = 1
            GROUP BY d.dock_id
            HAVING total > 0
            ORDER BY total DESC";
            
            $stmt = mysqli_prepare($dbc, $dock_query);
            if (!$stmt) {
                error_log("Dock detail query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($dock_params)) {
                mysqli_stmt_bind_param($stmt, $dock_types, ...$dock_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Dock detail query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = []; $open = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['total'];
                $colors[] = $row['color'];
                $open[] = (int)$row['open_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors, 'open' => $open, 'closed' => $closed]]);
            break;
            
        case 'by_priority':
            $priority_query = "SELECT 
                p.priority_name as label,
                p.priority_color as color,
                COUNT(s.signal_id) as value
            FROM lh_priorities p
            LEFT JOIN lh_signals s ON s.priority_id = p.priority_id AND " . $whereData['where'] . "
            WHERE p.is_active = 1
            GROUP BY p.priority_id
            HAVING value > 0
            ORDER BY p.priority_order ASC";
            
            $stmt = mysqli_prepare($dbc, $priority_query);
            if (!$stmt) {
                error_log("Priority query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Priority query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_service':
            $service_params = $whereData['params'];
            $service_types = $whereData['types'];
            
            // First use: open_count (NOT IN)
            $closed_not_in = '';
            if (!empty($closed_states)) {
                $closed_not_in = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $service_params[] = $state_id;
                    $service_types .= 'i';
                }
            } else {
                $closed_not_in = '0';
            }
            
            // Second use: closed_count (IN)
            $closed_in = '';
            if (!empty($closed_states)) {
                $closed_in = implode(',', array_fill(0, count($closed_states), '?'));
                foreach ($closed_states as $state_id) {
                    $service_params[] = $state_id;
                    $service_types .= 'i';
                }
            } else {
                $closed_in = '0';
            }
            
            $service_query = "SELECT 
                COALESCE(srv.service_name, 'Unassigned') as label,
                COALESCE(srv.service_color, '#9ca3af') as color,
                COUNT(s.signal_id) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN ($closed_not_in) THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN ($closed_in) THEN 1 ELSE 0 END) as closed_count
            FROM lh_signals s
            LEFT JOIN lh_services srv ON s.service_id = srv.service_id
            WHERE " . $whereData['where'] . "
            GROUP BY s.service_id
            ORDER BY total DESC";
            
            $stmt = mysqli_prepare($dbc, $service_query);
            if (!$stmt) {
                error_log("Service query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($service_params)) {
                mysqli_stmt_bind_param($stmt, $service_types, ...$service_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Service query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = []; $open = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['total'];
                $colors[] = $row['color'];
                $open[] = (int)$row['open_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors, 'open' => $open, 'closed' => $closed]]);
            break;
            
        case 'by_signal_type':
            // Whitelist for signal types
            $type_colors = [
                'feedback' => '#f59e0b',
                'feature_request' => '#3b82f6',
                'bug_report' => '#ef4444',
                'other' => '#6b7280'
            ];
            
            $type_query = "SELECT 
                s.signal_type as type_value,
                COUNT(*) as count
            FROM lh_signals s
            WHERE " . $whereData['where'] . "
            GROUP BY s.signal_type
            ORDER BY count DESC";
            
            $stmt = mysqli_prepare($dbc, $type_query);
            if (!$stmt) {
                error_log("Signal type query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Signal type query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $typeVal = $row['type_value'] ?: 'other';
                $labels[] = ucwords(str_replace('_', ' ', $typeVal));
                $values[] = (int)$row['count'];
                $colors[] = $type_colors[$typeVal] ?? '#6b7280';
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_keeper':
            $keeper_params = $whereData['params'];
            $keeper_types = $whereData['types'];
            $closed_in = buildClosedStatesCondition($closed_states, $keeper_params, $keeper_types);
            
            $keeper_query = "SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as keeper_name,
                COUNT(s.signal_id) as assigned_count,
                SUM(CASE WHEN s.sea_state_id IN ($closed_in) THEN 1 ELSE 0 END) as closed_count
            FROM users u
            INNER JOIN lh_signals s ON s.keeper_assigned = u.id AND " . $whereData['where'] . "
            WHERE u.account_delete = 0
            GROUP BY u.id
            ORDER BY assigned_count DESC
            LIMIT 10";
            
            $stmt = mysqli_prepare($dbc, $keeper_query);
            if (!$stmt) {
                error_log("Keeper query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($keeper_params)) {
                mysqli_stmt_bind_param($stmt, $keeper_types, ...$keeper_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Keeper query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $assigned = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $keeper_name = $row['keeper_name'] ?? '';
                $labels[] = $keeper_name ? htmlspecialchars($keeper_name, ENT_QUOTES, 'UTF-8') : '';
                $assigned[] = (int)$row['assigned_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'assigned' => $assigned, 'closed' => $closed]]);
            break;
            
        case 'by_user':
            $user_query = "SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                COUNT(s.signal_id) as signal_count
            FROM users u
            INNER JOIN lh_signals s ON s.sent_by = u.id AND " . $whereData['where'] . "
            WHERE u.account_delete = 0
            GROUP BY u.id
            ORDER BY signal_count DESC
            LIMIT 15";
            
            $stmt = mysqli_prepare($dbc, $user_query);
            if (!$stmt) {
                error_log("User query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("User query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $user_name = $row['user_name'] ?? '';
                $labels[] = $user_name ? htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') : '';
                $values[] = (int)$row['signal_count'];
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values]]);
            break;
            
        case 'table':
            // Validate pagination parameters
            $page = sanitizeInteger($_GET['page'] ?? 1, 1, 1, 10000);
            $page_size = sanitizeInteger($_GET['page_size'] ?? 25, 25, 1, 100);
            $offset = ($page - 1) * $page_size;
            
            // Validate sort parameters with whitelist
            $sort_column = $_GET['sort_column'] ?? 'sent_date';
            $allowed_columns = ['signal_number', 'title', 'dock_name', 'sea_state_name', 'priority_name', 
                               'sender_name', 'keeper_name', 'sent_date', 'age', 'service_name', 'signal_type'];
            if (!in_array($sort_column, $allowed_columns)) {
                $sort_column = 'sent_date';
            }
            
            $sort_direction = strtoupper($_GET['sort_direction'] ?? 'DESC');
            if (!in_array($sort_direction, ['ASC', 'DESC'])) {
                $sort_direction = 'DESC';
            }
            
            // Map sort columns to actual database columns
            $sort_map = [
                'dock_name' => 'd.dock_name',
                'sea_state_name' => 'ss.sea_state_name',
                'priority_name' => 'p.priority_name',
                'sender_name' => 'sender.first_name',
                'keeper_name' => 'keeper.first_name',
                'service_name' => 'srv.service_name',
                'signal_type' => 's.signal_type',
                'age' => 's.sent_date'
            ];
            $order_column = $sort_map[$sort_column] ?? "s.$sort_column";
            
            // Reverse direction for age sorting
            if ($sort_column === 'age') {
                $sort_direction = $sort_direction === 'ASC' ? 'DESC' : 'ASC';
            }
            
            // Count total
            $count_query = "SELECT COUNT(*) as total FROM lh_signals s WHERE " . $whereData['where'];
            $stmt = mysqli_prepare($dbc, $count_query);
            if (!$stmt) {
                error_log("Count query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Count query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $count_result = mysqli_stmt_get_result($stmt);
            $total = mysqli_fetch_assoc($count_result)['total'];
            mysqli_stmt_close($stmt);
            
            // Get data - ORDER BY and LIMIT cannot be parameterized, but we've validated them against whitelists
            $table_query = "SELECT 
                s.signal_id,
                s.signal_number,
                s.title,
                s.signal_type,
                s.sent_date,
                d.dock_name,
                d.dock_color,
                d.dock_icon,
                ss.sea_state_name,
                ss.sea_state_color,
                ss.sea_state_icon,
                ss.is_closed_resolution,
                p.priority_name,
                p.priority_color,
                p.priority_icon,
                srv.service_name,
                srv.service_color,
                srv.service_icon,
                CONCAT(sender.first_name, ' ', sender.last_name) as sender_name,
                CONCAT(keeper.first_name, ' ', keeper.last_name) as keeper_name
            FROM lh_signals s
            LEFT JOIN lh_docks d ON s.dock_id = d.dock_id
            LEFT JOIN lh_sea_states ss ON s.sea_state_id = ss.sea_state_id
            LEFT JOIN lh_priorities p ON s.priority_id = p.priority_id
            LEFT JOIN lh_services srv ON s.service_id = srv.service_id
            LEFT JOIN users sender ON s.sent_by = sender.id
            LEFT JOIN users keeper ON s.keeper_assigned = keeper.id
            WHERE " . $whereData['where'] . "
            ORDER BY $order_column $sort_direction
            LIMIT ?, ?";
            
            $table_params = $whereData['params'];
            $table_types = $whereData['types'];
            $table_params[] = $offset;
            $table_params[] = $page_size;
            $table_types .= 'ii';
            
            $stmt = mysqli_prepare($dbc, $table_query);
            if (!$stmt) {
                error_log("Table query prepare failed: " . mysqli_error($dbc));
                throw new Exception("Database error occurred");
            }
            
            if (!empty($table_params)) {
                mysqli_stmt_bind_param($stmt, $table_types, ...$table_params);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                error_log("Table query execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception("Database error occurred");
            }
            
            $result = mysqli_stmt_get_result($stmt);
            
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $sent_date = new DateTime($row['sent_date']);
                $now = new DateTime();
                $interval = $sent_date->diff($now);
                
                if ($interval->days == 0) {
                    $age = $interval->h . 'h';
                } elseif ($interval->days < 30) {
                    $age = $interval->days . 'd';
                } else {
                    $age = floor($interval->days / 30) . 'mo';
                }
                
                // Sanitize output to prevent XSS - handle NULL values
                $row['sent_date_formatted'] = $sent_date->format('M j, Y');
                $row['age'] = $age;
                $row['title'] = $row['title'] ? htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') : '';
                $row['sender_name'] = $row['sender_name'] ? htmlspecialchars($row['sender_name'], ENT_QUOTES, 'UTF-8') : '';
                $row['keeper_name'] = $row['keeper_name'] ? htmlspecialchars($row['keeper_name'], ENT_QUOTES, 'UTF-8') : '';
                
                $data[] = $row;
            }
            
            mysqli_stmt_close($stmt);
            
            echo json_encode(['success' => true, 'data' => $data, 'total' => (int)$total, 'page' => $page, 'page_size' => $page_size]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    }
} catch (Exception $e) {
    // Log the actual error server-side
    error_log("Report data error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return generic error to user
    echo json_encode(['success' => false, 'message' => 'An error occurred while generating the report. Please try again.']);
}