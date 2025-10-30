<?php
session_start();
require_once '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('timesown_admin')) {
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

try {
    $preferences_query = "
        SELECT aup.preferred_tenant_id, aup.settings, t.name as tenant_name
        FROM to_admin_user_preferences aup
        LEFT JOIN to_tenants t ON aup.preferred_tenant_id = t.id
        WHERE aup.user_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $preferences_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $preferences = [
                'preferred_tenant_id' => $row['preferred_tenant_id'],
                'tenant_name' => $row['tenant_name'],
                'settings' => $row['settings'] ? json_decode($row['settings'], true) : []
            ];
        } else {
            $preferences = [
                'preferred_tenant_id' => null,
                'tenant_name' => null,
                'settings' => []
            ];
        }
    } else {
        $preferences = [
            'preferred_tenant_id' => null,
            'tenant_name' => null,
            'settings' => []
        ];
    }
    
    $tenants_query = "SELECT id, name, logo FROM to_tenants WHERE active = 1 ORDER BY name";
    $stmt = mysqli_prepare($dbc, $tenants_query);
    mysqli_stmt_execute($stmt);
    $tenants_result = mysqli_stmt_get_result($stmt);
    $available_tenants = mysqli_fetch_all($tenants_result, MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'preferences' => $preferences,
        'available_tenants' => $available_tenants
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>