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

$banned_by_user_id = intval($_SESSION['id']);

if (!$banned_by_user_id) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Session error: User ID not found']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['userId']);
$duration_minutes = intval($data['duration']) ?: 5;
$reason = mysqli_real_escape_string($dbc, $data['reason'] ?? 'No reason provided');

$verify_query = "SELECT id FROM users WHERE id = ?";
$verify_stmt = mysqli_prepare($dbc, $verify_query);
mysqli_stmt_bind_param($verify_stmt, 'i', $user_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) === 0) {
    mysqli_stmt_close($verify_stmt);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

mysqli_stmt_close($verify_stmt);
date_default_timezone_set('America/New_York');

$banned_at = date('Y-m-d H:i:s');
$ban_until = date('Y-m-d H:i:s', strtotime("+{$duration_minutes} minutes"));

$check_query = "SELECT id FROM luckio_bans 
                WHERE user_id = ? 
                AND ban_until > NOW() 
                AND unbanned_at IS NULL 
                LIMIT 1";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
	$existing = mysqli_fetch_assoc($check_result);
    $update_query = "UPDATE luckio_bans 
                     SET ban_until = ?, reason = ?, banned_by_user_id = ?, banned_at = ? 
                     WHERE id = ?";
    $update_stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($update_stmt, 'ssisi', $ban_until, $reason, $banned_by_user_id, $banned_at, $existing['id']);
    $success = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
} else {
	$insert_query = "INSERT INTO luckio_bans (user_id, banned_by_user_id, reason, banned_at, ban_until) 
                     VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($dbc, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'iisss', $user_id, $banned_by_user_id, $reason, $banned_at, $ban_until);
    $success = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);

}

mysqli_stmt_close($check_stmt);

if ($success) {
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
        'ban_until' => $ban_until,
        'banned_at' => $banned_at,
        'userName' => $user_name,
        'duration' => $duration_minutes
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($dbc)
    ]);
}

mysqli_close($dbc);
?>