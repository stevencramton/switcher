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
$name = trim($_POST['name']);
$description = trim($_POST['description'] ?? '');
$color = $_POST['color'] ?? '#007bff';
$sort_order = (int)($_POST['sort_order'] ?? 0);
$active = isset($_POST['active']) ? 1 : 0;

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Department name is required']);
    exit();
}

if (strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Department name must be at least 2 characters long']);
    exit();
}

if (strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Department name must be 100 characters or less']);
    exit();
}

try {
    $check_query = "SELECT id, name FROM to_departments WHERE id = ? AND tenant_id = ?";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $department_id, $tenant_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Department not found or access denied']);
        exit();
    }
    mysqli_stmt_close($check_stmt);
    
    $name_check = "SELECT id FROM to_departments WHERE tenant_id = ? AND name = ? AND id != ? AND active = 1";
    $name_stmt = mysqli_prepare($dbc, $name_check);
    mysqli_stmt_bind_param($name_stmt, 'isi', $tenant_id, $name, $department_id);
    mysqli_stmt_execute($name_stmt);
    $name_result = mysqli_stmt_get_result($name_stmt);
    
    if (mysqli_num_rows($name_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Department name already exists in this organization']);
        exit();
    }
    mysqli_stmt_close($name_stmt);
    
    $update_query = "
        UPDATE to_departments 
        SET name = ?, description = ?, color = ?, sort_order = ?, active = ?, updated_at = NOW()
        WHERE id = ? AND tenant_id = ?
    ";
    
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'sssiiii', $name, $description, $color, $sort_order, $active, $department_id, $tenant_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        mysqli_stmt_close($update_stmt);
        
        echo json_encode([
            'success' => true,
            'message' => 'Department updated successfully',
            'department_id' => $department_id
        ]);
    } else {
        throw new Exception('Failed to update department: ' . mysqli_error($dbc));
    }
    
} catch (Exception $e) {
    error_log("Update department error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>