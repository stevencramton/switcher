<?php
session_start(); 
include '../../mysqli_connect.php';
include '../../templates/functions.php';

while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    ob_clean();
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

if (!isset($_GET['conversationId'])) {
    ob_clean();
    echo json_encode([
        'error' => 'Missing conversation ID',
        'debug' => 'Expected: ?conversationId=123',
        'received_params' => $_GET
    ]);
    exit();
}

$conversation_id = intval($_GET['conversationId']);

if ($conversation_id <= 0) {
    ob_clean();
    echo json_encode([
        'error' => 'Invalid conversation ID',
        'debug' => 'conversationId must be a positive integer',
        'received' => $_GET['conversationId']
    ]);
    exit();
}

$verifyQuery = "
    SELECT 1 FROM conversation_participants 
    WHERE conversation_id = ? 
    AND user_id = ? 
    AND left_at IS NULL
";

$stmt = $dbc->prepare($verifyQuery);

if (!$stmt) {
    ob_clean();
    echo json_encode([
        'error' => 'Database prepare error',
        'debug' => $dbc->error
    ]);
    exit();
}

$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$verifyResult = $stmt->get_result();

if ($verifyResult->num_rows === 0) {
    ob_clean();
    echo json_encode([
        'error' => 'Access denied',
        'debug' => 'User not a participant in this conversation'
    ]);
    exit();
}

$query = "
    SELECT 
        m.id,
        m.sender_id, 
        m.message, 
        m.timestamp,
        u.first_name AS sender_first_name, 
        u.last_name AS sender_last_name,
        u.profile_pic AS sender_profile_pic
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ?
    AND m.deleted_at IS NULL
    ORDER BY m.timestamp ASC
";

$stmt = $dbc->prepare($query);

if (!$stmt) {
    ob_clean();
    echo json_encode([
        'error' => 'Database prepare error',
        'debug' => $dbc->error
    ]);
    exit();
}

$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    ob_clean();
    echo json_encode([
        'error' => 'Database query error',
        'debug' => $stmt->error
    ]);
    exit();
}

$messages = $result->fetch_all(MYSQLI_ASSOC);

if (count($messages) > 0) {
    $lastMessageId = $messages[count($messages) - 1]['id'];
    $updateReadQuery = "
        UPDATE conversation_participants 
        SET last_read_message_id = ? 
        WHERE conversation_id = ? 
        AND user_id = ?
    ";
    $stmtUpdate = $dbc->prepare($updateReadQuery);
    
    if ($stmtUpdate) {
        $stmtUpdate->bind_param('iii', $lastMessageId, $conversation_id, $user_id);
        $stmtUpdate->execute();
    }
}

ob_clean();
echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages),
    'conversation_id' => $conversation_id
]);
?>