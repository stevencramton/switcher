<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

mysqli_query($dbc, "SET time_zone = '-04:00'");

if (!checkRole('luckio_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$unbanned_by_user_id = intval($_SESSION['id']);

if (!$unbanned_by_user_id) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Session error: User ID not found']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['userId']);

date_default_timezone_set('America/New_York');

$unbanned_at = date('Y-m-d H:i:s');

$query = "UPDATE luckio_bans 
          SET unbanned_at = ?, unbanned_by_user_id = ? 
          WHERE user_id = ? 
          AND ban_until > NOW() 
          AND unbanned_at IS NULL 
          LIMIT 1";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'sii', $unbanned_at, $unbanned_by_user_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
    	$name_query = "SELECT first_name, last_name FROM users WHERE id = ?";
        $name_stmt = mysqli_prepare($dbc, $name_query);
        mysqli_stmt_bind_param($name_stmt, 'i', $user_id);
        mysqli_stmt_execute($name_stmt);
        mysqli_stmt_bind_result($name_stmt, $first_name, $last_name);
        mysqli_stmt_fetch($name_stmt);
        $user_name = $first_name . ' ' . $last_name;
        mysqli_stmt_close($name_stmt);
        
      	echo json_encode([
            'success' => true,
            'userName' => $user_name,
            'unbanned_at' => $unbanned_at
        ]);
    } else {
     	echo json_encode([
            'success' => false,
            'message' => 'No active ban found for this user'
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($dbc);
?>