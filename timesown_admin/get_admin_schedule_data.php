<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
include 'closed_days_helper.php';
include 'admin_schedule_publication_helper.php'; // ADD THIS LINE

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id, role FROM users WHERE switch_id = ? AND account_delete = 0";
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

$publication_info = checkSchedulePublication($dbc, $tenant_id, $start_date, $end_date);

if (!checkRole('admin_developer')) {
    $tenant_check = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_check);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['error' => 'Access denied to this tenant']);
        exit();
    }
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
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $role;
    }
    
    $departments[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'color' => $row['color'],
        'sort_order' => $row['sort_order'],
        'roles' => $roles
    ];
}

$shifts_query = "
    SELECT 
        s.id,
        s.tenant_id,
        s.department_id,
        s.job_role_id,
        s.assigned_user_id,
        s.shift_date,
        s.start_time,
        s.end_time,
        s.status,
        s.shift_color,      
        s.shift_text_color,
        s.public_notes,
        s.private_notes,
        s.attendance_status,
        s.attendance_notes,
        s.created_by,
        s.created_at,
        s.updated_at,
        d.name as department_name,
        d.color as department_color,
        jr.name as role_name,
        jr.color as role_color,
        u.first_name,
        u.last_name,
        u.display_name,
        CASE 
            WHEN u.display_name IS NOT NULL AND u.display_name != '' THEN u.display_name
            WHEN u.first_name IS NOT NULL AND u.last_name IS NOT NULL THEN CONCAT(u.first_name, ' ', u.last_name)
            ELSE NULL
        END as assigned_user_display_name,
        CASE 
            WHEN u.first_name IS NOT NULL AND u.last_name IS NOT NULL THEN CONCAT(u.first_name, ' ', u.last_name)
            ELSE NULL
        END as assigned_user_name
    FROM to_shifts s
    LEFT JOIN to_departments d ON s.department_id = d.id
    LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
    LEFT JOIN users u ON s.assigned_user_id = u.id AND u.account_delete = 0
    WHERE s.tenant_id = ?
    AND s.shift_date BETWEEN ? AND ?
    AND s.status IN ('scheduled', 'open', 'pending')
    ORDER BY s.shift_date, s.start_time, d.sort_order, jr.sort_order
";

$stmt = mysqli_prepare($dbc, $shifts_query);
mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$shifts_result = mysqli_stmt_get_result($stmt);

$shifts = [];
while ($row = mysqli_fetch_assoc($shifts_result)) {
    if ($row['assigned_user_id']) {
        $debug_info = sprintf(
            "SHIFT %d: User ID=%s, User Name='%s', Display Name='%s', First='%s', Last='%s'\n",
            $row['id'],
            $row['assigned_user_id'],
            $row['assigned_user_name'],
            $row['assigned_user_display_name'],
            $row['first_name'],
            $row['last_name']
        );
    }
    
    $shifts[] = [
        'id' => $row['id'],
        'department_id' => $row['department_id'],
        'job_role_id' => $row['job_role_id'],
        'assigned_user_id' => $row['assigned_user_id'],
        'shift_date' => $row['shift_date'],
        'start_time' => substr($row['start_time'], 0, 5),
        'end_time' => substr($row['end_time'], 0, 5),
        'status' => $row['status'],
        'public_notes' => $row['public_notes'],
        'private_notes' => $row['private_notes'],
        'shift_color' => $row['shift_color'],
        'shift_text_color' => $row['shift_text_color'],
        'department_name' => $row['department_name'],
        'department_color' => $row['department_color'],
        'role_name' => $row['role_name'],
        'role_color' => $row['role_color'],
        'assigned_user_name' => $row['assigned_user_name'],
        'assigned_user_display_name' => $row['assigned_user_display_name'],
        'created_by' => $row['created_by'],
        'attendance_status' => $row['attendance_status'],      // ADD THIS LINE
        'attendance_notes' => $row['attendance_notes']         // ADD THIS LINE
    ];
}

$users_query = "
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.display_name
    FROM users u
    WHERE u.account_delete = 0
    AND (
        -- Users assigned to this tenant
        u.id IN (
            SELECT ut.user_id 
            FROM to_user_tenants ut 
            WHERE ut.tenant_id = ? AND ut.active = 1
        )
        OR
        -- Users who have shifts in this tenant during the date range
        u.id IN (
            SELECT DISTINCT s.assigned_user_id 
            FROM to_shifts s 
            INNER JOIN to_departments d ON s.department_id = d.id
            WHERE d.tenant_id = ? 
            AND s.assigned_user_id IS NOT NULL
            AND s.shift_date BETWEEN ? AND ?
        )
    )
    ORDER BY u.display_name, u.first_name, u.last_name
";

$stmt = mysqli_prepare($dbc, $users_query);
mysqli_stmt_bind_param($stmt, 'iiss', $tenant_id, $tenant_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
	$user_roles_query = "
        SELECT udr.department_id, udr.job_role_id, udr.is_primary, udr.active
        FROM to_user_department_roles udr
        INNER JOIN to_departments d ON udr.department_id = d.id
        WHERE udr.user_id = ? AND d.tenant_id = ? AND udr.active = 1
    ";
    $roles_stmt = mysqli_prepare($dbc, $user_roles_query);
    mysqli_stmt_bind_param($roles_stmt, 'ii', $row['id'], $tenant_id);
    mysqli_stmt_execute($roles_stmt);
    $user_roles_result = mysqli_stmt_get_result($roles_stmt);
    
    $department_roles = [];
    while ($role = mysqli_fetch_assoc($user_roles_result)) {
        $department_roles[] = $role;
    }
    
	$users[] = [
        'id' => $row['id'],
        'name' => trim($row['first_name'] . ' ' . $row['last_name']),
        'display_name' => $row['display_name'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'department_roles' => $department_roles
    ];
} 

foreach ($users as &$user) {
    $user_shifts = array_filter($shifts, function($shift) use ($user) {
        return $shift['assigned_user_id'] == $user['id'];
    });
    
    $total_hours = 0;
    foreach ($user_shifts as $shift) {
        $start = strtotime($shift['start_time']);
        $end = strtotime($shift['end_time']);
        $hours = ($end - $start) / 3600;
        $total_hours += $hours;
    }
    
    $user['total_hours'] = round($total_hours, 1);
}

foreach ($shifts as $shift) {
    if ($shift['assigned_user_id']) {
        $found_user = null;
        foreach ($users as $user) {
            if ($user['id'] == $shift['assigned_user_id']) {
                $found_user = $user;
                break;
            }
        }
        
        if (!$found_user) {
            $debug_info = sprintf(
                "MISSING USER: Shift %d assigned to user ID %s but user not found in users array\n",
                $shift['id'],
                $shift['assigned_user_id']
            );
            
        }
    }
}

foreach ($users as &$user) {
    $user_shifts = array_filter($shifts, function($shift) use ($user) {
        return $shift['assigned_user_id'] == $user['id'];
    });
    
    $total_hours = 0;
    foreach ($user_shifts as $shift) {
        $start = strtotime($shift['start_time']);
        $end = strtotime($shift['end_time']);
        $hours = ($end - $start) / 3600;
        $total_hours += $hours;
    }
    
    $user['total_hours'] = round($total_hours, 1);
}

$availability = [];
if (!empty($users)) {
    $user_ids = array_column($users, 'id');
    $user_ids_placeholder = str_repeat('?,', count($user_ids) - 1) . '?';
    
    $availability_query = "
        SELECT ua.*, 
               CONCAT(u.first_name, ' ', u.last_name) as user_name,
               u.display_name as user_display_name
        FROM to_user_availability ua
        JOIN users u ON ua.user_id = u.id
        WHERE ua.tenant_id = ?
        AND ua.user_id IN ($user_ids_placeholder)
        AND ua.effective_date <= ?
        AND (ua.end_date IS NULL OR ua.end_date >= ?)
        ORDER BY ua.user_id, ua.day_of_week, ua.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $availability_query);
    $params = array_merge([$tenant_id], $user_ids, [$end_date, $start_date]);
    $types = 'i' . str_repeat('i', count($user_ids)) . 'ss';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $availability_result = mysqli_stmt_get_result($stmt);

	while ($row = mysqli_fetch_assoc($availability_result)) {
	    $availability[] = [
	        'user_id' => $row['user_id'],
	        'user_name' => $row['user_display_name'] ?: $row['user_name'],
	        'day_of_week' => $row['day_of_week'],
	        'start_time' => substr($row['start_time'], 0, 5),
	        'end_time' => substr($row['end_time'], 0, 5),
	        'notes' => $row['notes'] ?? ''
	    ];
	}
}

$trades = [];
$trades_query = "
    SELECT st.*,
           s.shift_date, s.start_time, s.end_time,
           d.name as department_name,
           jr.name as role_name,
           CONCAT(offering.first_name, ' ', offering.last_name) as offering_user_name,
           offering.display_name as offering_user_display_name,
           CONCAT(requesting.first_name, ' ', requesting.last_name) as requesting_user_name,
           requesting.display_name as requesting_user_display_name
    FROM to_shift_trades st
    JOIN to_shifts s ON st.shift_id = s.id
    JOIN to_departments d ON s.department_id = d.id
    JOIN to_job_roles jr ON s.job_role_id = jr.id
    JOIN users offering ON st.offering_user_id = offering.id
    LEFT JOIN users requesting ON st.requesting_user_id = requesting.id
    WHERE s.tenant_id = ?
    AND s.shift_date BETWEEN ? AND ?
    AND st.status IN ('pending', 'approved')
    ORDER BY st.created_at DESC
";

$trades_stmt = mysqli_prepare($dbc, $trades_query);
mysqli_stmt_bind_param($trades_stmt, 'iss', $tenant_id, $start_date, $end_date);
mysqli_stmt_execute($trades_stmt);
$trades_result = mysqli_stmt_get_result($trades_stmt);

while ($row = mysqli_fetch_assoc($trades_result)) {
    $trades[] = [
        'id' => $row['id'],
        'shift_id' => $row['shift_id'],
        'offering_user_id' => $row['offering_user_id'],
        'requesting_user_id' => $row['requesting_user_id'],
        'offering_user_name' => $row['offering_user_display_name'] ?: $row['offering_user_name'],
        'requesting_user_name' => $row['requesting_user_display_name'] ?: $row['requesting_user_name'],
        'shift_date' => $row['shift_date'],
        'start_time' => substr($row['start_time'], 0, 5),
        'end_time' => substr($row['end_time'], 0, 5),
        'department_name' => $row['department_name'],
        'role_name' => $row['role_name'],
        'notes' => $row['notes'],
        'status' => $row['status']
    ];
}

$total_shifts = count($shifts);
$open_shifts = count(array_filter($shifts, function($s) { return !$s['assigned_user_id']; }));
$filled_shifts = $total_shifts - $open_shifts;

$conflicts = [];
foreach ($users as $user) {
    $user_shifts = array_filter($shifts, function($shift) use ($user) {
        return $shift['assigned_user_id'] == $user['id'];
    });
    
    foreach ($user_shifts as $shift1) {
        foreach ($user_shifts as $shift2) {
            if ($shift1['id'] >= $shift2['id']) continue;
            if ($shift1['shift_date'] !== $shift2['shift_date']) continue;
            
            $start1 = strtotime($shift1['start_time']);
            $end1 = strtotime($shift1['end_time']);
            $start2 = strtotime($shift2['start_time']);
            $end2 = strtotime($shift2['end_time']);
            
            if ($start1 < $end2 && $start2 < $end1) {
                $conflicts[] = [
                    'user_id' => $user['id'],
                    'user_name' => $user['display_name'] ?: $user['name'],
                    'shift1_id' => $shift1['id'],
                    'shift2_id' => $shift2['id'],
                    'date' => $shift1['shift_date']
                ];
            }
        }
    }
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
$current_date_obj = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

while ($current_date_obj <= $end_date_obj) {
    $date_str = $current_date_obj->format('Y-m-d');
    $dates[$date_str] = [
        'date' => $date_str,
        'is_closed' => false,
        'closed_info' => null,
        'is_published' => $publication_info['is_published']
    ];
    
    $current_date_obj->modify('+1 day');
}

$response = [
    'success' => true,
   	'is_published' => $publication_info['is_published'],
    'publication_status' => $publication_info['is_published'] ? 'Published' : 'Not Published',
    'published_ranges' => $published_ranges,
   	'departments' => $departments,
    'users' => $users,
    'shifts' => $shifts,
    'availability' => $availability,
    'trades' => $trades,
    'conflicts' => $conflicts,
    'statistics' => [
        'total_shifts' => $total_shifts,
        'open_shifts' => $open_shifts,
        'filled_shifts' => $filled_shifts,
        'conflicts_count' => count($conflicts),
        'total_users' => count($users),
        'active_trades' => count($trades)
    ],
   	'dates' => $dates,
    'date_range' => [
        'start' => $start_date,
        'end' => $end_date
    ],
    'view' => $view,
    'user_id' => $user_id,
    'tenant_id' => $tenant_id,
    'user_role' => $user_data['role']
];

try {
    addClosedDaysToScheduleData($dbc, $tenant_id, $response, $start_date, $end_date);
} catch (Exception $e) {
  	error_log("Error adding closed days data: " . $e->getMessage());
    $response['closed_days'] = [];
}

if (isset($response['closed_days']) && !empty($response['closed_days'])) {
    foreach ($response['closed_days'] as $date => $closed_info) {
        if (isset($response['dates'][$date])) {
            $response['dates'][$date]['is_closed'] = true;
            $response['dates'][$date]['closed_info'] = $closed_info;
        }
    }
}

try {
   	if (isset($response['closed_days']) && !empty($response['closed_days'])) {
        $response['closed_day_hints'] = [];
        
        foreach ($response['closed_days'] as $date => $closed_info) {
          	$date_shifts = array_filter($response['shifts'], function($shift) use ($date) {
                return $shift['shift_date'] === $date;
            });
            
            $shift_count = count($date_shifts);
            $has_shifts = $shift_count > 0;
            
          	$display_strategy = 'none';
            if ($has_shifts && $closed_info['allow_shifts']) {
                $display_strategy = 'banner';
            } elseif (!$has_shifts) {
                $display_strategy = 'replace';
            }
            
            $response['closed_day_hints'][$date] = [
                'strategy' => $display_strategy,
                'shift_count' => $shift_count,
                'icon' => $closed_info['type'] === 'holiday' ? 'fa-gift' : 'fa-times-circle',
                'color' => $closed_info['type'] === 'holiday' ? 'success' : 'danger',
                'message' => $closed_info['title'],
                'description' => $closed_info['type'] === 'holiday' ? 'a holiday' : 'closed',
                'show_notes' => !empty($closed_info['notes']) && $closed_info['notes'] !== '0'
            ];
        }
    }

 	$current_view_date = $view === 'day' ? $start_date : $start_date;

    if (isset($response['closed_days'][$current_view_date])) {
        $response['current_date_closed'] = true;
        $response['current_date_closed_info'] = $response['closed_days'][$current_view_date];
        $response['current_date_closed_hint'] = $response['closed_day_hints'][$current_view_date] ?? null;
    } else {
        $response['current_date_closed'] = false;
    }

  	$response['current_view_date'] = $current_view_date;

} catch (Exception $e) {
   	error_log("Error in holiday optimization: " . $e->getMessage());
    $response['current_date_closed'] = false;
}

$response['current_view_date'] = $current_view_date ?? $start_date;

if (isset($_GET['debug_timing']) && $_GET['debug_timing'] === '1') {
    $response['debug_timing'] = [
        'server_time' => microtime(true),
        'closed_days_count' => count($response['closed_days'] ?? []),
        'total_shifts' => count($response['shifts'] ?? []),
        'query_date_range' => [
            'start' => $start_date,
            'end' => $end_date
        ],
        'current_view_date' => $current_view_date ?? null,
        'current_date_is_closed' => $response['current_date_closed'] ?? false,
        'publication_status' => $publication_info['is_published'] ? 'Published' : 'Not Published'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>