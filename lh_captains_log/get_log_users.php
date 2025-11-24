<?php
/**
 * Captain's Log - Get Users for Filter
 * 
 * AJAX endpoint to fetch users who have log entries
 * Place in: ajax/lh_captains_log/get_log_users.php
 */
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

// Check role
if (!checkRole('lighthouse_captain')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Get users who have entries in the log
$query = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, u.user as username
          FROM users u
          INNER JOIN lh_captains_log cl ON u.id = cl.user_id
          GROUP BY u.id, u.first_name, u.last_name, u.user
          ORDER BY u.first_name, u.last_name";

$result = mysqli_query($dbc, $query);

$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'username' => $row['username']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $data
]);
?>