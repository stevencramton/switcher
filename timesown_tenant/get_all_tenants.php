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

$switch_id = $_SESSION['switch_id'];

try {
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

    $tenants_query = "
        SELECT t.*,
               COUNT(DISTINCT ut.user_id) as user_count,
               COUNT(DISTINCT d.id) as department_count,
               COUNT(DISTINCT s.id) as shift_count
        FROM to_tenants t
        LEFT JOIN to_user_tenants ut ON t.id = ut.tenant_id AND ut.active = 1
        LEFT JOIN to_departments d ON t.id = d.tenant_id AND d.active = 1
        LEFT JOIN to_shifts s ON t.id = s.tenant_id AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY t.id
        ORDER BY t.name
    ";

    $stmt = mysqli_prepare($dbc, $tenants_query);
    mysqli_stmt_execute($stmt);
    $tenants_result = mysqli_stmt_get_result($stmt);
    $tenants = mysqli_fetch_all($tenants_result, MYSQLI_ASSOC);

    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM to_tenants WHERE active = 1) as active_tenants,
            (SELECT COUNT(*) FROM to_user_tenants WHERE active = 1) as total_user_assignments,
            (SELECT COUNT(*) FROM to_shifts WHERE shift_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as shifts_this_week,
            (SELECT COUNT(*) FROM to_shifts WHERE assigned_user_id IS NULL AND shift_date >= NOW()) as open_shifts
    ";

    $stmt = mysqli_prepare($dbc, $stats_query);
    mysqli_stmt_execute($stmt);
    $stats_result = mysqli_stmt_get_result($stmt);
    $system_stats = mysqli_fetch_assoc($stats_result);

    echo json_encode([
        'success' => true,
        'tenants' => $tenants,
        'stats' => $system_stats,
        'count' => count($tenants)
    ]);

} catch (Exception $e) {
    error_log("Error in get_all_tenants.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>