<?php
/**
 * Export Signal Reports to CSV/PDF
 * Location: ajax/lighthouse_reports/export_report.php
 * SECURED VERSION - Fixed XSS and SQL Injection vulnerabilities
 */
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!checkRole('lighthouse_maritime')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Input validation functions
function validateDate($date) {
    if (empty($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInteger($value, $default = null, $min = null) {
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

// Get and validate filter parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$allowed_formats = ['csv', 'pdf'];
if (!in_array($format, $allowed_formats)) {
    $format = 'csv';
}

// Validate dates
$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end = date('Y-m-d');

$start_date = isset($_GET['start_date']) && $_GET['start_date'] ? $_GET['start_date'] : $default_start;
if (!validateDate($start_date)) {
    $start_date = $default_start;
}

$end_date = isset($_GET['end_date']) && $_GET['end_date'] ? $_GET['end_date'] : $default_end;
if (!validateDate($end_date)) {
    $end_date = $default_end;
}

// Validate integer parameters
$dock_id = sanitizeInteger($_GET['dock_id'] ?? null, null, 1);
$priority_id = sanitizeInteger($_GET['priority_id'] ?? null, null, 1);
$service_id = sanitizeInteger($_GET['service_id'] ?? null, null, 1);

// Validate status parameter
$status = isset($_GET['status']) ? $_GET['status'] : null;
$allowed_status_values = ['open', 'closed', null];
if (!in_array($status, $allowed_status_values) && !is_numeric($status)) {
    $status = null;
}
if (is_numeric($status)) {
    $status = sanitizeInteger($status, null, 1);
}

// Sanitize search input - limit length
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $search = substr($search, 0, 200); // Limit to 200 characters
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

// Get closed state IDs using prepared statement
$closed_state_ids = [];
$closed_states_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_closed_resolution = 1";
$closed_stmt = mysqli_prepare($dbc, $closed_states_query);
if ($closed_stmt) {
    mysqli_stmt_execute($closed_stmt);
    $closed_result = mysqli_stmt_get_result($closed_stmt);
    while ($row = mysqli_fetch_assoc($closed_result)) {
        $closed_state_ids[] = (int)$row['sea_state_id'];
    }
    mysqli_stmt_close($closed_stmt);
}

// Build WHERE clause with proper parameterization
$where = ["s.is_deleted = 0"];
$params = [];
$types = "";

// Date range - already validated
$where[] = "DATE(s.sent_date) >= ?";
$params[] = $start_date;
$types .= "s";

$where[] = "DATE(s.sent_date) <= ?";
$params[] = $end_date;
$types .= "s";

// Dock filter - already validated
if ($dock_id !== null) {
    $where[] = "s.dock_id = ?";
    $params[] = $dock_id;
    $types .= "i";
}

// Status filter - parameterize closed_state_ids
if ($status === 'open' && !empty($closed_state_ids)) {
    $placeholders = implode(',', array_fill(0, count($closed_state_ids), '?'));
    $where[] = "s.sea_state_id NOT IN ($placeholders)";
    foreach ($closed_state_ids as $state_id) {
        $params[] = $state_id;
        $types .= "i";
    }
} elseif ($status === 'closed' && !empty($closed_state_ids)) {
    $placeholders = implode(',', array_fill(0, count($closed_state_ids), '?'));
    $where[] = "s.sea_state_id IN ($placeholders)";
    foreach ($closed_state_ids as $state_id) {
        $params[] = $state_id;
        $types .= "i";
    }
} elseif (is_numeric($status)) {
    $where[] = "s.sea_state_id = ?";
    $params[] = (int)$status;
    $types .= "i";
}

// Priority filter - already validated
if ($priority_id !== null) {
    $where[] = "s.priority_id = ?";
    $params[] = $priority_id;
    $types .= "i";
}

// Service filter - already validated
if ($service_id !== null) {
    $where[] = "s.service_id = ?";
    $params[] = $service_id;
    $types .= "i";
}

// Search filter - already length-limited
if ($search !== '') {
    $searchTerm = "%{$search}%";
    $where[] = "(s.signal_number LIKE ? OR s.title LIKE ? OR s.message LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// User IDs filter - already validated
if (!empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $where[] = "s.sent_by IN ($placeholders)";
    foreach ($user_ids as $uid) {
        $params[] = $uid;
        $types .= "i";
    }
}

// Keeper IDs filter - already validated
if (!empty($keeper_ids) || $include_unassigned) {
    $keeper_conditions = [];
    if (!empty($keeper_ids)) {
        $placeholders = implode(',', array_fill(0, count($keeper_ids), '?'));
        $keeper_conditions[] = "s.keeper_assigned IN ($placeholders)";
        foreach ($keeper_ids as $kid) {
            $params[] = $kid;
            $types .= "i";
        }
    }
    if ($include_unassigned) {
        $keeper_conditions[] = "s.keeper_assigned IS NULL";
    }
    if (!empty($keeper_conditions)) {
        $where[] = "(" . implode(' OR ', $keeper_conditions) . ")";
    }
}

$where_clause = implode(" AND ", $where);

// Execute main query with prepared statement
$signals_query = "SELECT 
                    s.signal_id, s.signal_number, s.title, s.signal_type,
                    s.sent_date, s.updated_date, s.resolved_date,
                    d.dock_name, ss.sea_state_name, ss.is_closed_resolution,
                    p.priority_name,
                    CONCAT(sender.first_name, ' ', sender.last_name) as sender_name,
                    COALESCE(sender.microsoft_email, sender.personal_email, sender.user) as sender_email,
                    CONCAT(keeper.first_name, ' ', keeper.last_name) as keeper_name,
                    COALESCE(keeper.microsoft_email, keeper.personal_email, keeper.user) as keeper_email,
                    srv.service_name
                 FROM lh_signals s
                 LEFT JOIN lh_docks d ON s.dock_id = d.dock_id
                 LEFT JOIN lh_sea_states ss ON s.sea_state_id = ss.sea_state_id
                 LEFT JOIN lh_priorities p ON s.priority_id = p.priority_id
                 LEFT JOIN users sender ON s.sent_by = sender.id
                 LEFT JOIN users keeper ON s.keeper_assigned = keeper.id
                 LEFT JOIN lh_services srv ON s.service_id = srv.service_id
                 WHERE {$where_clause}
                 ORDER BY s.sent_date DESC";

$stmt = mysqli_prepare($dbc, $signals_query);
if (!$stmt) {
    error_log("Export query prepare failed: " . mysqli_error($dbc));
    die("An error occurred while preparing the report. Please try again.");
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

if (!mysqli_stmt_execute($stmt)) {
    error_log("Export query execute failed: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    die("An error occurred while generating the report. Please try again.");
}

$result = mysqli_stmt_get_result($stmt);

$signals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sent_date = new DateTime($row['sent_date']);
    $now = new DateTime();
    $interval = $sent_date->diff($now);
    
    $age = $interval->days == 0 ? $interval->h . ' hours' : ($interval->days < 30 ? $interval->days . ' days' : floor($interval->days / 30) . ' months');
    
    $resolution_time = '';
    if ($row['is_closed_resolution'] && $row['resolved_date']) {
        $resolved = new DateTime($row['resolved_date']);
        $res_interval = $sent_date->diff($resolved);
        $resolution_time = $res_interval->days == 0 ? $res_interval->h . ' hours' : $res_interval->days . ' days';
    }
    
    $row['age'] = $age;
    $row['resolution_time'] = $resolution_time;
    $row['status_type'] = $row['is_closed_resolution'] ? 'Closed' : 'Open';
    $row['signal_type_formatted'] = $row['signal_type'] ? ucwords(str_replace('_', ' ', $row['signal_type'])) : 'Other';
    $signals[] = $row;
}
mysqli_stmt_close($stmt);

$filename = 'signal_report_' . date('Y-m-d_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Signal Number', 'Title', 'Dock', 'Status', 'Status Type', 'Priority', 'Service', 'Signal Type', 'Created By', 'Creator Email', 'Assigned To', 'Assignee Email', 'Created Date', 'Updated Date', 'Resolved Date', 'Age', 'Resolution Time']);
    
    foreach ($signals as $s) {
        fputcsv($output, [
            $s['signal_number'], $s['title'], $s['dock_name'] ?: 'Unassigned', $s['sea_state_name'], $s['status_type'], $s['priority_name'],
            $s['service_name'] ?: 'None', $s['signal_type_formatted'], $s['sender_name'], $s['sender_email'],
            $s['keeper_name'] ?: 'Unassigned', $s['keeper_email'] ?: '',
            date('Y-m-d H:i', strtotime($s['sent_date'])), date('Y-m-d H:i', strtotime($s['updated_date'])),
            $s['resolved_date'] ? date('Y-m-d H:i', strtotime($s['resolved_date'])) : '', $s['age'], $s['resolution_time']
        ]);
    }
    fclose($output);
    
} else {
    // PDF format - Build applied filters with proper encoding
    $total_count = count($signals);
    $open_count = count(array_filter($signals, fn($s) => $s['status_type'] === 'Open'));
    $closed_count = $total_count - $open_count;
    
    // Build applied filters array with HTML encoding for XSS prevention
    $applied_filters = ["Date Range: " . htmlspecialchars(date('M j, Y', strtotime($start_date)) . " - " . date('M j, Y', strtotime($end_date)), ENT_QUOTES, 'UTF-8')];
    
    // Dock filter - use prepared statement
    if ($dock_id !== null) {
        $dock_query = "SELECT dock_name FROM lh_docks WHERE dock_id = ?";
        $dock_stmt = mysqli_prepare($dbc, $dock_query);
        if ($dock_stmt) {
            mysqli_stmt_bind_param($dock_stmt, 'i', $dock_id);
            mysqli_stmt_execute($dock_stmt);
            $dock_result = mysqli_stmt_get_result($dock_stmt);
            if ($r = mysqli_fetch_assoc($dock_result)) {
                $applied_filters[] = "Dock: " . htmlspecialchars($r['dock_name'], ENT_QUOTES, 'UTF-8');
            }
            mysqli_stmt_close($dock_stmt);
        }
    }
    
    // Status filter
    if ($status === 'open') {
        $applied_filters[] = "Status: Open Only";
    } elseif ($status === 'closed') {
        $applied_filters[] = "Status: Closed Only";
    }
    
    // Priority filter - use prepared statement
    if ($priority_id !== null) {
        $priority_query = "SELECT priority_name FROM lh_priorities WHERE priority_id = ?";
        $priority_stmt = mysqli_prepare($dbc, $priority_query);
        if ($priority_stmt) {
            mysqli_stmt_bind_param($priority_stmt, 'i', $priority_id);
            mysqli_stmt_execute($priority_stmt);
            $priority_result = mysqli_stmt_get_result($priority_stmt);
            if ($r = mysqli_fetch_assoc($priority_result)) {
                $applied_filters[] = "Priority: " . htmlspecialchars($r['priority_name'], ENT_QUOTES, 'UTF-8');
            }
            mysqli_stmt_close($priority_stmt);
        }
    }
    
    // Service filter - use prepared statement
    if ($service_id !== null) {
        $service_query = "SELECT service_name FROM lh_services WHERE service_id = ?";
        $service_stmt = mysqli_prepare($dbc, $service_query);
        if ($service_stmt) {
            mysqli_stmt_bind_param($service_stmt, 'i', $service_id);
            mysqli_stmt_execute($service_stmt);
            $service_result = mysqli_stmt_get_result($service_stmt);
            if ($r = mysqli_fetch_assoc($service_result)) {
                $applied_filters[] = "Service: " . htmlspecialchars($r['service_name'], ENT_QUOTES, 'UTF-8');
            }
            mysqli_stmt_close($service_stmt);
        }
    }
    
    // User IDs filter - use prepared statement
    if (!empty($user_ids)) {
        $user_names = [];
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
        $user_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id IN ($placeholders)";
        $user_stmt = mysqli_prepare($dbc, $user_query);
        if ($user_stmt) {
            $user_types = str_repeat('i', count($user_ids));
            mysqli_stmt_bind_param($user_stmt, $user_types, ...$user_ids);
            mysqli_stmt_execute($user_stmt);
            $user_result = mysqli_stmt_get_result($user_stmt);
            while ($u = mysqli_fetch_assoc($user_result)) {
                $user_names[] = htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8');
            }
            mysqli_stmt_close($user_stmt);
        }
        if (!empty($user_names)) {
            $applied_filters[] = "Created By: " . implode(', ', $user_names);
        }
    }
    
    // Keeper IDs filter - use prepared statement
    if (!empty($keeper_ids) || $include_unassigned) {
        $keeper_names = [];
        if ($include_unassigned) {
            $keeper_names[] = 'Unassigned';
        }
        if (!empty($keeper_ids)) {
            $placeholders = implode(',', array_fill(0, count($keeper_ids), '?'));
            $keeper_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id IN ($placeholders)";
            $keeper_stmt = mysqli_prepare($dbc, $keeper_query);
            if ($keeper_stmt) {
                $keeper_types = str_repeat('i', count($keeper_ids));
                mysqli_stmt_bind_param($keeper_stmt, $keeper_types, ...$keeper_ids);
                mysqli_stmt_execute($keeper_stmt);
                $keeper_result = mysqli_stmt_get_result($keeper_stmt);
                while ($k = mysqli_fetch_assoc($keeper_result)) {
                    $keeper_names[] = htmlspecialchars($k['name'], ENT_QUOTES, 'UTF-8');
                }
                mysqli_stmt_close($keeper_stmt);
            }
        }
        if (!empty($keeper_names)) {
            $applied_filters[] = "Assigned To: " . implode(', ', $keeper_names);
        }
    }
    
    // Search filter - CRITICAL: Encode for XSS prevention
    if ($search !== '') {
        $applied_filters[] = "Search: \"" . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . "\"";
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signal Report - <?php echo htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1e3a5f; padding-bottom: 20px; }
        .header h1 { font-size: 24px; color: #1e3a5f; margin-bottom: 5px; }
        .header .date { color: #666; font-size: 14px; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 30px; }
        .summary-card { text-align: center; padding: 15px 30px; background: #f8f9fa; border-radius: 8px; }
        .summary-card .value { font-size: 28px; font-weight: bold; color: #1e3a5f; }
        .summary-card .label { font-size: 12px; color: #666; margin-top: 5px; }
        .filters { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
        .filters h3 { font-size: 14px; margin-bottom: 10px; color: #1e3a5f; }
        .filters p { font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #1e3a5f; color: white; padding: 10px 8px; text-align: left; font-size: 11px; font-weight: 600; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }
        .badge-open { background: #dcfce7; color: #166534; }
        .badge-closed { background: #f3f4f6; color: #4b5563; }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        @media print { .print-btn { display: none; } body { padding: 0; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    <div class="header">
        <h1>Signal Report</h1>
        <p class="date">Generated on <?php echo htmlspecialchars(date('F j, Y \a\t g:i A'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="summary">
        <div class="summary-card"><div class="value"><?php echo number_format($total_count); ?></div><div class="label">Total Signals</div></div>
        <div class="summary-card"><div class="value"><?php echo number_format($open_count); ?></div><div class="label">Open</div></div>
        <div class="summary-card"><div class="value"><?php echo number_format($closed_count); ?></div><div class="label">Closed</div></div>
    </div>
    <div class="filters"><h3>Applied Filters</h3><p><?php echo implode(' | ', $applied_filters); ?></p></div>
    <table>
        <thead><tr><th>Signal #</th><th>Title</th><th>Dock</th><th>Status</th><th>Type</th><th>Service</th><th>Priority</th><th>Created By</th><th>Assigned To</th><th>Created</th><th>Age</th></tr></thead>
        <tbody>
            <?php foreach ($signals as $s): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($s['signal_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><?php echo htmlspecialchars(substr($s['title'], 0, 40), ENT_QUOTES, 'UTF-8') . (strlen($s['title']) > 40 ? '...' : ''); ?></td>
                <td><?php echo htmlspecialchars($s['dock_name'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="badge <?php echo $s['status_type'] === 'Open' ? 'badge-open' : 'badge-closed'; ?>"><?php echo htmlspecialchars($s['sea_state_name'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php echo htmlspecialchars($s['signal_type_formatted'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['service_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['priority_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['sender_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['keeper_name'] ?: 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(date('M j, Y', strtotime($s['sent_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($s['age'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };</script>
</body>
</html>
<?php
}

// Log the export with prepared statement
$log_details = json_encode([
    'filters' => [
        'start_date' => $start_date,
        'end_date' => $end_date,
        'dock_id' => $dock_id,
        'status' => $status,
        'priority_id' => $priority_id,
        'service_id' => $service_id,
        'user_ids' => $user_ids,
        'keeper_ids' => $keeper_ids,
        'search' => $search
    ],
    'format' => $format,
    'record_count' => count($signals)
]);

$log_query = "INSERT INTO lh_captains_log (event_type, event_category, entity_type, entity_reference, user_id, new_value, details, ip_address, user_agent) VALUES ('export_generated', 'system', NULL, 'Signal Report Export', ?, ?, ?, ?, ?)";
$log_stmt = mysqli_prepare($dbc, $log_query);

if ($log_stmt) {
    $user_id = $_SESSION['id'];
    $export_file = $filename . '.' . $format;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    mysqli_stmt_bind_param($log_stmt, 'issss', $user_id, $export_file, $log_details, $ip, $ua);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}