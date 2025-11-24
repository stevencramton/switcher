<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['status' => 'error', 'message' => 'Not authenticated']));
}

$is_admin = checkRole('lighthouse_keeper');

// Only admins can see the list of keepers
if (!$is_admin) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Access denied']));
}

// Get all active admin users who can be assigned as keepers
$query = "SELECT u.id, u.first_name, u.last_name 
          FROM users u 
          INNER JOIN roles_dev r ON u.role_id = r.role_id 
          WHERE r.lighthouse_keeper = 1 AND u.account_delete = 0 
          ORDER BY u.first_name, u.last_name";
$result = mysqli_query($dbc, $query);

if ($result) {
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch lighthouse keepers'
    ]);
}
?>