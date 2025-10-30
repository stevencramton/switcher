<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_tenant')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['tenant_id']) || !is_numeric($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid tenant ID']);
    exit();
}

$tenant_id = (int)$_GET['tenant_id'];

try {
    $counts_query = "
        SELECT 
            (SELECT COUNT(DISTINCT ut.user_id) 
             FROM to_user_tenants ut 
             WHERE ut.tenant_id = ? AND ut.active = 1) as user_count,
            
            (SELECT COUNT(DISTINCT d.id) 
             FROM to_departments d 
             WHERE d.tenant_id = ? AND d.active = 1) as department_count,
            
            (SELECT COUNT(DISTINCT s.id) 
             FROM to_shifts s 
             WHERE s.tenant_id = ? AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as shift_count
    ";

    $stmt = mysqli_prepare($dbc, $counts_query);
    mysqli_stmt_bind_param($stmt, 'iii', $tenant_id, $tenant_id, $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $counts = mysqli_fetch_assoc($result);

    if (!$counts) {
        echo json_encode(['success' => false, 'message' => 'Tenant not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'counts' => [
            'user_count' => (int)$counts['user_count'],
            'department_count' => (int)$counts['department_count'], 
            'shift_count' => (int)$counts['shift_count']
        ],
        'tenant_id' => $tenant_id
    ]);

} catch (Exception $e) {
    error_log("Error in get_tenant_counts.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>