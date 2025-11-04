<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('timesown_tenant')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_POST['department_id']) || !isset($_POST['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Department ID and Tenant ID are required']);
    exit();
}

$department_id = (int)$_POST['department_id'];
$tenant_id = (int)$_POST['tenant_id'];

try {
 	mysqli_autocommit($dbc, false);
    
	$check_query = "SELECT id, name FROM to_departments WHERE id = ? AND tenant_id = ?";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $department_id, $tenant_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        mysqli_rollback($dbc);
        echo json_encode(['success' => false, 'message' => 'Department not found or access denied']);
        exit();
    }
    
    $dept_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
 	$shift_check = "SELECT COUNT(*) as shift_count FROM to_shifts WHERE department_id = ?";
    $shift_stmt = mysqli_prepare($dbc, $shift_check);
    mysqli_stmt_bind_param($shift_stmt, 'i', $department_id);
    mysqli_stmt_execute($shift_stmt);
    $shift_result = mysqli_stmt_get_result($shift_stmt);
    $shift_data = mysqli_fetch_assoc($shift_result);
    mysqli_stmt_close($shift_stmt);
    
 	if ($shift_data['shift_count'] > 0) {
    	$user_assign_query = "UPDATE to_user_department_roles SET active = 0 WHERE department_id = ?";
        $user_stmt = mysqli_prepare($dbc, $user_assign_query);
        mysqli_stmt_bind_param($user_stmt, 'i', $department_id);
        mysqli_stmt_execute($user_stmt);
        mysqli_stmt_close($user_stmt);
        
      	$roles_query = "UPDATE to_job_roles SET active = 0 WHERE department_id = ?";
        $roles_stmt = mysqli_prepare($dbc, $roles_query);
        mysqli_stmt_bind_param($roles_stmt, 'i', $department_id);
        mysqli_stmt_execute($roles_stmt);
        mysqli_stmt_close($roles_stmt);
        
      	$dept_query = "UPDATE to_departments SET active = 0, updated_at = NOW() WHERE id = ?";
        $dept_stmt = mysqli_prepare($dbc, $dept_query);
        mysqli_stmt_bind_param($dept_stmt, 'i', $department_id);
        mysqli_stmt_execute($dept_stmt);
        mysqli_stmt_close($dept_stmt);
        
        $message = 'Department "' . $dept_data['name'] . '" has been deactivated (soft deleted due to existing schedule data)';
        
    } else {
      	$user_assign_query = "DELETE FROM to_user_department_roles WHERE department_id = ?";
        $user_stmt = mysqli_prepare($dbc, $user_assign_query);
        mysqli_stmt_bind_param($user_stmt, 'i', $department_id);
        mysqli_stmt_execute($user_stmt);
        mysqli_stmt_close($user_stmt);
        
     	$roles_query = "DELETE FROM to_job_roles WHERE department_id = ?";
        $roles_stmt = mysqli_prepare($dbc, $roles_query);
        mysqli_stmt_bind_param($roles_stmt, 'i', $department_id);
        mysqli_stmt_execute($roles_stmt);
        mysqli_stmt_close($roles_stmt);
        
     	$dept_query = "DELETE FROM to_departments WHERE id = ?";
        $dept_stmt = mysqli_prepare($dbc, $dept_query);
        mysqli_stmt_bind_param($dept_stmt, 'i', $department_id);
        mysqli_stmt_execute($dept_stmt);
        mysqli_stmt_close($dept_stmt);
        
        $message = 'Department "' . $dept_data['name'] . '" has been permanently deleted';
    }
    
 	mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'soft_delete' => $shift_data['shift_count'] > 0
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    error_log("Delete department error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>