<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
include 'schedule_publication_helper.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('timesown_user')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$switch_id = $_SESSION['switch_id'];

$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];
$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'week';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$filter_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !strtotime($start_date)) {
    $start_date = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) || !strtotime($end_date)) {
    $end_date = $start_date;
}

$tenant_check = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
$stmt = mysqli_prepare($dbc, $tenant_check);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $default_assignment = "INSERT IGNORE INTO to_user_tenants (user_id, tenant_id, is_primary, active) VALUES (?, ?, 1, 1)";
    $stmt = mysqli_prepare($dbc, $default_assignment);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
}

if ($filter_user_id && $filter_user_id !== $user_id) {
    if (!checkRole('timesown_admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit();
    }
}

$publication_info = checkSchedulePublication($dbc, $tenant_id, $start_date, $end_date);

if (!isset($publication_info['published_ranges'])) {
    $publication_info['published_ranges'] = [];
    if (isset($publication_info['publications']) && is_array($publication_info['publications'])) {
        foreach ($publication_info['publications'] as $pub) {
            if ($pub['is_published'] == 1) {
                $publication_info['published_ranges'][] = $pub;
            }
        }
    }
}

$closed_days = [];
try {
    $closed_days_query = "
        SELECT date, type, title, notes, allow_shifts 
        FROM to_closed_days 
        WHERE tenant_id = ? 
        AND date BETWEEN ? AND ?
        ORDER BY date ASC
    ";
    
    $stmt = mysqli_prepare($dbc, $closed_days_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $closed_result = mysqli_stmt_get_result($stmt);
        
        $closed_days_array = [];
        while ($row = mysqli_fetch_assoc($closed_result)) {
            $closed_days_array[$row['date']] = [
                'type' => $row['type'],
                'title' => $row['title'],
                'notes' => $row['notes'],
                'allow_shifts' => (bool)$row['allow_shifts']
            ];
        }
        
        $closed_days = $closed_days_array;
        mysqli_stmt_close($stmt);
    }
} catch (Exception $e) {
    error_log("Error loading closed days for employees: " . $e->getMessage());
    $closed_days = [];
}

$notes = [];
try {
    $notes_query = "
        SELECT id, schedule_date, public_note, admin_note, position, 
               created_at, updated_at, created_by
        FROM to_daily_schedule_notes 
        WHERE tenant_id = ? 
        AND schedule_date BETWEEN ? AND ?
        ORDER BY schedule_date, position
    ";
    
    $notes_stmt = mysqli_prepare($dbc, $notes_query);
    if ($notes_stmt) {
        mysqli_stmt_bind_param($notes_stmt, 'iss', $tenant_id, $start_date, $end_date);
        mysqli_stmt_execute($notes_stmt);
        $notes_result = mysqli_stmt_get_result($notes_stmt);
        
        while ($note_row = mysqli_fetch_assoc($notes_result)) {
            $notes[] = [
                'id' => $note_row['id'],
                'schedule_date' => $note_row['schedule_date'],
                'public_note' => $note_row['public_note'],
                'admin_note' => $note_row['admin_note'],
                'position' => $note_row['position'],
                'created_at' => $note_row['created_at'],
                'updated_at' => $note_row['updated_at'],
                'created_by' => $note_row['created_by']
            ];
        }
        
        mysqli_stmt_close($notes_stmt);
    }
} catch (Exception $e) {
 	$notes = [];
}

if (!$publication_info['is_published']) {
    echo json_encode([
        'success' => true,
        'is_published' => false,
        'publication_status' => 'Schedule is not published for this date range',
        'departments' => [],
        'users' => [],
        'shifts' => [],
        'closed_days' => $closed_days,
        'notes' => $notes,
        'message' => 'No published schedules available for the selected dates',
        'filter_type' => $filter_type,
        'filter_user_id' => $filter_user_id,
        'current_user_id' => $user_id
    ]);
    exit();
}

$departments_query = "
    SELECT d.id, d.name, d.description, d.color, d.sort_order
    FROM to_departments d
    WHERE d.tenant_id = ? AND d.active = 1
    ORDER BY d.sort_order, d.name
";

$stmt = mysqli_prepare($dbc, $departments_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);

$departments = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $roles_query = "
        SELECT id, name, description, color, sort_order
        FROM to_job_roles 
        WHERE department_id = ? AND active = 1
        ORDER BY sort_order, name
    ";
    
    $roles_stmt = mysqli_prepare($dbc, $roles_query);
    mysqli_stmt_bind_param($roles_stmt, 'i', $row['id']);
    mysqli_stmt_execute($roles_stmt);
    $roles_result = mysqli_stmt_get_result($roles_stmt);
    $roles = [];
    
    while ($role_row = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $role_row;
    }
    
    $row['roles'] = $roles;
    $departments[] = $row;
}

$users_query = "
    SELECT u.id, u.first_name, u.last_name, u.display_name, u.profile_pic,
           COALESCE(u.display_name, CONCAT(u.first_name, ' ', u.last_name)) as name
    FROM users u
    INNER JOIN to_user_tenants ut ON u.id = ut.user_id
    WHERE ut.tenant_id = ? 
    AND ut.active = 1 AND u.account_delete = 0
    ORDER BY name
";

$stmt = mysqli_prepare($dbc, $users_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[] = $row;
}

if ($filter_type === 'my_shifts' && $filter_user_id) {
    $shifts_query = "
		SELECT s.id, s.tenant_id, s.department_id, s.job_role_id, s.assigned_user_id,
		       s.shift_date, s.start_time, s.end_time, s.status, s.shift_color, s.shift_text_color,
		       s.public_notes, s.private_notes, s.created_by, s.created_at, s.updated_at,
		       d.name as department_name, d.color as department_color,
		       r.name as role_name, r.color as role_color,
		       u.first_name, u.last_name, u.display_name, u.profile_pic,
		       COALESCE(u.display_name, CONCAT(u.first_name, ' ', u.last_name)) as user_name
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles r ON s.job_role_id = r.id
        LEFT JOIN users u ON s.assigned_user_id = u.id
        WHERE s.tenant_id = ? 
        AND s.shift_date >= ? 
        AND s.shift_date <= ?
        AND s.assigned_user_id = ?
        AND s.status IN ('scheduled', 'open', 'pending')
        AND EXISTS (
            SELECT 1 FROM to_schedule_publication sp 
            WHERE sp.tenant_id = s.tenant_id 
            AND sp.is_published = 1 
            AND s.shift_date >= sp.start_date 
            AND s.shift_date <= sp.end_date
        )
        ORDER BY s.shift_date, s.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'issi', $tenant_id, $start_date, $end_date, $filter_user_id);
} else {
    $shifts_query = "
        SELECT s.id, s.tenant_id, s.department_id, s.job_role_id, s.assigned_user_id,
               s.shift_date, s.start_time, s.end_time, s.status, s.shift_color, s.shift_text_color,
               s.public_notes, s.private_notes, s.created_by, s.created_at, s.updated_at,
               d.name as department_name, d.color as department_color,
               r.name as role_name, r.color as role_color,
               u.first_name, u.last_name, u.display_name,
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), u.display_name) as user_name
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles r ON s.job_role_id = r.id
        LEFT JOIN users u ON s.assigned_user_id = u.id
        WHERE s.tenant_id = ? 
        AND s.shift_date >= ? 
        AND s.shift_date <= ?
        AND s.status IN ('scheduled', 'open', 'pending')
        AND EXISTS (
            SELECT 1 FROM to_schedule_publication sp 
            WHERE sp.tenant_id = s.tenant_id 
            AND sp.is_published = 1 
            AND s.shift_date >= sp.start_date 
            AND s.shift_date <= sp.end_date
        )
        ORDER BY s.shift_date, s.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
}

mysqli_stmt_execute($stmt);
$shifts_result = mysqli_stmt_get_result($stmt);

$shifts = [];
while ($row = mysqli_fetch_assoc($shifts_result)) {
    $row['start_time_formatted'] = date('g:i A', strtotime($row['start_time']));
    $row['end_time_formatted'] = date('g:i A', strtotime($row['end_time']));
    $row['date_formatted'] = date('M j, Y', strtotime($row['shift_date']));
    
    $start_timestamp = strtotime($row['shift_date'] . ' ' . $row['start_time']);
    $end_timestamp = strtotime($row['shift_date'] . ' ' . $row['end_time']);
    
    if ($end_timestamp < $start_timestamp) {
        $end_timestamp += 86400;
    }
    
    $duration_hours = ($end_timestamp - $start_timestamp) / 3600;
    $row['duration_hours'] = round($duration_hours, 2);
    $row['assigned_user_name'] = $row['user_name'];
    $row['assigned_user_display_name'] = $row['display_name'];
    $row['assigned_user_first_name'] = $row['first_name'];
    $row['assigned_user_last_name'] = $row['last_name'];
    $shifts[] = $row;
}

$published_ranges = [];
if (isset($publication_info['published_ranges']) && is_array($publication_info['published_ranges'])) {
    foreach ($publication_info['published_ranges'] as $pub) {
        $published_ranges[] = [
            'start_date' => $pub['start_date'],
            'end_date' => $pub['end_date'],
            'published_by' => ($pub['published_by_first'] ?? '') . ' ' . ($pub['published_by_last'] ?? ''),
            'published_at' => $pub['published_at'] ?? null
        ];
    }
}

$dates = [];
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

while ($current_date <= $end_date_obj) {
    $date_str = $current_date->format('Y-m-d');
    $dates[$date_str] = [
        'date' => $date_str,
        'is_closed' => false,
        'closed_info' => null
    ];
    
   	if (isset($closed_days[$date_str])) {
        $dates[$date_str]['is_closed'] = true;
        $dates[$date_str]['closed_info'] = $closed_days[$date_str];
    }
    
    $current_date->modify('+1 day');
}

$response = [
    'success' => true,
    'is_published' => true,
    'publication_status' => 'Published',
    'published_ranges' => $published_ranges,
    'departments' => $departments,
    'users' => $users,
    'shifts' => $shifts,
    'closed_days' => $closed_days,
    'dates' => $dates,
    'notes' => $notes,
    'total_shifts' => count($shifts),
    'date_range' => [
        'start' => $start_date,
        'end' => $end_date
    ],
    'filter_type' => $filter_type,
    'filter_user_id' => $filter_user_id,
    'current_user_id' => $user_id,
    'debug_info' => [
        'filter_type' => $filter_type,
        'filter_user_id' => $filter_user_id,
        'current_user_id' => $user_id,
        'query_params' => $_GET,
        'closed_days_count' => count($closed_days),
        'notes_count' => count($notes)
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>