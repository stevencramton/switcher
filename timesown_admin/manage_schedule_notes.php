<?php
session_start();
date_default_timezone_set('America/New_York');

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$user_id = $_SESSION['switch_id'];

$user_query = "SELECT id FROM users WHERE switch_id = ? AND account_delete = 0";
$stmt = mysqli_prepare($dbc, $user_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

if (!$user_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$actual_user_id = $user_data['id'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input && $method === 'POST') {
    $input = $_POST;
}

function validateTenantAccess($dbc, $actual_user_id, $tenant_id) {
    if (checkRole('admin_developer')) {
        return true;
    }
    
    $access_query = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $actual_user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

try {
    switch ($method) {
        case 'GET':
         	$tenant_id = intval($_GET['tenant_id'] ?? 0);
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? $start_date;
            
            if (!$tenant_id || !$start_date) {
                throw new Exception('Missing required parameters');
            }
            
            if (!validateTenantAccess($dbc, $actual_user_id, $tenant_id)) {
                throw new Exception('Access denied to this organization');
            }
            
            $query = "SELECT id, schedule_date, public_note, admin_note, position, is_active, 
                             created_by, created_at, updated_at 
                      FROM to_daily_schedule_notes 
                      WHERE tenant_id = ? AND schedule_date BETWEEN ? AND ? AND is_active = 1
                      ORDER BY schedule_date";
            
            $stmt = mysqli_prepare($dbc, $query);
            mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $notes = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $notes[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'notes' => $notes
            ]);
            break;
            
        case 'POST':
          	$action = $input['action'] ?? 'create';
            $tenant_id = intval($input['tenant_id'] ?? 0);
            $schedule_date = $input['schedule_date'] ?? '';
            $public_note = trim($input['public_note'] ?? '');
            $admin_note = trim($input['admin_note'] ?? '');
            $position = $input['position'] ?? 'top';
            $note_id = intval($input['id'] ?? 0);
            
            if (!$tenant_id || !$schedule_date) {
                throw new Exception('Missing required parameters');
            }
            
            if (!validateTenantAccess($dbc, $actual_user_id, $tenant_id)) {
                throw new Exception('Access denied to this organization');
            }
            
          	if (!in_array($position, ['top', 'bottom'])) {
                $position = 'top';
            }
            
          	if (empty($public_note) && empty($admin_note)) {
                if ($note_id) {
                    $delete_query = "DELETE FROM to_daily_schedule_notes 
                                   WHERE id = ? AND tenant_id = ?";
                    $stmt = mysqli_prepare($dbc, $delete_query);
                    mysqli_stmt_bind_param($stmt, 'ii', $note_id, $tenant_id);
                    mysqli_stmt_execute($stmt);
                }
                echo json_encode(['success' => true, 'message' => 'Note deleted']);
                break;
            }
            
            if ($action === 'update' && $note_id) {
               	$update_query = "UPDATE to_daily_schedule_notes 
                               SET public_note = ?, admin_note = ?, position = ?, 
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ? AND tenant_id = ?";
                $stmt = mysqli_prepare($dbc, $update_query);
                
                $public_note_param = $public_note ?: null;
                $admin_note_param = $admin_note ?: null;
                
                mysqli_stmt_bind_param($stmt, 'sssii', 
                    $public_note_param, $admin_note_param, $position, $note_id, $tenant_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to update note');
                }
                
                echo json_encode(['success' => true, 'message' => 'Note updated successfully']);
                
            } else {
              	$upsert_query = "INSERT INTO to_daily_schedule_notes 
                               (tenant_id, schedule_date, public_note, admin_note, position, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE 
                               public_note = VALUES(public_note), 
                               admin_note = VALUES(admin_note),
                               position = VALUES(position),
                               updated_at = CURRENT_TIMESTAMP";
                
                $stmt = mysqli_prepare($dbc, $upsert_query);
                
                $public_note_param = $public_note ?: null;
                $admin_note_param = $admin_note ?: null;
                
                mysqli_stmt_bind_param($stmt, 'issssi', 
                    $tenant_id, $schedule_date, $public_note_param, $admin_note_param, 
                    $position, $actual_user_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to save note');
                }
                
                echo json_encode(['success' => true, 'message' => 'Note saved successfully']);
            }
            break;
            
        case 'DELETE':
          	$note_id = intval($input['id'] ?? 0);
            $tenant_id = intval($input['tenant_id'] ?? 0);
            
            if (!$note_id || !$tenant_id) {
                throw new Exception('Missing required parameters');
            }
            
            if (!validateTenantAccess($dbc, $actual_user_id, $tenant_id)) {
                throw new Exception('Access denied to this organization');
            }
            
            $delete_query = "DELETE FROM to_daily_schedule_notes 
                           WHERE id = ? AND tenant_id = ?";
            $stmt = mysqli_prepare($dbc, $delete_query);
            mysqli_stmt_bind_param($stmt, 'ii', $note_id, $tenant_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to delete note');
            }
            
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($dbc);
?>