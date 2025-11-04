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

try {
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
        'stats' => $system_stats
    ]);

} catch (Exception $e) {
    error_log("Error in get_system_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>