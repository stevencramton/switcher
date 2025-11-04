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

if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit();
}

$department_id = (int)$_GET['department_id'];

try {
    $query = "
        SELECT d.id, d.name, d.description, d.color, d.sort_order, d.active, 
               d.created_at, d.updated_at, d.tenant_id, t.name as tenant_name
        FROM to_departments d
        JOIN to_tenants t ON d.tenant_id = t.id
        WHERE d.id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $department_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        
        $role_count_query = "SELECT COUNT(*) as role_count FROM to_job_roles WHERE department_id = ? AND active = 1";
        $count_stmt = mysqli_prepare($dbc, $role_count_query);
        mysqli_stmt_bind_param($count_stmt, 'i', $department_id);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $count_data = mysqli_fetch_assoc($count_result);
        mysqli_stmt_close($count_stmt);
        
        $user_count_query = "
            SELECT COUNT(DISTINCT udr.user_id) as user_count 
            FROM to_user_department_roles udr 
            WHERE udr.department_id = ? AND udr.active = 1
        ";
        $user_stmt = mysqli_prepare($dbc, $user_count_query);
        mysqli_stmt_bind_param($user_stmt, 'i', $department_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_data = mysqli_fetch_assoc($user_result);
        mysqli_stmt_close($user_stmt);
        
        $row['role_count'] = (int)$count_data['role_count'];
        $row['user_count'] = (int)$user_data['user_count'];
        
        echo json_encode([
            'success' => true,
            'department' => $row
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found']);
    }
    
} catch (Exception $e) {
    error_log("Get department details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>