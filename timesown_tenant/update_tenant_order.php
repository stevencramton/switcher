<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!checkRole('timesown_tenant')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['tenant_ids']) || !is_array($data['tenant_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit();
}

$tenant_ids = $data['tenant_ids'];
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

try {
 	mysqli_begin_transaction($dbc);
    
	$update_query = "UPDATE to_tenants SET display_order = ? WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $update_query);
    
    foreach ($tenant_ids as $index => $tenant_id) {
        $display_order = $index + 1;
        mysqli_stmt_bind_param($stmt, 'ii', $display_order, $tenant_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update tenant order');
        }
    }
    
	if (!empty($tenant_ids)) {
        $audit_query = "INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at) 
                        VALUES (?, ?, 'UPDATE_ORDER', 'to_tenants', 0, ?, ?, ?, NOW())";
        
        $audit_stmt = mysqli_prepare($dbc, $audit_query);
        if ($audit_stmt) {
            $new_values = json_encode(['tenant_order' => $tenant_ids]);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $first_tenant_id = $tenant_ids[0];
            
            mysqli_stmt_bind_param($audit_stmt, 'iisss', $first_tenant_id, $user_id, $new_values, $ip_address, $user_agent);
            mysqli_stmt_execute($audit_stmt);
        }
    }
    
	mysqli_commit($dbc);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Tenant order updated successfully',
        'updated_count' => count($tenant_ids)
    ]);
    
} catch (Exception $e) {
	mysqli_rollback($dbc);
    
	echo json_encode([
        'success' => false, 
        'message' => 'Failed to update tenant order: ' . $e->getMessage()
    ]);
}
?>