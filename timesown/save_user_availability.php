<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

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
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$switch_id = $_SESSION['switch_id'];
$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $switch_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($user_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$user_id = $user_data['id'];
$tenant_id = isset($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : 1;
$preferences_json = isset($_POST['preferences']) ? $_POST['preferences'] : null;
$availability_json = isset($_POST['availability']) ? $_POST['availability'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

try {
    mysqli_autocommit($dbc, false);
    
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS `to_user_time_preferences` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` int UNSIGNED NOT NULL,
            `tenant_id` int UNSIGNED NOT NULL,
            `day_of_week` tinyint NOT NULL,
            `time_slot` time NOT NULL,
            `preference_level` enum('preferred','available','dislikes','cannot_work') NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_time_unique` (`user_id`, `tenant_id`, `day_of_week`, `time_slot`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`tenant_id`) REFERENCES `to_tenants` (`id`) ON DELETE CASCADE,
            INDEX `idx_user_tenant` (`user_id`, `tenant_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    mysqli_query($dbc, $create_table_query);
    
    if ($preferences_json !== null) {
        $preferences_data = json_decode($preferences_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            mysqli_rollback($dbc);
            echo json_encode(['success' => false, 'message' => 'Invalid preferences data']);
            exit();
        }
        
        $delete_prefs_query = "DELETE FROM to_user_time_preferences WHERE user_id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $delete_prefs_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        
        if (!empty($preferences_data)) {
            $insert_prefs_query = "
                INSERT INTO to_user_time_preferences 
                (user_id, tenant_id, day_of_week, time_slot, preference_level) 
                VALUES (?, ?, ?, ?, ?)
            ";
            
            $stmt = mysqli_prepare($dbc, $insert_prefs_query);
            
            foreach ($preferences_data as $pref) {
                if (!isset($pref['day_of_week']) || !isset($pref['time_slot']) || !isset($pref['preference_level'])) {
                    mysqli_rollback($dbc);
                    echo json_encode(['success' => false, 'message' => 'Missing required preference fields']);
                    exit();
                }
                
                $day_of_week = (int)$pref['day_of_week'];
                $time_slot = $pref['time_slot'];
                $preference_level = $pref['preference_level'];
                
                if ($day_of_week < 0 || $day_of_week > 6) {
                    mysqli_rollback($dbc);
                    echo json_encode(['success' => false, 'message' => 'Invalid day of week']);
                    exit();
                }
                
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time_slot)) {
                    mysqli_rollback($dbc);
                    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
                    exit();
                }
                
                if (!in_array($preference_level, ['preferred', 'available', 'dislikes', 'cannot_work'])) {
                    mysqli_rollback($dbc);
                    echo json_encode(['success' => false, 'message' => 'Invalid preference level']);
                    exit();
                }
                
                mysqli_stmt_bind_param($stmt, 'iiiss', 
                    $user_id, $tenant_id, $day_of_week, $time_slot, $preference_level);
                
                if (!mysqli_stmt_execute($stmt)) {
                    mysqli_rollback($dbc);
                    echo json_encode(['success' => false, 'message' => 'Error saving preference: ' . mysqli_error($dbc)]);
                    exit();
                }
            }
        }
        
        $availability_ranges = convertPreferencesToAvailability($preferences_data);
        $records_saved = count($preferences_data);
        $save_type = 'detailed preferences';
        
    } else if ($availability_json !== null) {
        $availability_data = json_decode($availability_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            mysqli_rollback($dbc);
            echo json_encode(['success' => false, 'message' => 'Invalid availability data']);
            exit();
        }
        
        $availability_ranges = $availability_data;
        $records_saved = count($availability_data);
        $save_type = 'availability ranges';
        
        $delete_prefs_query = "DELETE FROM to_user_time_preferences WHERE user_id = ? AND tenant_id = ?";
        $stmt = mysqli_prepare($dbc, $delete_prefs_query);
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
        mysqli_stmt_execute($stmt);
        
    } else {
        mysqli_rollback($dbc);
        echo json_encode(['success' => false, 'message' => 'No availability or preferences data provided']);
        exit();
    }
    
    $delete_avail_query = "DELETE FROM to_user_availability WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_avail_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_rollback($dbc);
        echo json_encode(['success' => false, 'message' => 'Error clearing existing availability']);
        exit();
    }
    
    if (!empty($availability_ranges)) {
		$insert_avail_query = "
		    INSERT INTO to_user_availability 
		    (user_id, tenant_id, day_of_week, start_time, end_time, preference_level, notes, effective_date) 
		    VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
		";
        
        $stmt = mysqli_prepare($dbc, $insert_avail_query);
        
        foreach ($availability_ranges as $avail) {
            if (!isset($avail['day_of_week']) || !isset($avail['start_time']) || 
                !isset($avail['end_time']) || !isset($avail['preference_level'])) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Missing required availability fields']);
                exit();
            }
            
            $day_of_week = (int)$avail['day_of_week'];
            $start_time = $avail['start_time'];
            $end_time = $avail['end_time'];
            $preference_level = $avail['preference_level'];
            
            if ($day_of_week < 0 || $day_of_week > 6) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Invalid day of week']);
                exit();
            }
            
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) ||
                !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Invalid time format']);
                exit();
            }
            
            if (!in_array($preference_level, ['preferred', 'available', 'unavailable', 'dislikes', 'cannot_work'])) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Invalid preference level']);
                exit();
            }
            
            if (strtotime($start_time) >= strtotime($end_time)) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Start time must be before end time']);
                exit();
            }
            
			mysqli_stmt_bind_param($stmt, 'iiissss', 
			    $user_id, $tenant_id, $day_of_week, $start_time, $end_time, $preference_level, $notes);
            
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_rollback($dbc);
                echo json_encode(['success' => false, 'message' => 'Error saving availability: ' . mysqli_error($dbc)]);
                exit();
            }
        }
    }
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent)
        VALUES (?, ?, 'UPDATE', 'to_user_availability', ?, ?, ?, ?)
    ";
    
    $new_values = json_encode([
        'type' => $save_type,
        'records_saved' => $records_saved,
        'availability_ranges' => count($availability_ranges)
    ]);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', $tenant_id, $user_id, $user_id, $new_values, $ip_address, $user_agent);
    mysqli_stmt_execute($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($save_type) . ' saved successfully',
        'records_saved' => $records_saved,
        'availability_ranges' => count($availability_ranges),
        'type' => $save_type
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function convertPreferencesToAvailability($preferences_data) {
    $ranges = [];
 	$by_day = [];
    
	foreach ($preferences_data as $pref) {
        $day = $pref['day_of_week'];
        $level = $pref['preference_level'];
        if (!isset($by_day[$day])) {
            $by_day[$day] = [];
        }
        if (!isset($by_day[$day][$level])) {
            $by_day[$day][$level] = [];
        }
        $by_day[$day][$level][] = $pref['time_slot'];
    }
    
    foreach ($by_day as $day => $levels) {
        foreach ($levels as $level => $times) {
            if ($level === 'cannot_work') {
                $level = 'unavailable';
            }
            
            sort($times);
            $ranges = array_merge($ranges, createTimeRanges($day, $times, $level));
        }
    }
    
    return $ranges;
}

function createTimeRanges($day, $times, $level) {
    $ranges = [];
    if (empty($times)) return $ranges;
    
    $start = $times[0];
    $end = $times[0];
    
    for ($i = 1; $i < count($times); $i++) {
        $current = $times[$i];
        $prev = $times[$i - 1];
        
        $prevMinutes = timeToMinutes($prev);
        $currentMinutes = timeToMinutes($current);
        
        if ($currentMinutes - $prevMinutes <= 30) {
           $end = $current;
        } else {
            $ranges[] = [
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => addMinutesToTime($end, 30),
                'preference_level' => $level
            ];
            $start = $current;
            $end = $current;
        }
    }
    
   	$ranges[] = [
        'day_of_week' => $day,
        'start_time' => $start,
        'end_time' => addMinutesToTime($end, 30),
        'preference_level' => $level
    ];
    
    return $ranges;
}

function timeToMinutes($time) {
    list($hours, $minutes) = explode(':', $time);
    return intval($hours) * 60 + intval($minutes);
}

function addMinutesToTime($time, $minutes) {
    $totalMinutes = timeToMinutes($time) + $minutes;
    $hours = intval($totalMinutes / 60);
    $mins = $totalMinutes % 60;
    return sprintf('%02d:%02d', min($hours, 23), $mins);
}
?>