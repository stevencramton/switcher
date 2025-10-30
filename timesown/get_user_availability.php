<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('timesown_user')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

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
$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
$time_prefs_query = "
    SELECT day_of_week, time_slot, preference_level
    FROM to_user_time_preferences
    WHERE user_id = ? AND tenant_id = ?
    ORDER BY day_of_week, time_slot
";

$stmt = mysqli_prepare($dbc, $time_prefs_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$prefs_result = mysqli_stmt_get_result($stmt);
$time_preferences = [];

while ($row = mysqli_fetch_assoc($prefs_result)) {
    $time_preferences[] = [
        'day_of_week' => $row['day_of_week'],
        'time_slot' => substr($row['time_slot'], 0, 5),
        'preference_level' => $row['preference_level']
    ];
}

$availability_query = "
    SELECT day_of_week, start_time, end_time, preference_level, notes
    FROM to_user_availability
    WHERE user_id = ? AND tenant_id = ?
    AND effective_date <= CURDATE()
    AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY day_of_week, start_time
";

$stmt = mysqli_prepare($dbc, $availability_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$availability = [];
$notes = '';

while ($row = mysqli_fetch_assoc($result)) {
    $availability[] = [
        'day_of_week' => $row['day_of_week'],
        'start_time' => substr($row['start_time'], 0, 5),
        'end_time' => substr($row['end_time'], 0, 5),
        'preference_level' => $row['preference_level'],
        'notes' => $row['notes']
    ];
    
    if (!empty($row['notes'])) {
        $notes = $row['notes'];
    }
}

$stats_query = "
    SELECT 
        COUNT(*) as shifts_this_month,
        SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as hours_this_month
    FROM to_shifts 
    WHERE assigned_user_id = ? 
    AND tenant_id = ? 
    AND shift_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND status IN ('scheduled', 'completed')
";

$stmt = mysqli_prepare($dbc, $stats_query);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $tenant_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'time_preferences' => $time_preferences,
    'availability' => $availability,
    'notes' => $notes,
    'stats' => [
        'shifts_this_month' => $stats['shifts_this_month'] ?: 0,
        'hours_this_month' => $stats['hours_this_month'] ?: 0
    ],
    'user_id' => $user_id,
    'tenant_id' => $tenant_id,
    'has_detailed_prefs' => !empty($time_preferences)
]);
?>