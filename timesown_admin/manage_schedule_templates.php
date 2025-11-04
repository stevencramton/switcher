<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('America/New_York');

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input && isset($_POST['action'])) {
    $input = $_POST;
}

if (!isset($input['action']) || !isset($input['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Action and tenant_id are required']);
    exit();
}

$action = $input['action'];
$tenant_id = intval($input['tenant_id']);

if ($tenant_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant_id']);
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

if (!checkRole('admin_developer')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this tenant']);
        exit();
    }
}

switch ($action) {
	case 'check_week_shifts':
	    checkWeekShifts($dbc, $input, $tenant_id, $user_id);
	    break;
		
    case 'get_templates':
        getScheduleTemplates($dbc, $tenant_id);
        break;
        
    case 'save_template':
        saveScheduleTemplate($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'apply_template':
        applyScheduleTemplate($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'apply_template_span':
        applyScheduleTemplateSpan($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'apply_template_original_range':
        applyScheduleTemplateOriginalRange($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'bulk_apply_template':
        bulkApplyTemplate($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'delete_template':
        deleteScheduleTemplate($dbc, $input, $tenant_id, $user_id);
        break;
        
    case 'copy_previous_week':
        copyPreviousWeek($dbc, $input, $tenant_id, $user_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
}

function getScheduleTemplates($dbc, $tenant_id) {
    $query = "
        SELECT t.*, u.first_name, u.last_name, u.display_name
        FROM to_schedule_templates t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.tenant_id = ?
        ORDER BY t.created_at DESC
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $templates = [];
    while ($template = mysqli_fetch_assoc($result)) {
        $template_data = json_decode($template['template_data'], true);
        $creator_name = $template['display_name'] ?: ($template['first_name'] . ' ' . $template['last_name']);
        
		$templates[] = [
		    'id' => $template['id'],
		    'name' => $template['name'],
		    'description' => $template['description'],
		    'shifts_count' => count($template_data['shifts'] ?? []),
		    'date_range_days' => $template_data['date_range_days'] ?? 1,
		    'start_date' => $template_data['original_start_date'] ?? null,
		    'end_date' => $template_data['original_end_date'] ?? null,
		    'created_by_name' => trim($creator_name),
		    'created_at' => $template['created_at'],
		    'updated_at' => $template['updated_at']
		];
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'count' => count($templates)
    ]);
}

function saveScheduleTemplate($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_name']) || !isset($input['start_date']) || !isset($input['end_date'])) {
        echo json_encode(['success' => false, 'message' => 'Template name, start_date and end_date are required']);
        return;
    }
    
    $template_name = trim($input['template_name']);
    $start_date = $input['start_date'];
    $end_date = $input['end_date'];
    $description = isset($input['description']) ? trim($input['description']) : '';
    
    if (empty($template_name)) {
        echo json_encode(['success' => false, 'message' => 'Template name cannot be empty']);
        return;
    }
    
	$shifts_query = "
        SELECT s.*, d.name as department_name, jr.name as role_name
        FROM to_shifts s
        LEFT JOIN to_departments d ON s.department_id = d.id
        LEFT JOIN to_job_roles jr ON s.job_role_id = jr.id
        WHERE s.tenant_id = ? AND s.shift_date >= ? AND s.shift_date <= ?
        ORDER BY s.shift_date, s.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $shifts_query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $shifts_result = mysqli_stmt_get_result($stmt);
    
    $shifts_data = [];
    while ($shift = mysqli_fetch_assoc($shifts_result)) {
        $shift_date = new DateTime($shift['shift_date']);
        $template_start = new DateTime($start_date);
        $day_offset = $shift_date->diff($template_start)->days;
        
        $shifts_data[] = [
            'day_offset' => $day_offset,
            'start_time' => $shift['start_time'],
            'end_time' => $shift['end_time'],
            'department_id' => $shift['department_id'],
            'job_role_id' => $shift['job_role_id'],
            'assigned_user_id' => $shift['assigned_user_id'],
            'status' => $shift['status'],
            'shift_color' => $shift['shift_color'],
            'shift_text_color' => $shift['shift_text_color'],
            'public_notes' => $shift['public_notes']
        ];
    }
    
    if (empty($shifts_data)) {
        echo json_encode(['success' => false, 'message' => 'No shifts found in the specified date range']);
        return;
    }
    
    $template_data = [
        'date_range_days' => (strtotime($end_date) - strtotime($start_date)) / (24 * 60 * 60) + 1,
        'original_start_date' => $start_date,
        'original_end_date' => $end_date,
        'shifts' => $shifts_data
    ];
    
    $insert_query = "INSERT INTO to_schedule_templates (tenant_id, name, description, template_data, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($dbc, $insert_query);
    $template_json = json_encode($template_data);
    mysqli_stmt_bind_param($stmt, 'isssi', $tenant_id, $template_name, $description, $template_json, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Template saved successfully',
            'template_id' => mysqli_insert_id($dbc),
            'shifts_count' => count($shifts_data)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save template']);
    }
}

function applyScheduleTemplate($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_id']) || !isset($input['target_start_date'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID and target start date are required']);
        return;
    }
    
    $template_id = intval($input['template_id']);
    $target_start_date = $input['target_start_date'];
    $apply_count = isset($input['apply_count']) ? intval($input['apply_count']) : 1;
    
    $template_query = "SELECT * FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $template_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $template_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($template_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        return;
    }
    
    $template = mysqli_fetch_assoc($template_result);
    $template_data = json_decode($template['template_data'], true);
    
    $created_shifts = [];
    $skipped_shifts = [];
    
    // Apply the template the specified number of times
    for ($i = 0; $i < $apply_count; $i++) {
        $current_start_date = new DateTime($target_start_date);
        
        // Only add weeks for multiple applications (i > 0)
        // This fixes the bug where even single applications were getting week offsets
        if ($i > 0) {
            $current_start_date->modify("+{$i} weeks");
        }
        
        foreach ($template_data['shifts'] as $shift_template) {
            $shift_date = clone $current_start_date;
            $shift_date->modify("+{$shift_template['day_offset']} days");
            $shift_date_str = $shift_date->format('Y-m-d');
            
            // Check if identical shift already exists
            $exists_query = "
                SELECT id FROM to_shifts 
                WHERE tenant_id = ? 
                AND department_id = ? 
                AND job_role_id = ? 
                AND shift_date = ? 
                AND start_time = ? 
                AND end_time = ?
            ";
            $stmt = mysqli_prepare($dbc, $exists_query);
            mysqli_stmt_bind_param($stmt, 'iiiiss', 
                $tenant_id, 
                $shift_template['department_id'], 
                $shift_template['job_role_id'], 
                $shift_date_str, 
                $shift_template['start_time'],
                $shift_template['end_time']
            );
            mysqli_stmt_execute($stmt);
            $exists_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($exists_result) > 0) {
                $skipped_shifts[] = [
                    'date' => $shift_date_str, 
                    'time' => $shift_template['start_time'],
                    'reason' => 'Identical shift already exists'
                ];
                continue;
            }
            
            // Check for overlapping shifts if user is assigned
            if ($shift_template['assigned_user_id']) {
                $overlap_query = "
                    SELECT id FROM to_shifts 
                    WHERE tenant_id = ? 
                    AND assigned_user_id = ? 
                    AND shift_date = ? 
                    AND (
                        (start_time <= ? AND end_time > ?) OR 
                        (start_time < ? AND end_time >= ?) OR
                        (start_time >= ? AND end_time <= ?)
                    )
                ";
                $stmt = mysqli_prepare($dbc, $overlap_query);
                mysqli_stmt_bind_param($stmt, 'iisssssss', 
                    $tenant_id, 
                    $shift_template['assigned_user_id'], 
                    $shift_date_str,
                    $shift_template['start_time'], $shift_template['start_time'],
                    $shift_template['end_time'], $shift_template['end_time'],
                    $shift_template['start_time'], $shift_template['end_time']
                );
                mysqli_stmt_execute($stmt);
                $overlap_result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($overlap_result) > 0) {
                    $skipped_shifts[] = [
                        'date' => $shift_date_str, 
                        'time' => $shift_template['start_time'],
                        'reason' => 'User already has overlapping shift'
                    ];
                    continue;
                }
            }
            
            // Insert the shift
            $insert_shift_query = "
                INSERT INTO to_shifts (tenant_id, department_id, job_role_id, assigned_user_id, shift_date, start_time, end_time, status, shift_color, shift_text_color, public_notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = mysqli_prepare($dbc, $insert_shift_query);
            mysqli_stmt_bind_param($stmt, 'iiiisssssssi',
                $tenant_id,
                $shift_template['department_id'],
                $shift_template['job_role_id'],
                $shift_template['assigned_user_id'],
                $shift_date_str,
                $shift_template['start_time'],
                $shift_template['end_time'],
                $shift_template['status'],
                $shift_template['shift_color'],
                $shift_template['shift_text_color'],
                $shift_template['public_notes'],
                $user_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $created_shifts[] = ['id' => mysqli_insert_id($dbc), 'date' => $shift_date_str];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Template applied successfully',
        'created_count' => count($created_shifts),
        'skipped_count' => count($skipped_shifts)
    ]);
}

function applyScheduleTemplateSpan($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_id']) || !isset($input['target_start_date']) || !isset($input['original_days'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID, target start date, and original days are required']);
        return;
    }
    
    $template_id = intval($input['template_id']);
    $target_start_date = $input['target_start_date'];
    $original_days = intval($input['original_days']);
    
   	$template_query = "SELECT * FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $template_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $template_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($template_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        return;
    }
    
    $template = mysqli_fetch_assoc($template_result);
    $template_data = json_decode($template['template_data'], true);
    
    $created_shifts = [];
    $skipped_shifts = [];
    
  	$current_start_date = new DateTime($target_start_date);
    
    foreach ($template_data['shifts'] as $shift_template) {
        $shift_date = clone $current_start_date;
        $shift_date->modify("+{$shift_template['day_offset']} days");
        $shift_date_str = $shift_date->format('Y-m-d');
        
      	$exists_query = "
            SELECT id FROM to_shifts 
            WHERE tenant_id = ? 
            AND department_id = ? 
            AND job_role_id = ? 
            AND shift_date = ? 
            AND start_time = ? 
            AND end_time = ?
        ";
        $stmt = mysqli_prepare($dbc, $exists_query);
        mysqli_stmt_bind_param($stmt, 'iiiiss', 
            $tenant_id, 
            $shift_template['department_id'], 
            $shift_template['job_role_id'], 
            $shift_date_str, 
            $shift_template['start_time'],
            $shift_template['end_time']
        );
        mysqli_stmt_execute($stmt);
        $exists_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($exists_result) > 0) {
            $skipped_shifts[] = [
                'date' => $shift_date_str, 
                'time' => $shift_template['start_time'],
                'reason' => 'Identical shift already exists'
            ];
            continue;
        }
        
       	$insert_shift_query = "
            INSERT INTO to_shifts (tenant_id, department_id, job_role_id, assigned_user_id, shift_date, start_time, end_time, status, shift_color, shift_text_color, public_notes, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $insert_shift_query);
        mysqli_stmt_bind_param($stmt, 'iiiisssssssi',
            $tenant_id,
            $shift_template['department_id'],
            $shift_template['job_role_id'],
            $shift_template['assigned_user_id'],
            $shift_date_str,
            $shift_template['start_time'],
            $shift_template['end_time'],
            $shift_template['status'],
            $shift_template['shift_color'],
            $shift_template['shift_text_color'],
            $shift_template['public_notes'],
            $user_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $created_shifts[] = ['id' => mysqli_insert_id($dbc), 'date' => $shift_date_str];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Template applied successfully',
        'created_count' => count($created_shifts),
        'skipped_count' => count($skipped_shifts)
    ]);
}

function applyScheduleTemplateOriginalRange($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_id']) || !isset($input['original_start_date']) || !isset($input['original_end_date'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID and original date range are required']);
        return;
    }
    
    $template_id = intval($input['template_id']);
    $original_start_date = $input['original_start_date'];
    $original_end_date = $input['original_end_date'];
    
   	$template_query = "SELECT * FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $template_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $template_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($template_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        return;
    }
    
    $template = mysqli_fetch_assoc($template_result);
    $template_data = json_decode($template['template_data'], true);
    
    $created_shifts = [];
    $skipped_shifts = [];
    
   	$target_start_date = new DateTime($original_start_date);
    
    foreach ($template_data['shifts'] as $shift_template) {
        $shift_date = clone $target_start_date;
        $shift_date->modify("+{$shift_template['day_offset']} days");
        $shift_date_str = $shift_date->format('Y-m-d');
        
       	$exists_query = "
            SELECT id FROM to_shifts 
            WHERE tenant_id = ? 
            AND department_id = ? 
            AND job_role_id = ? 
            AND shift_date = ? 
            AND start_time = ? 
            AND end_time = ?
        ";
        $stmt = mysqli_prepare($dbc, $exists_query);
        mysqli_stmt_bind_param($stmt, 'iiiiss', 
            $tenant_id, 
            $shift_template['department_id'], 
            $shift_template['job_role_id'], 
            $shift_date_str, 
            $shift_template['start_time'],
            $shift_template['end_time']
        );
        mysqli_stmt_execute($stmt);
        $exists_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($exists_result) > 0) {
            $skipped_shifts[] = [
                'date' => $shift_date_str, 
                'time' => $shift_template['start_time'],
                'reason' => 'Identical shift already exists'
            ];
            continue;
        }
        
       	if ($shift_template['assigned_user_id']) {
            $overlap_query = "
                SELECT id FROM to_shifts 
                WHERE tenant_id = ? 
                AND assigned_user_id = ? 
                AND shift_date = ? 
                AND (
                    (start_time <= ? AND end_time > ?) OR 
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )
            ";
            $stmt = mysqli_prepare($dbc, $overlap_query);
            mysqli_stmt_bind_param($stmt, 'iisssssss', 
                $tenant_id, 
                $shift_template['assigned_user_id'], 
                $shift_date_str,
                $shift_template['start_time'], $shift_template['start_time'],
                $shift_template['end_time'], $shift_template['end_time'],
                $shift_template['start_time'], $shift_template['end_time']
            );
            mysqli_stmt_execute($stmt);
            $overlap_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($overlap_result) > 0) {
                $skipped_shifts[] = [
                    'date' => $shift_date_str, 
                    'time' => $shift_template['start_time'],
                    'reason' => 'User already has overlapping shift'
                ];
                continue;
            }
        }
        
        $insert_shift_query = "
            INSERT INTO to_shifts (tenant_id, department_id, job_role_id, assigned_user_id, shift_date, start_time, end_time, status, shift_color, shift_text_color, public_notes, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $insert_shift_query);
        mysqli_stmt_bind_param($stmt, 'iiiisssssssi',
            $tenant_id,
            $shift_template['department_id'],
            $shift_template['job_role_id'],
            $shift_template['assigned_user_id'],
            $shift_date_str,
            $shift_template['start_time'],
            $shift_template['end_time'],
            $shift_template['status'],
            $shift_template['shift_color'],
            $shift_template['shift_text_color'],
            $shift_template['public_notes'],
            $user_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $created_shifts[] = ['id' => mysqli_insert_id($dbc), 'date' => $shift_date_str];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Template applied successfully',
        'created_count' => count($created_shifts),
        'skipped_count' => count($skipped_shifts)
    ]);
}

function bulkApplyTemplate($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_id']) || !isset($input['start_date']) || !isset($input['end_date']) || !isset($input['apply_range'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID, start date, end date, and apply range are required']);
        return;
    }
    
    $template_id = intval($input['template_id']);
    $start_date = $input['start_date'];
    $end_date = $input['end_date'];
    $apply_range = $input['apply_range'];
    
   	$template_query = "SELECT * FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $template_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $template_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($template_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        return;
    }
    
    $template = mysqli_fetch_assoc($template_result);
    $template_data = json_decode($template['template_data'], true);
    
    $created_shifts = [];
    $skipped_shifts = [];
    $error_count = 0;
    
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        foreach ($template_data['shifts'] as $shift_template) {
            $shift_date = clone $current_date;
            $shift_date->modify("+{$shift_template['day_offset']} days");
            $shift_date_str = $shift_date->format('Y-m-d');
            
           	if ($shift_date > $end_date_obj) {
                continue;
            }
            
           	$exists_query = "
                SELECT id FROM to_shifts 
                WHERE tenant_id = ? 
                AND department_id = ? 
                AND job_role_id = ? 
                AND shift_date = ? 
                AND start_time = ? 
                AND end_time = ?
            ";
            $stmt = mysqli_prepare($dbc, $exists_query);
            mysqli_stmt_bind_param($stmt, 'iiiiss', 
                $tenant_id, 
                $shift_template['department_id'], 
                $shift_template['job_role_id'], 
                $shift_date_str, 
                $shift_template['start_time'],
                $shift_template['end_time']
            );
            mysqli_stmt_execute($stmt);
            $exists_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($exists_result) > 0) {
                $skipped_shifts[] = [
                    'date' => $shift_date_str, 
                    'time' => $shift_template['start_time'],
                    'reason' => 'Identical shift already exists'
                ];
                continue;
            }
            
           	$insert_shift_query = "
                INSERT INTO to_shifts (tenant_id, department_id, job_role_id, assigned_user_id, shift_date, start_time, end_time, status, shift_color, shift_text_color, public_notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $stmt = mysqli_prepare($dbc, $insert_shift_query);
            mysqli_stmt_bind_param($stmt, 'iiiisssssssi',
                $tenant_id,
                $shift_template['department_id'],
                $shift_template['job_role_id'],
                $shift_template['assigned_user_id'],
                $shift_date_str,
                $shift_template['start_time'],
                $shift_template['end_time'],
                $shift_template['status'],
                $shift_template['shift_color'],
                $shift_template['shift_text_color'],
                $shift_template['public_notes'],
                $user_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $created_shifts[] = ['id' => mysqli_insert_id($dbc), 'date' => $shift_date_str];
            } else {
                $error_count++;
            }
        }
        
       	if ($apply_range === 'week') {
            $current_date->modify('+1 week');
        } else {
            $current_date->modify('+1 month');
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Bulk template application completed',
        'created_count' => count($created_shifts),
        'skipped_count' => count($skipped_shifts),
        'error_count' => $error_count
    ]);
}

function deleteScheduleTemplate($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['template_id'])) {
        echo json_encode(['success' => false, 'message' => 'Template ID is required']);
        return;
    }
    
    $template_id = intval($input['template_id']);
    
   	$check_query = "SELECT id, name FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Template not found']);
        return;
    }
    
    $template = mysqli_fetch_assoc($result);
    
    $delete_query = "DELETE FROM to_schedule_templates WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_query);
    mysqli_stmt_bind_param($stmt, 'ii', $template_id, $tenant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Template deleted successfully',
            'template_name' => $template['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete template']);
    }
}

function copyPreviousWeek($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['source_start_date']) || !isset($input['target_start_date'])) {
        echo json_encode(['success' => false, 'message' => 'Source and target start dates are required']);
        return;
    }
    
    $source_start = $input['source_start_date'];
    $source_end = isset($input['source_end_date']) ? $input['source_end_date'] : date('Y-m-d', strtotime($source_start . ' +6 days'));
    $target_start = $input['target_start_date'];
    
   	$source_shifts_query = "
        SELECT s.* FROM to_shifts s
        WHERE s.tenant_id = ? AND s.shift_date >= ? AND s.shift_date <= ?
        ORDER BY s.shift_date, s.start_time
    ";
    
    $stmt = mysqli_prepare($dbc, $source_shifts_query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $source_start, $source_end);
    mysqli_stmt_execute($stmt);
    $source_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($source_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'No shifts found in source week']);
        return;
    }
    
    $created_shifts = [];
    $skipped_shifts = [];
    
    while ($source_shift = mysqli_fetch_assoc($source_result)) {
       	$source_date = new DateTime($source_shift['shift_date']);
        $source_week_start = new DateTime($source_start);
        $day_offset = $source_date->diff($source_week_start)->days;
        
        $target_date = new DateTime($target_start);
        $target_date->modify("+{$day_offset} days");
        $target_date_str = $target_date->format('Y-m-d');
        
       	$exists_query = "
            SELECT id FROM to_shifts 
            WHERE tenant_id = ? 
            AND department_id = ? 
            AND job_role_id = ? 
            AND shift_date = ? 
            AND start_time = ? 
            AND end_time = ?
        ";
        $stmt = mysqli_prepare($dbc, $exists_query);
        mysqli_stmt_bind_param($stmt, 'iiiiss', 
            $tenant_id, 
            $source_shift['department_id'], 
            $source_shift['job_role_id'], 
            $target_date_str, 
            $source_shift['start_time'],
            $source_shift['end_time']
        );
        mysqli_stmt_execute($stmt);
        $exists_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($exists_result) > 0) {
            $skipped_shifts[] = [
                'date' => $target_date_str, 
                'time' => $source_shift['start_time'],
                'reason' => 'Identical shift already exists'
            ];
            continue;
        }
        
       	if ($source_shift['assigned_user_id']) {
            $overlap_query = "
                SELECT id FROM to_shifts 
                WHERE tenant_id = ? 
                AND assigned_user_id = ? 
                AND shift_date = ? 
                AND (
                    (start_time < ? AND end_time > ?) OR 
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND start_time < ?)
                )
            ";
            $stmt = mysqli_prepare($dbc, $overlap_query);
            mysqli_stmt_bind_param($stmt, 'iisssssss', 
                $tenant_id, 
                $source_shift['assigned_user_id'], 
                $target_date_str,
               	$source_shift['start_time'], $source_shift['start_time'],
                $source_shift['end_time'], $source_shift['end_time'],
                $source_shift['start_time'], $source_shift['end_time']
            );
            mysqli_stmt_execute($stmt);
            $overlap_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($overlap_result) > 0) {
                $skipped_shifts[] = [
                    'date' => $target_date_str, 
                    'time' => $source_shift['start_time'],
                    'reason' => 'User already has overlapping shift'
                ];
                continue;
            }
        }
        
      	$insert_shift_query = "
            INSERT INTO to_shifts (tenant_id, department_id, job_role_id, assigned_user_id, shift_date, start_time, end_time, status, shift_color, shift_text_color, public_notes, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = mysqli_prepare($dbc, $insert_shift_query);
        mysqli_stmt_bind_param($stmt, 'iiiisssssssi',
            $tenant_id,
            $source_shift['department_id'],
            $source_shift['job_role_id'],
            $source_shift['assigned_user_id'],
            $target_date_str,
            $source_shift['start_time'],
            $source_shift['end_time'],
            $source_shift['status'],
            $source_shift['shift_color'],
            $source_shift['shift_text_color'],
            $source_shift['public_notes'],
            $user_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $created_shifts[] = ['id' => mysqli_insert_id($dbc), 'date' => $target_date_str];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Previous week copied successfully',
        'created_count' => count($created_shifts),
        'skipped_count' => count($skipped_shifts)
    ]);
}

function checkWeekShifts($dbc, $input, $tenant_id, $user_id) {
    if (!isset($input['start_date']) || !isset($input['end_date'])) {
        echo json_encode(['success' => false, 'message' => 'Start date and end date are required']);
        return;
    }
    
    $start_date = $input['start_date'];
    $end_date = $input['end_date'];
    
   	$count_query = "
        SELECT COUNT(*) as shift_count 
        FROM to_shifts 
        WHERE tenant_id = ? 
        AND shift_date >= ? 
        AND shift_date <= ?
    ";
    
    $stmt = mysqli_prepare($dbc, $count_query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $shift_count = intval($row['shift_count']);
        
        echo json_encode([
            'success' => true,
            'shift_count' => $shift_count,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to check shifts for the specified date range'
        ]);
    }
}
?>