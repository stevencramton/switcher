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

// Get user ID
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

// Check tenant access
if (!checkRole('admin_developer')) {
    $tenant_access_query = "SELECT id FROM to_user_tenants WHERE user_id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $tenant_access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $access_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($access_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this organization']);
        exit();
    }
}

switch ($action) {
    case 'get_closed_days':
        handleGetClosedDays($dbc, $tenant_id, $input);
        break;
        
    case 'add_closed_day':
        handleAddClosedDay($dbc, $tenant_id, $user_id, $input);
        break;
        
    case 'add_bulk_closed_days':
        handleAddBulkClosedDays($dbc, $tenant_id, $user_id, $input);
        break;
        
    case 'update_closed_day':
        handleUpdateClosedDay($dbc, $tenant_id, $user_id, $input);
        break;
        
    case 'delete_closed_day':
        handleDeleteClosedDay($dbc, $tenant_id, $input);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleGetClosedDays($dbc, $tenant_id, $input) {
    $start_date = isset($input['start_date']) ? $input['start_date'] : null;
    $end_date = isset($input['end_date']) ? $input['end_date'] : null;
    
    $query = "SELECT cd.*, u.first_name, u.last_name 
              FROM to_closed_days cd 
              LEFT JOIN users u ON cd.created_by = u.id 
              WHERE cd.tenant_id = ?";
    $params = [$tenant_id];
    $types = 'i';
    
    if ($start_date && $end_date) {
        $query .= " AND cd.date BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= 'ss';
    }
    
    $query .= " ORDER BY cd.date ASC";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $closed_days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $closed_days[] = [
            'id' => $row['id'],
            'date' => $row['date'],
            'type' => $row['type'],
            'title' => $row['title'],
            'notes' => $row['notes'],
            'allow_shifts' => (bool)$row['allow_shifts'],
            'created_by' => $row['first_name'] . ' ' . $row['last_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'closed_days' => $closed_days]);
}

function handleAddClosedDay($dbc, $tenant_id, $user_id, $input) {
    if (!isset($input['date']) || !isset($input['title']) || !isset($input['type'])) {
        echo json_encode(['success' => false, 'message' => 'Date, title, and type are required']);
        return;
    }
    
    $date = $input['date'];
    $title = trim($input['title']);
    $type = $input['type'];
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    $allow_shifts = isset($input['allow_shifts']) ? (bool)$input['allow_shifts'] : false;
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }
    
    // Validate type
    if (!in_array($type, ['closed', 'holiday'])) {
        echo json_encode(['success' => false, 'message' => 'Type must be either "closed" or "holiday"']);
        return;
    }
    
    // Check if date already exists for this tenant
    $check_query = "SELECT id FROM to_closed_days WHERE tenant_id = ? AND date = ?";
    $stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($stmt, 'is', $tenant_id, $date);
    mysqli_stmt_execute($stmt);
    $check_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'A closed day or holiday already exists for this date']);
        return;
    }
    
    // Insert new closed day
    $insert_query = "INSERT INTO to_closed_days (tenant_id, date, type, title, notes, allow_shifts, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $insert_query);
    mysqli_stmt_bind_param($stmt, 'isssiii', $tenant_id, $date, $type, $title, $notes, $allow_shifts, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($dbc);
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($type) . ' added successfully',
            'id' => $new_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add ' . $type]);
    }
}

function handleAddBulkClosedDays($dbc, $tenant_id, $user_id, $input) {
    if (!isset($input['dates']) || !isset($input['title']) || !isset($input['type'])) {
        echo json_encode(['success' => false, 'message' => 'Dates, title, and type are required']);
        return;
    }
    
    $dates = $input['dates'];
    $title = trim($input['title']);
    $type = $input['type'];
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    $allow_shifts = isset($input['allow_shifts']) ? (bool)$input['allow_shifts'] : false;
    
    if (!is_array($dates) || empty($dates)) {
        echo json_encode(['success' => false, 'message' => 'At least one date must be selected']);
        return;
    }
    
    // Validate type
    if (!in_array($type, ['closed', 'holiday'])) {
        echo json_encode(['success' => false, 'message' => 'Type must be either "closed" or "holiday"']);
        return;
    }
    
    $success_count = 0;
    $failed_dates = [];
    $duplicate_dates = [];
    
    // Begin transaction
    mysqli_begin_transaction($dbc);
    
    try {
        foreach ($dates as $date) {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
                $failed_dates[] = $date . ' (invalid format)';
                continue;
            }
            
            // Check if date already exists
            $check_query = "SELECT id FROM to_closed_days WHERE tenant_id = ? AND date = ?";
            $stmt = mysqli_prepare($dbc, $check_query);
            mysqli_stmt_bind_param($stmt, 'is', $tenant_id, $date);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $duplicate_dates[] = $date;
                continue;
            }
            
            // Insert new closed day
            $insert_query = "INSERT INTO to_closed_days (tenant_id, date, type, title, notes, allow_shifts, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $insert_query);
            mysqli_stmt_bind_param($stmt, 'isssiii', $tenant_id, $date, $type, $title, $notes, $allow_shifts, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            } else {
                $failed_dates[] = $date . ' (database error)';
            }
        }
        
        mysqli_commit($dbc);
        
        $message = "Successfully created $success_count " . ($type === 'holiday' ? 'holidays' : 'closed days');
        if (!empty($duplicate_dates)) {
            $message .= ". Skipped " . count($duplicate_dates) . " duplicate dates";
        }
        if (!empty($failed_dates)) {
            $message .= ". Failed to create " . count($failed_dates) . " dates";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'created_count' => $success_count,
            'duplicate_count' => count($duplicate_dates),
            'failed_count' => count($failed_dates),
            'duplicate_dates' => $duplicate_dates,
            'failed_dates' => $failed_dates
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        echo json_encode(['success' => false, 'message' => 'Database error occurred during bulk creation']);
    }
}

function handleUpdateClosedDay($dbc, $tenant_id, $user_id, $input) {
    if (!isset($input['id']) || !isset($input['title']) || !isset($input['type'])) {
        echo json_encode(['success' => false, 'message' => 'ID, title, and type are required']);
        return;
    }
    
    $id = intval($input['id']);
    $title = trim($input['title']);
    $type = $input['type'];
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    $allow_shifts = isset($input['allow_shifts']) ? (bool)$input['allow_shifts'] : false;
    
    // Validate type
    if (!in_array($type, ['closed', 'holiday'])) {
        echo json_encode(['success' => false, 'message' => 'Type must be either "closed" or "holiday"']);
        return;
    }
    
    // Verify the closed day belongs to this tenant
    $verify_query = "SELECT id FROM to_closed_days WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $verify_query);
    mysqli_stmt_bind_param($stmt, 'ii', $id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $verify_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Closed day not found or access denied']);
        return;
    }
    
    // Update closed day
    $update_query = "UPDATE to_closed_days SET type = ?, title = ?, notes = ?, allow_shifts = ? WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, 'sssiii', $type, $title, $notes, $allow_shifts, $id, $tenant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update ' . $type]);
    }
}

function handleDeleteClosedDay($dbc, $tenant_id, $input) {
    if (!isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        return;
    }
    
    $id = intval($input['id']);
    
    // Verify the closed day belongs to this tenant
    $verify_query = "SELECT type FROM to_closed_days WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $verify_query);
    mysqli_stmt_bind_param($stmt, 'ii', $id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $verify_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Closed day not found or access denied']);
        return;
    }
    
    $row = mysqli_fetch_assoc($verify_result);
    $type = $row['type'];
    
    // Delete closed day
    $delete_query = "DELETE FROM to_closed_days WHERE id = ? AND tenant_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_query);
    mysqli_stmt_bind_param($stmt, 'ii', $id, $tenant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete ' . $type]);
    }
}
?>