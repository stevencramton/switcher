<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Basic PHP execution working\n";

session_start();
echo "Step 2: Session started\n";

header('Content-Type: application/json');
echo "Step 3: Header set (this shouldn't show in browser)\n";

try {
    include '../../mysqli_connect.php';
    echo "Step 4: mysqli_connect.php included successfully\n";
} catch (Exception $e) {
    echo "Step 4 FAILED: mysqli_connect.php error: " . $e->getMessage() . "\n";
    exit();
}

try {
    include '../../templates/functions.php';
    echo "Step 5: functions.php included successfully\n";
} catch (Exception $e) {
    echo "Step 5 FAILED: functions.php error: " . $e->getMessage() . "\n";
    exit();
}

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['error' => 'No session switch_id found', 'session_data' => $_SESSION]);
    exit();
}

echo "Step 6: Session switch_id found: " . $_SESSION['switch_id'] . "\n";

if (!function_exists('checkRole')) {
    echo json_encode(['error' => 'checkRole function not found']);
    exit();
}

echo "Step 7: checkRole function exists\n";

if (!checkRole('timesown_admin')) {
    echo json_encode(['error' => 'User does not have timesown_admin role']);
    exit();
}

echo "Step 8: User has timesown_admin role\n";

if (!$dbc) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

echo "Step 9: Database connection active\n";

$table_check = "SHOW TABLES LIKE 'to_schedule_templates'";
$result = mysqli_query($dbc, $table_check);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => true,
        'message' => 'All systems working - templates table does not exist (normal)',
        'templates' => [],
        'count' => 0
    ]);
} else {
    echo json_encode([
        'success' => true, 
        'message' => 'All systems working - templates table exists',
        'templates' => [],
        'count' => 0
    ]);
}
?>