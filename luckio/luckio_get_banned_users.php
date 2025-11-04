<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!checkRole('luckio_admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

mysqli_query($dbc, "SET time_zone = '-04:00'");
date_default_timezone_set('America/New_York');

$query = "SELECT 
            lb.user_id,
            lb.ban_until,
            lb.reason,
            lb.banned_at,
            u.first_name,
            u.last_name
          FROM luckio_bans lb
          JOIN users u ON lb.user_id = u.id
          WHERE lb.ban_until > NOW()
          AND lb.unbanned_at IS NULL
          ORDER BY lb.banned_at DESC";

$result = mysqli_query($dbc, $query);

$banned_users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $banned_users[] = [
            'userId' => (int)$row['user_id'],
            'userName' => $row['first_name'] . ' ' . $row['last_name'],
            'banUntil' => $row['ban_until'],
            'reason' => $row['reason'],
            'bannedAt' => $row['banned_at'],
            'isAdmin' => false,
            'isBanned' => true
        ];
    }
}

echo json_encode([
    'success' => true,
    'banned_users' => $banned_users
]);

mysqli_close($dbc);
?>