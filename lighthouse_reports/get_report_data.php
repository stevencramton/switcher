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

// Get parameters
$type = $_GET['type'] ?? 'stats';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$dock_id = isset($_GET['dock_id']) && $_GET['dock_id'] !== '' ? (int)$_GET['dock_id'] : null;
$status = $_GET['status'] ?? '';
$priority_id = isset($_GET['priority_id']) && $_GET['priority_id'] !== '' ? (int)$_GET['priority_id'] : null;
$service_id = isset($_GET['service_id']) && $_GET['service_id'] !== '' ? (int)$_GET['service_id'] : null;
$search = $_GET['search'] ?? '';

// Multi-select filters
$user_ids = [];
if (isset($_GET['user_ids']) && trim($_GET['user_ids']) !== '') {
    $raw_user_ids = explode(',', $_GET['user_ids']);
    foreach ($raw_user_ids as $uid) {
        $uid = trim($uid);
        if ($uid !== '' && is_numeric($uid) && (int)$uid > 0) {
            $user_ids[] = (int)$uid;
        }
    }
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

// Get closed state IDs
$closed_states = [];
$closed_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_closed_resolution = 1";
$closed_result = mysqli_query($dbc, $closed_query);
while ($row = mysqli_fetch_assoc($closed_result)) {
    $closed_states[] = $row['sea_state_id'];
}
$closed_states_str = implode(',', $closed_states);

// Build WHERE clause
function buildWhereClause($start_date, $end_date, $dock_id, $status, $priority_id, $service_id, $search, $closed_states, $user_ids = [], $keeper_ids = [], $include_unassigned = false, $prefix = 's') {
    $where = ["{$prefix}.is_deleted = 0"];
    $params = [];
    $types = '';
    
    // Date range
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
    
    // Dock filter
    if ($dock_id) {
        $where[] = "{$prefix}.dock_id = ?";
        $params[] = $dock_id;
        $types .= 'i';
    }
    
    // Status filter
    if ($status === 'open' && !empty($closed_states)) {
        $where[] = "{$prefix}.sea_state_id NOT IN (" . implode(',', $closed_states) . ")";
    } elseif ($status === 'closed' && !empty($closed_states)) {
        $where[] = "{$prefix}.sea_state_id IN (" . implode(',', $closed_states) . ")";
    } elseif (is_numeric($status)) {
        $where[] = "{$prefix}.sea_state_id = ?";
        $params[] = (int)$status;
        $types .= 'i';
    }
    
    // Priority filter
    if ($priority_id) {
        $where[] = "{$prefix}.priority_id = ?";
        $params[] = $priority_id;
        $types .= 'i';
    }
    
    // Service filter
    if ($service_id) {
        $where[] = "{$prefix}.service_id = ?";
        $params[] = $service_id;
        $types .= 'i';
    }
    
    // User IDs filter (multi-select for signal creators)
    if (!empty($user_ids)) {
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $where[] = "{$prefix}.sent_by IN ($placeholders)";
        foreach ($user_ids as $uid) {
            $params[] = $uid;
            $types .= 'i';
        }
    }
    
    // Keeper IDs filter (multi-select for assigned keepers)
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
    
    // Search filter
    if ($search) {
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

$whereData = buildWhereClause($start_date, $end_date, $dock_id, $status, $priority_id, $service_id, $search, $closed_states, $user_ids, $keeper_ids, $include_unassigned);

// Debug mode - add ?debug=1 to see filter info
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

try {
    // If debug mode, return filter information
    if ($debug_mode && $type === 'debug') {
        echo json_encode([
            'success' => true,
            'debug' => [
                'user_ids_raw' => $_GET['user_ids'] ?? 'not set',
                'user_ids_parsed' => $user_ids,
                'keeper_ids_raw' => $_GET['keeper_ids'] ?? 'not set',
                'keeper_ids_parsed' => $keeper_ids,
                'include_unassigned' => $include_unassigned,
                'where_clause' => $whereData['where'],
                'param_types' => $whereData['types'],
                'param_count' => count($whereData['params'])
            ]
        ]);
        exit;
    }
    
    switch ($type) {
        case 'stats':
            // Get high priority IDs (top 2 by order)
            $high_priority_ids = [];
            $hp_query = "SELECT priority_id FROM lh_priorities WHERE is_active = 1 ORDER BY priority_order DESC LIMIT 2";
            $hp_result = mysqli_query($dbc, $hp_query);
            while ($row = mysqli_fetch_assoc($hp_result)) {
                $high_priority_ids[] = $row['priority_id'];
            }
            
            $stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN s.sea_state_id NOT IN (" . ($closed_states_str ?: '0') . ") AND s.priority_id IN (" . (implode(',', $high_priority_ids) ?: '0') . ") THEN 1 ELSE 0 END) as high_priority,
                AVG(CASE WHEN s.resolved_date IS NOT NULL THEN TIMESTAMPDIFF(HOUR, s.sent_date, s.resolved_date) ELSE NULL END) as avg_resolution_hours
            FROM lh_signals s
            WHERE " . $whereData['where'];
            
            $stmt = mysqli_prepare($dbc, $stats_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $stats = mysqli_fetch_assoc($result);
            
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
            
            $timeline_query = "SELECT 
                DATE_FORMAT(s.sent_date, '$date_format') as period,
                MIN(DATE(s.sent_date)) as period_date,
                COUNT(*) as created,
                SUM(CASE WHEN s.sea_state_id IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as closed
            FROM lh_signals s
            WHERE " . $whereData['where'] . "
            GROUP BY period
            ORDER BY period_date ASC";
            
            $stmt = mysqli_prepare($dbc, $timeline_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
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
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
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
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_dock_detail':
            $dock_query = "SELECT 
                d.dock_name as label,
                d.dock_color as color,
                COUNT(s.signal_id) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as closed_count
            FROM lh_docks d
            LEFT JOIN lh_signals s ON s.dock_id = d.dock_id AND " . $whereData['where'] . "
            WHERE d.is_active = 1
            GROUP BY d.dock_id
            HAVING total > 0
            ORDER BY total DESC";
            
            $stmt = mysqli_prepare($dbc, $dock_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = []; $open = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['total'];
                $colors[] = $row['color'];
                $open[] = (int)$row['open_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
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
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['value'];
                $colors[] = $row['color'];
            }
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_service':
            $service_query = "SELECT 
                COALESCE(srv.service_name, 'Unassigned') as label,
                COALESCE(srv.service_color, '#9ca3af') as color,
                COUNT(s.signal_id) as total,
                SUM(CASE WHEN s.sea_state_id NOT IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN s.sea_state_id IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as closed_count
            FROM lh_signals s
            LEFT JOIN lh_services srv ON s.service_id = srv.service_id
            WHERE " . $whereData['where'] . "
            GROUP BY s.service_id
            ORDER BY total DESC";
            
            $stmt = mysqli_prepare($dbc, $service_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = []; $open = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['label'];
                $values[] = (int)$row['total'];
                $colors[] = $row['color'];
                $open[] = (int)$row['open_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors, 'open' => $open, 'closed' => $closed]]);
            break;
            
        case 'by_signal_type':
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
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = []; $colors = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $typeVal = $row['type_value'] ?: 'other';
                $labels[] = ucwords(str_replace('_', ' ', $typeVal));
                $values[] = (int)$row['count'];
                $colors[] = $type_colors[$typeVal] ?? '#6b7280';
            }
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values, 'colors' => $colors]]);
            break;
            
        case 'by_keeper':
            $keeper_query = "SELECT 
                CONCAT(u.first_name, ' ', u.last_name) as keeper_name,
                COUNT(s.signal_id) as assigned_count,
                SUM(CASE WHEN s.sea_state_id IN (" . ($closed_states_str ?: '0') . ") THEN 1 ELSE 0 END) as closed_count
            FROM users u
            INNER JOIN lh_signals s ON s.keeper_assigned = u.id AND " . $whereData['where'] . "
            WHERE u.account_delete = 0
            GROUP BY u.id
            ORDER BY assigned_count DESC
            LIMIT 10";
            
            $stmt = mysqli_prepare($dbc, $keeper_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $assigned = []; $closed = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['keeper_name'];
                $assigned[] = (int)$row['assigned_count'];
                $closed[] = (int)$row['closed_count'];
            }
            
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
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $labels = []; $values = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $labels[] = $row['user_name'];
                $values[] = (int)$row['signal_count'];
            }
            
            echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'values' => $values]]);
            break;
            
        case 'table':
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $page_size = isset($_GET['page_size']) ? min(100, max(1, (int)$_GET['page_size'])) : 25;
            $offset = ($page - 1) * $page_size;
            
            $sort_column = $_GET['sort_column'] ?? 'sent_date';
            $sort_direction = strtoupper($_GET['sort_direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            
            // Validate sort column
            $allowed_columns = ['signal_number', 'title', 'dock_name', 'sea_state_name', 'priority_name', 'sender_name', 'keeper_name', 'sent_date', 'age', 'service_name', 'signal_type'];
            if (!in_array($sort_column, $allowed_columns)) {
                $sort_column = 'sent_date';
            }
            
            // Map sort columns
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
            if ($sort_column === 'age') {
                $sort_direction = $sort_direction === 'ASC' ? 'DESC' : 'ASC';
            }
            
            // Count total
            $count_query = "SELECT COUNT(*) as total FROM lh_signals s WHERE " . $whereData['where'];
            $stmt = mysqli_prepare($dbc, $count_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
            $count_result = mysqli_stmt_get_result($stmt);
            $total = mysqli_fetch_assoc($count_result)['total'];
            
            // Get data
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
            LIMIT $offset, $page_size";
            
            $stmt = mysqli_prepare($dbc, $table_query);
            if (!empty($whereData['params'])) {
                mysqli_stmt_bind_param($stmt, $whereData['types'], ...$whereData['params']);
            }
            mysqli_stmt_execute($stmt);
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
                
                $row['sent_date_formatted'] = $sent_date->format('M j, Y');
                $row['age'] = $age;
                $data[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $data, 'total' => (int)$total, 'page' => $page, 'page_size' => $page_size]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'debug' => [
        'user_ids' => $user_ids,
        'keeper_ids' => $keeper_ids,
        'where' => $whereData['where'] ?? 'not built'
    ]]);
}