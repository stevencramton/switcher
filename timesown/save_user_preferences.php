<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!checkRole('timesown_user')){
    header("Location:../../index.php?msg1");
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

if (!isset($_POST['preferred_tenant_id']) || empty($_POST['preferred_tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Preferred organization is required']);
    exit();
}

$preferred_tenant_id = (int)$_POST['preferred_tenant_id'];

$tenant_access_query = "
    SELECT t.id, t.name 
    FROM to_tenants t 
    JOIN to_user_tenants ut ON t.id = ut.tenant_id 
    WHERE t.id = ? AND ut.user_id = ? AND ut.active = 1 AND t.active = 1
";

$stmt = mysqli_prepare($dbc, $tenant_access_query);
mysqli_stmt_bind_param($stmt, 'ii', $preferred_tenant_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid organization - you do not have access to this organization']);
    exit();
}

$tenant_data = mysqli_fetch_assoc($result);

try {
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS `to_user_preferences` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` int UNSIGNED NOT NULL,
            `preferred_tenant_id` int UNSIGNED NULL,
            `settings` json DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_preferences_unique` (`user_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`preferred_tenant_id`) REFERENCES `to_tenants` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ";
    
    mysqli_query($dbc, $create_table_query);
    
    $upsert_query = "
        INSERT INTO to_user_preferences (user_id, preferred_tenant_id) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
        preferred_tenant_id = VALUES(preferred_tenant_id),
        updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = mysqli_prepare($dbc, $upsert_query);
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $preferred_tenant_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Preferred organization updated successfully',
            'tenant_name' => $tenant_data['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save preference']);
    }
    
} catch (Exception $e) {
 	error_log('Save User Preferences - Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . ' | User ID: ' . $user_id . ' | Tenant ID: ' . $preferred_tenant_id);
    
 	http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to save user preferences'
    ]);
}
?>