<?php
/**
 * Export Signal Reports to CSV/PDF
 * Location: ajax/lighthouse_reports/export_report.php
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

// Get filter parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$start_date = isset($_GET['start_date']) && $_GET['start_date'] ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) && $_GET['end_date'] ? $_GET['end_date'] : date('Y-m-d');
$dock_id = isset($_GET['dock_id']) && is_numeric($_GET['dock_id']) ? (int)$_GET['dock_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$priority_id = isset($_GET['priority_id']) && is_numeric($_GET['priority_id']) ? (int)$_GET['priority_id'] : null;
$service_id = isset($_GET['service_id']) && is_numeric($_GET['service_id']) ? (int)$_GET['service_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
$closed_states_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_closed_resolution = 1";
$closed_states_result = mysqli_query($dbc, $closed_states_query);
$closed_state_ids = [];
while ($row = mysqli_fetch_assoc($closed_states_result)) {
    $closed_state_ids[] = $row['sea_state_id'];
}

// Build WHERE clause
$where = ["s.is_deleted = 0"];
$params = [];
$types = "";

$where[] = "DATE(s.sent_date) >= ?";
$params[] = $start_date;
$types .= "s";

$where[] = "DATE(s.sent_date) <= ?";
$params[] = $end_date;
$types .= "s";

if ($dock_id) {
    $where[] = "s.dock_id = ?";
    $params[] = $dock_id;
    $types .= "i";
}

if ($status === 'open' && !empty($closed_state_ids)) {
    $where[] = "s.sea_state_id NOT IN (" . implode(',', $closed_state_ids) . ")";
} elseif ($status === 'closed' && !empty($closed_state_ids)) {
    $where[] = "s.sea_state_id IN (" . implode(',', $closed_state_ids) . ")";
} elseif (is_numeric($status)) {
    $where[] = "s.sea_state_id = ?";
    $params[] = (int)$status;
    $types .= "i";
}

if ($priority_id) {
    $where[] = "s.priority_id = ?";
    $params[] = $priority_id;
    $types .= "i";
}

if ($service_id) {
    $where[] = "s.service_id = ?";
    $params[] = $service_id;
    $types .= "i";
}

if ($search) {
    $searchTerm = "%{$search}%";
    $where[] = "(s.signal_number LIKE ? OR s.title LIKE ? OR s.message LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// User IDs filter (multi-select for signal creators)
if (!empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $where[] = "s.sent_by IN ($placeholders)";
    foreach ($user_ids as $uid) {
        $params[] = $uid;
        $types .= "i";
    }
}

// Keeper IDs filter (multi-select for assigned keepers)
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
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
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
    $total_count = count($signals);
    $open_count = count(array_filter($signals, fn($s) => $s['status_type'] === 'Open'));
    $closed_count = $total_count - $open_count;
    
    $applied_filters = ["Date Range: " . date('M j, Y', strtotime($start_date)) . " - " . date('M j, Y', strtotime($end_date))];
    if ($dock_id) { $r = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT dock_name FROM lh_docks WHERE dock_id = $dock_id")); if ($r) $applied_filters[] = "Dock: " . $r['dock_name']; }
    if ($status === 'open') $applied_filters[] = "Status: Open Only";
    elseif ($status === 'closed') $applied_filters[] = "Status: Closed Only";
    if ($priority_id) { $r = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT priority_name FROM lh_priorities WHERE priority_id = $priority_id")); if ($r) $applied_filters[] = "Priority: " . $r['priority_name']; }
    if ($service_id) { $r = mysqli_fetch_assoc(mysqli_query($dbc, "SELECT service_name FROM lh_services WHERE service_id = $service_id")); if ($r) $applied_filters[] = "Service: " . $r['service_name']; }
    if (!empty($user_ids)) {
        $user_names = [];
        $uids = implode(',', $user_ids);
        $ur = mysqli_query($dbc, "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id IN ($uids)");
        while ($u = mysqli_fetch_assoc($ur)) { $user_names[] = $u['name']; }
        if (!empty($user_names)) $applied_filters[] = "Created By: " . implode(', ', $user_names);
    }
    if (!empty($keeper_ids) || $include_unassigned) {
        $keeper_names = [];
        if ($include_unassigned) $keeper_names[] = 'Unassigned';
        if (!empty($keeper_ids)) {
            $kids = implode(',', $keeper_ids);
            $kr = mysqli_query($dbc, "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id IN ($kids)");
            while ($k = mysqli_fetch_assoc($kr)) { $keeper_names[] = $k['name']; }
        }
        if (!empty($keeper_names)) $applied_filters[] = "Assigned To: " . implode(', ', $keeper_names);
    }
    if ($search) $applied_filters[] = "Search: \"$search\"";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signal Report - <?php echo date('Y-m-d'); ?></title>
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
        <p class="date">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
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
                <td><strong><?php echo htmlspecialchars($s['signal_number']); ?></strong></td>
                <td><?php echo htmlspecialchars(substr($s['title'], 0, 40)) . (strlen($s['title']) > 40 ? '...' : ''); ?></td>
                <td><?php echo htmlspecialchars($s['dock_name'] ?: 'Unassigned'); ?></td>
                <td><span class="badge <?php echo $s['status_type'] === 'Open' ? 'badge-open' : 'badge-closed'; ?>"><?php echo htmlspecialchars($s['sea_state_name']); ?></span></td>
                <td><?php echo htmlspecialchars($s['signal_type_formatted']); ?></td>
                <td><?php echo htmlspecialchars($s['service_name'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($s['priority_name']); ?></td>
                <td><?php echo htmlspecialchars($s['sender_name']); ?></td>
                <td><?php echo htmlspecialchars($s['keeper_name'] ?: 'Unassigned'); ?></td>
                <td><?php echo date('M j, Y', strtotime($s['sent_date'])); ?></td>
                <td><?php echo $s['age']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };</script>
</body>
</html>
<?php
}

// Log the export
$log_details = json_encode(['filters' => ['start_date' => $start_date, 'end_date' => $end_date, 'dock_id' => $dock_id, 'status' => $status, 'priority_id' => $priority_id, 'service_id' => $service_id, 'user_ids' => $user_ids, 'keeper_ids' => $keeper_ids, 'search' => $search], 'format' => $format, 'record_count' => count($signals)]);
$log_query = "INSERT INTO lh_captains_log (event_type, event_category, entity_type, entity_reference, user_id, new_value, details, ip_address, user_agent) VALUES ('export_generated', 'system', NULL, 'Signal Report Export', ?, ?, ?, ?, ?)";
$log_stmt = mysqli_prepare($dbc, $log_query);
$user_id = $_SESSION['id'];
$export_file = $filename . '.' . $format;
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
mysqli_stmt_bind_param($log_stmt, 'issss', $user_id, $export_file, $log_details, $ip, $ua);
mysqli_stmt_execute($log_stmt);