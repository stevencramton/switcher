<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['id'];
$is_keeper = checkRole('lighthouse_keeper');

$get_counts_only = isset($_GET['get_counts_only']) && $_GET['get_counts_only'] == 'true';

if ($get_counts_only) {
	$counts = getCounts($dbc, $user_id, $is_keeper);
	header('Content-Type: application/json');
	echo json_encode([
		'status' => 'success',
		'counts' => $counts
	]);
	exit();
}

$dock = isset($_GET['dock']) ? $_GET['dock'] : 'all';
$state = isset($_GET['state']) ? (int)$_GET['state'] : null;
$priority = isset($_GET['priority']) ? (int)$_GET['priority'] : null;
$owner = isset($_GET['owner']) ? $_GET['owner'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT t.*, 
          ts.sea_state_name, ts.sea_state_color,
          tp.priority_name, tp.priority_color,
          td.dock_name, td.dock_color, td.dock_icon,
          CONCAT(u.first_name, ' ', u.last_name) as creator_name,
          CONCAT(a.first_name, ' ', a.last_name) as assigned_name
          FROM lh_signals t
          LEFT JOIN lh_sea_states ts ON t.sea_state_id = ts.sea_state_id
          LEFT JOIN lh_priorities tp ON t.priority_id = tp.priority_id
          LEFT JOIN lh_docks td ON t.dock_id = td.dock_id
          LEFT JOIN users u ON t.sent_by = u.id
          LEFT JOIN users a ON t.keeper_assigned = a.id
          WHERE t.is_deleted = 0";

$params = [];
$types = '';

// Dock filter
if ($dock !== 'all' && is_numeric($dock)) {
    $query .= " AND t.dock_id = ?";
    $params[] = (int)$dock;
    $types .= 'i';
}

// Sea State filter
if ($state) {
    $query .= " AND t.sea_state_id = ?";
    $params[] = $state;
    $types .= 'i';
}

// Priority filter
if ($priority) {
    $query .= " AND t.priority_id = ?";
    $params[] = $priority;
    $types .= 'i';
}

// Owner filters
if ($owner === 'my_signals') {
    $query .= " AND t.sent_by = ?";
    $params[] = $user_id;
    $types .= 'i';
} elseif ($owner === 'assigned' && $is_keeper) {
    $query .= " AND t.keeper_assigned = ?";
    $params[] = $user_id;
    $types .= 'i';
} elseif ($owner === 'unassigned' && $is_keeper) {
    $query .= " AND (t.keeper_assigned IS NULL OR t.keeper_assigned = 0)";
}

// Search filter
if (!empty($search)) {
    $query .= " AND (t.signal_number LIKE ? OR t.title LIKE ? OR t.message LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Non-admin users can only see their own lh_signals unless specified otherwise
if (!$is_keeper && $owner !== 'all') {
    $query .= " AND t.sent_by = ?";
    $params[] = $user_id;
    $types .= 'i';
}

$query .= " ORDER BY t.sent_date DESC";

// Prepare and execute
$stmt = mysqli_prepare($dbc, $query);

if ($stmt) {
    if (!empty($params)) {
        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $data = [];
        
		while ($row = mysqli_fetch_assoc($result)) {
		    $data[] = [
		        'signal_id' => $row['signal_id'],
		        'signal_number' => $row['signal_number'],
		        'title' => $row['title'],
		        'message' => $row['message'],
		        'signal_type' => $row['signal_type'],
		        'sea_state_name' => $row['sea_state_name'],
		        'sea_state_color' => $row['sea_state_color'],
		        'priority_name' => $row['priority_name'],
		        'priority_color' => $row['priority_color'],
		        'dock_name' => $row['dock_name'],
		        'dock_color' => $row['dock_color'],
		        'dock_icon' => $row['dock_icon'],
		        'creator_name' => $row['creator_name'],
		        'assigned_name' => $row['assigned_name'],
		        'sent_date_formatted' => date('M d, Y g:i A', strtotime($row['sent_date'])),
		        'updated_date_formatted' => date('M d, Y g:i A', strtotime($row['updated_date']))  // Ã¢â€ Â ADDED
		    ];
		}
        
        // Get counts for filters
        $counts = getCounts($dbc, $user_id, $is_keeper);
        
        mysqli_stmt_close($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'counts' => $counts
        ]);
        
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database query failed'
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare statement'
    ]);
}

function getCounts($dbc, $user_id, $is_keeper) {
    $counts = [
        'all' => 0,
        'my_signals' => 0,
        'my_closed_signals' => 0,
        'all_closed_signals' => 0,
        'assigned' => 0,
        'unassigned' => 0,
        'quick_access' => [],
        'docks' => [],
        'dept_status' => [],
        'priorities' => []
    ];
    
    // Get all closed sea states
    $closed_states_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_closed_resolution = 1";
    $closed_states_result = mysqli_query($dbc, $closed_states_query);
    $closed_state_ids = [];
    while ($row = mysqli_fetch_assoc($closed_states_result)) {
        $closed_state_ids[] = $row['sea_state_id'];
    }
    $closed_states_in = !empty($closed_state_ids) ? implode(',', $closed_state_ids) : '0';
    
    // Total count (excluding closed)
    $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0";
    if (!empty($closed_state_ids)) {
        $query .= " AND sea_state_id NOT IN ($closed_states_in)";
    }
    if (!$is_keeper) {
        $query .= " AND sent_by = $user_id";
    }
    $result = mysqli_query($dbc, $query);
    if ($result) {
        $counts['all'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // My signals count (excluding closed)
    $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0 AND sent_by = $user_id";
    if (!empty($closed_state_ids)) {
        $query .= " AND sea_state_id NOT IN ($closed_states_in)";
    }
    $result = mysqli_query($dbc, $query);
    if ($result) {
        $counts['my_signals'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // My closed signals count
    $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0 AND sent_by = $user_id";
    if (!empty($closed_state_ids)) {
        $query .= " AND sea_state_id IN ($closed_states_in)";
    }
    $result = mysqli_query($dbc, $query);
    if ($result) {
        $counts['my_closed_signals'] = mysqli_fetch_assoc($result)['count'];
    }
    
    // Admin counts
    if ($is_keeper) {
        // All closed signals count (only for admins)
        $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0";
        if (!empty($closed_state_ids)) {
            $query .= " AND sea_state_id IN ($closed_states_in)";
        }
        $result = mysqli_query($dbc, $query);
        if ($result) {
            $counts['all_closed_signals'] = mysqli_fetch_assoc($result)['count'];
        }
        
        // Assigned to me count (excluding closed)
        $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0 AND keeper_assigned = $user_id";
        if (!empty($closed_state_ids)) {
            $query .= " AND sea_state_id NOT IN ($closed_states_in)";
        }
        $result = mysqli_query($dbc, $query);
        if ($result) {
            $counts['assigned'] = mysqli_fetch_assoc($result)['count'];
        }
        
        // Unassigned count (only for admins, excluding closed)
        $query = "SELECT COUNT(*) as count FROM lh_signals WHERE is_deleted = 0 AND (keeper_assigned IS NULL OR keeper_assigned = 0)";
        if (!empty($closed_state_ids)) {
            $query .= " AND sea_state_id NOT IN ($closed_states_in)";
        }
        $result = mysqli_query($dbc, $query);
        if ($result) {
            $counts['unassigned'] = mysqli_fetch_assoc($result)['count'];
        }
    }
    
    // Quick Access counts (for sidebar)
    $counts['quick_access'] = [
        'all' => $counts['all'],
        'assigned' => $counts['assigned'],
        'unassigned' => $counts['unassigned'],
        'closed' => $counts['all_closed_signals'],
        'my_closed' => $counts['my_closed_signals']
    ];
    
    // Dock counts (excluding closed)
    $query = "SELECT dock_id, COUNT(*) as count FROM lh_signals WHERE is_deleted = 0";
    if (!empty($closed_state_ids)) {
        $query .= " AND sea_state_id NOT IN ($closed_states_in)";
    }
    if (!$is_keeper) {
        $query .= " AND sent_by = $user_id";
    }
    $query .= " GROUP BY dock_id";
    $result = mysqli_query($dbc, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $counts['docks'][$row['dock_id']] = $row['count'];
        }
    }
    
    // Dock-Beacon combination counts (include ALL states, even closed ones)
    // This ensures accurate counts for each individual state within a dock
    $query = "SELECT dock_id, sea_state_id, COUNT(*) as count FROM lh_signals WHERE is_deleted = 0";
    if (!$is_keeper) {
        $query .= " AND sent_by = $user_id";
    }
    $query .= " GROUP BY dock_id, sea_state_id";
    $result = mysqli_query($dbc, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $key = $row['dock_id'] . '-' . $row['sea_state_id'];
            $counts['dept_status'][$key] = $row['count'];
        }
    }
    
    // Priority counts (excluding closed)
    $query = "SELECT priority_id, COUNT(*) as count FROM lh_signals WHERE is_deleted = 0";
    if (!empty($closed_state_ids)) {
        $query .= " AND sea_state_id NOT IN ($closed_states_in)";
    }
    if (!$is_keeper) {
        $query .= " AND sent_by = $user_id";
    }
    $query .= " GROUP BY priority_id";
    $result = mysqli_query($dbc, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $counts['priorities'][$row['priority_id']] = $row['count'];
        }
    }
    
    return $counts;
}
?>