<?php
ob_start();
session_start();

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json');

// Secure error handler that logs details but doesn't expose them to clients
set_error_handler(function($severity, $message, $file, $line) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Log the detailed error server-side for debugging
    error_log("PHP Error [$severity]: $message in $file on line $line");
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal error occurred. Please try again or contact support.'
    ]);
    exit;
});

if (!isset($_SESSION['switch_id'])) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    include '../../mysqli_connect.php';
    include '../../templates/functions.php';
} catch (Exception $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Log the detailed error server-side
    error_log('Database connection failed: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Unable to connect to the database. Please try again or contact support.'
    ]);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['user_id'])) {
    $input = $_POST;
}

if (!$input && isset($_GET['user_id'])) {
    $input = $_GET;
}

if (!isset($input['user_id']) || !is_numeric($input['user_id']) || 
    !isset($input['tenant_id']) || !is_numeric($input['tenant_id'])) {
    
	http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid user_id and tenant_id are required'
    ]);
    exit;
}

$user_id = intval($input['user_id']);
$tenant_id = intval($input['tenant_id']);
$switch_id = $_SESSION['switch_id'];
$current_user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $current_user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$current_user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($current_user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Current user not found']);
    exit();
}

$current_user_data = mysqli_fetch_assoc($current_user_result);
$actual_user_id = $current_user_data['id'];

if (!checkRole('timesown_admin')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $access_stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($access_stmt, 'ii', $actual_user_id, $tenant_id);
    mysqli_stmt_execute($access_stmt);
    $access_result = mysqli_stmt_get_result($access_stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

function timeToMinutes($time) {
    $timestamp = strtotime($time);
    $hours = date('H', $timestamp);
    $minutes = date('i', $timestamp);
    return intval($hours) * 60 + intval($minutes);
}

function addHoursToTime($time, $hours) {
    $timestamp = strtotime($time) + ($hours * 3600);
    return date('g:i A', $timestamp);
}

function createConsolidatedRanges($times) {
    if (empty($times)) return [];

	$hourSlots = [];
    foreach ($times as $time) {
     	$timestamp = strtotime($time);
        $hour = intval(date('H', $timestamp));
        $hourSlots[] = $hour;
    }
    
	$hourSlots = array_unique($hourSlots);
    sort($hourSlots);
    
    if (count($hourSlots) === 0) return [];
    
    $ranges = [];
    $rangeStart = $hourSlots[0];
    $currentHour = $hourSlots[0];
    
	for ($i = 1; $i < count($hourSlots); $i++) {
        if ($hourSlots[$i] === $currentHour + 1) {
          	$currentHour = $hourSlots[$i];
        } else {
          	$startTime = date('g:i A', mktime($rangeStart, 0, 0));
            $endTime = date('g:i A', mktime($currentHour + 1, 0, 0));
            
            $ranges[] = [
                'start' => $startTime,
                'end' => $endTime,
                'count' => $currentHour - $rangeStart + 1
            ];
            
          	$rangeStart = $hourSlots[$i];
            $currentHour = $hourSlots[$i];
        }
    }
    
	$startTime = date('g:i A', mktime($rangeStart, 0, 0));
    $endTime = date('g:i A', mktime($currentHour + 1, 0, 0));
    
    $ranges[] = [
        'start' => $startTime,
        'end' => $endTime,
        'count' => $currentHour - $rangeStart + 1
    ];
    
    return $ranges;
}

try {
	$availability_query = "
        SELECT 
            day_of_week,
            start_time,
            end_time,
            preference_level,
            notes
        FROM to_user_availability
        WHERE user_id = ? 
        AND tenant_id = ?
        AND effective_date <= CURDATE()
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY day_of_week, start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $availability_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $availability_ranges = [];
    while ($row = mysqli_fetch_assoc($result)) {
      	$start_time = substr($row['start_time'], 0, 5);
        $end_time = substr($row['end_time'], 0, 5);
        
        $availability_ranges[] = [
            'day_of_week' => intval($row['day_of_week']),
            'start_time' => date('g:i A', strtotime($start_time)),
            'end_time' => date('g:i A', strtotime($end_time)),
            'preference_level' => $row['preference_level'],
            'notes' => $row['notes'],
            'type' => 'range'
        ];
    }
    mysqli_stmt_close($stmt);
    
	$time_prefs_query = "
        SELECT 
            day_of_week,
            time_slot,
            preference_level
        FROM to_user_time_preferences
        WHERE user_id = ? 
        AND tenant_id = ?
        ORDER BY day_of_week, time_slot
    ";
    
    $stmt2 = mysqli_prepare($dbc, $time_prefs_query);
    if (!$stmt2) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt2, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    
    $time_preferences = [];
    while ($row = mysqli_fetch_assoc($result2)) {
 	   	$time_slot = substr($row['time_slot'], 0, 5);
        
        $time_preferences[] = [
            'day_of_week' => intval($row['day_of_week']),
            'time_slot' => date('g:i A', strtotime($time_slot)),
            'preference_level' => $row['preference_level'],
            'type' => 'hourly'
        ];
    }
    mysqli_stmt_close($stmt2);
    
	$all_availability = [];

	if (!empty($availability_ranges)) {
	 	$ranges_by_day = [];
	    foreach ($availability_ranges as $range) {
	        $day = $range['day_of_week'];
	        $level = $range['preference_level'];
	        if (!isset($ranges_by_day[$day])) {
	            $ranges_by_day[$day] = [];
	        }
	        if (!isset($ranges_by_day[$day][$level])) {
	            $ranges_by_day[$day][$level] = [];
	        }
	        $ranges_by_day[$day][$level][] = $range['start_time'];
	    }
    
	 	foreach ($ranges_by_day as $day => $levels) {
	        foreach ($levels as $level => $times) {
	            $consolidatedRanges = createConsolidatedRanges($times);
            
	            foreach ($consolidatedRanges as $range) {
	                $all_availability[] = [
	                    'day_of_week' => $day,
	                    'start_time' => $range['start'],
	                    'end_time' => $range['end'],
	                    'preference_level' => $level,
	                    'type' => 'consolidated_range',
	                    'time_count' => $range['count'],
	                    'original_times' => $times
	                ];
	            }
	        }
	    }
	}

	if (!empty($time_preferences) && empty($all_availability)) {
	  	$grouped_by_day = [];
	    foreach ($time_preferences as $pref) {
	        $day = $pref['day_of_week'];
	        $level = $pref['preference_level'];
	        if (!isset($grouped_by_day[$day])) {
	            $grouped_by_day[$day] = [];
	        }
	        if (!isset($grouped_by_day[$day][$level])) {
	            $grouped_by_day[$day][$level] = [];
	        }
	        $grouped_by_day[$day][$level][] = $pref['time_slot'];
	    }
    
	  	foreach ($grouped_by_day as $day => $levels) {
	        foreach ($levels as $level => $times) {
	            $consolidatedRanges = createConsolidatedRanges($times);
            	foreach ($consolidatedRanges as $range) {
	                $all_availability[] = [
	                    'day_of_week' => $day,
	                    'start_time' => $range['start'],
	                    'end_time' => $range['end'],
	                    'preference_level' => $level,
	                    'type' => 'consolidated_hourly',
	                    'time_count' => $range['count'],
	                    'original_times' => $times
	                ];
	            }
	        }
	    }
	}
    
 	$days_with_preferences = [];
    $total_preferred_hours = 0;
    $total_available_hours = 0;
    
    foreach ($all_availability as $avail) {
        $days_with_preferences[$avail['day_of_week']] = true;
        
      	if (isset($avail['start_time']) && isset($avail['end_time'])) {
            $start = strtotime($avail['start_time']);
            $end = strtotime($avail['end_time']);
            if ($start && $end && $end > $start) {
                $hours = ($end - $start) / 3600;
                if ($avail['preference_level'] === 'preferred') {
                    $total_preferred_hours += $hours;
                } elseif ($avail['preference_level'] === 'available') {
                    $total_available_hours += $hours;
                }
            }
        } elseif (isset($avail['time_count'])) {
          	$hours = $avail['time_count'];
            if ($avail['preference_level'] === 'preferred') {
                $total_preferred_hours += $hours;
            } elseif ($avail['preference_level'] === 'available') {
                $total_available_hours += $hours;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $all_availability,
        'statistics' => [
            'total_preferred_hours' => round($total_preferred_hours, 1),
            'total_available_hours' => round($total_available_hours, 1),
            'days_with_preferences' => count($days_with_preferences),
            'has_availability_set' => !empty($all_availability),
            'data_type' => !empty($availability_ranges) ? 'ranges' : (!empty($time_preferences) ? 'hourly' : 'none')
        ]
    ]);
    
} catch (Exception $e) {
    // Log the detailed error server-side for debugging
    error_log('Employee availability query error (User ID: ' . $user_id . ', Tenant ID: ' . $tenant_id . '): ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving availability data. Please try again or contact support.'
    ]);
}
?>