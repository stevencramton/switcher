<?php
session_start();
require_once '../../mysqli_connect.php';
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

if (!isset($_POST['name']) || empty($_POST['name'])) {
    echo json_encode(['success' => false, 'message' => 'Organization name is required']);
    exit();
}

if (!isset($_POST['slug']) || empty($_POST['slug'])) {
    echo json_encode(['success' => false, 'message' => 'URL slug is required']);
    exit();
}

$name = trim($_POST['name']);
$slug = trim($_POST['slug']);
$active = isset($_POST['active']) ? (int)$_POST['active'] : 1;

if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
    echo json_encode(['success' => false, 'message' => 'URL slug can only contain lowercase letters, numbers, and hyphens']);
    exit();
}

$slug_check = "SELECT id FROM to_tenants WHERE slug = ?";
$stmt = mysqli_prepare($dbc, $slug_check);
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['success' => false, 'message' => 'URL slug already exists']);
    exit();
}

$logo_path = null;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../img/tenant_logos/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_info = pathinfo($_FILES['logo']['name']);
    $file_extension = strtolower($file_info['extension']);
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit();
    }
    
    if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
        exit();
    }
    
    $filename = $slug . '_logo.' . $file_extension;
    $full_path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $full_path)) {
        $logo_path = 'img/tenant_logos/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload logo file']);
        exit();
    }
}

$settings = [];
if (isset($_POST['settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $settings[$key] = (bool)$value;
    }
}
$settings_json = json_encode($settings);

try {
    mysqli_autocommit($dbc, false);
    
    $create_query = "
        INSERT INTO to_tenants (name, slug, logo, settings, active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ";
    
    $stmt = mysqli_prepare($dbc, $create_query);
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $slug, $logo_path, $settings_json, $active);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error creating tenant: ' . mysqli_error($dbc));
    }
    
    $new_tenant_id = mysqli_insert_id($dbc);
    
    mysqli_commit($dbc);
    mysqli_autocommit($dbc, true);
    
    $audit_query = "
        INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
        VALUES (?, ?, 'CREATE', 'to_tenants', ?, ?, ?, ?, NOW())
    ";
    
    $new_values = json_encode([
        'name' => $name,
        'slug' => $slug,
        'logo' => $logo_path,
        'settings' => $settings,
        'active' => $active
    ]);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($stmt, 'iissss', 
        $new_tenant_id, $user_id, $new_tenant_id, 
        $new_values, $ip_address, $user_agent
    );
    mysqli_stmt_execute($stmt);
    
    $tenant_query = "
        SELECT t.*,
               COUNT(DISTINCT ut.user_id) as user_count,
               COUNT(DISTINCT d.id) as department_count,
               COUNT(DISTINCT s.id) as shift_count
        FROM to_tenants t
        LEFT JOIN to_user_tenants ut ON t.id = ut.tenant_id AND ut.active = 1
        LEFT JOIN to_departments d ON t.id = d.tenant_id AND d.active = 1
        LEFT JOIN to_shifts s ON t.id = s.tenant_id AND s.shift_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE t.id = ?
        GROUP BY t.id
    ";
    
    $stmt = mysqli_prepare($dbc, $tenant_query);
    mysqli_stmt_bind_param($stmt, 'i', $new_tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tenant_data = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Organization created successfully',
        'tenant' => $tenant_data,
        'tenant_id' => $new_tenant_id
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    mysqli_autocommit($dbc, true);
    
    if ($logo_path && file_exists('../../' . $logo_path)) {
        unlink('../../' . $logo_path);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>