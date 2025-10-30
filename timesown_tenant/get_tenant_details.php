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

if (!isset($_SESSION['switch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID required']);
    exit();
}

try {
    include '../../mysqli_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$tenant_id = (int)$_GET['tenant_id'];
$tenant_query = "SELECT *,
                        UNIX_TIMESTAMP(updated_at) * 1000 as logo_updated_at
                 FROM to_tenants 
                 WHERE id = ?";

$stmt = mysqli_prepare($dbc, $tenant_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Tenant not found']);
    exit();
}

$tenant = mysqli_fetch_assoc($result);

$settings = [];
if (!empty($tenant['settings'])) {
    $decoded = json_decode($tenant['settings'], true);
    if ($decoded) {
        $settings = $decoded;
    }
}

$logo_with_timestamp = null;
if ($tenant['logo'] && !empty($tenant['logo'])) {
    $cache_buster = "t=" . $tenant['logo_updated_at'] . "&v=" . $tenant['id'];
    $separator = strpos($tenant['logo'], '?') !== false ? '&' : '?';
    $logo_with_timestamp = $tenant['logo'] . $separator . $cache_buster;
}

echo json_encode([
    'success' => true,
    'data' => [
        'id' => $tenant['id'],
        'name' => $tenant['name'],
        'slug' => $tenant['slug'],
        'logo' => $tenant['logo'],
        'logo_with_timestamp' => $logo_with_timestamp,
        'logo_updated_at' => $tenant['logo_updated_at'],
        'active' => $tenant['active'],
        'settings' => $settings,
        'created_at' => $tenant['created_at'],
        'updated_at' => $tenant['updated_at']
    ]
]);
?>