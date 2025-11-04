<?php
// ajax/timesown/get_schedule_notes.php
session_start();
date_default_timezone_set('America/New_York');

include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Check authentication
if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['switch_id'];

// Get the actual user ID from the users table
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

try {
    // Get parameters
    $tenant_id = intval($_GET['tenant_id'] ?? 0);
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? $start_date;
    
    if (!$tenant_id || !$start_date) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate user has access to this tenant
    $access_query = "SELECT 1 FROM to_user_tenants WHERE user_id = ? AND tenant_id = ? AND active = 1";
    $stmt = mysqli_prepare($dbc, $access_query);
    mysqli_stmt_bind_param($stmt, 'ii', $actual_user_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Access denied to this organization');
    }
    
    // Get public notes only (no admin notes for regular users)
    $query = "SELECT id, schedule_date, public_note, position, created_at 
              FROM to_daily_schedule_notes 
              WHERE tenant_id = ? AND schedule_date BETWEEN ? AND ? 
                    AND is_active = 1 AND public_note IS NOT NULL AND public_note != ''
              ORDER BY schedule_date";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notes[] = [
            'id' => $row['id'],
            'schedule_date' => $row['schedule_date'],
            'public_note' => $row['public_note'],
            'position' => $row['position'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notes' => $notes
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($dbc);
?>