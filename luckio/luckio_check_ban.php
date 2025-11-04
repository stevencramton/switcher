<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

$user_id = intval($_GET['userId'] ?? 0);

if (!$user_id) {
    echo json_encode(['banned' => false]);
    exit;
}

date_default_timezone_set('America/New_York');

mysqli_query($dbc, "SET time_zone = '-04:00'");

$debug_query = "SELECT NOW() as mysql_now, ban_until, reason, banned_at,
                CASE WHEN ban_until > NOW() THEN 'ACTIVE' ELSE 'EXPIRED' END as status
                FROM luckio_bans 
                WHERE user_id = ? 
                AND unbanned_at IS NULL 
                ORDER BY banned_at DESC 
                LIMIT 1";

$debug_stmt = mysqli_prepare($dbc, $debug_query);
mysqli_stmt_bind_param($debug_stmt, 'i', $user_id);
mysqli_stmt_execute($debug_stmt);
$debug_result = mysqli_stmt_get_result($debug_stmt);

if ($debug_row = mysqli_fetch_assoc($debug_result)) {
    
}

mysqli_stmt_close($debug_stmt);

$query = "SELECT ban_until, reason, banned_at 
          FROM luckio_bans 
          WHERE user_id = ? 
          AND ban_until > NOW() 
          AND unbanned_at IS NULL 
          ORDER BY banned_at DESC 
          LIMIT 1";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'banned' => true,
        'ban_until' => $row['ban_until'],
        'reason' => $row['reason'],
        'banned_at' => $row['banned_at']
    ]);
} else {
    echo json_encode(['banned' => false]);
}

mysqli_stmt_close($stmt);
mysqli_close($dbc);
?>