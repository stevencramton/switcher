<?php
session_start(); 
include '../../mysqli_connect.php';
include '../../templates/functions.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

$query = "
    SELECT 
        c.id as conversation_id,
        c.name,
        c.type,
        c.created_by,
        c.updated_at,
        (SELECT COUNT(*) FROM conversation_participants WHERE conversation_id = c.id AND left_at IS NULL) as participant_count,
        (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY timestamp DESC LIMIT 1) as last_message,
        (SELECT timestamp FROM chat_messages WHERE conversation_id = c.id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM chat_messages cm 
         WHERE cm.conversation_id = c.id 
         AND cm.id > COALESCE((SELECT last_read_message_id FROM conversation_participants WHERE conversation_id = c.id AND user_id = ?), 0)
         AND cm.sender_id != ?) as unread_count
    FROM conversations c
    INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
    WHERE cp.user_id = ?
    AND cp.left_at IS NULL
    AND c.deleted_at IS NULL
    ORDER BY c.updated_at DESC
";

$stmt = $dbc->prepare($query);
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$conversations = [];

while ($row = $result->fetch_assoc()) {
    $conversation_id = $row['conversation_id'];
    
  	$participantsQuery = "
        SELECT u.id, u.first_name, u.last_name, u.profile_pic, u.user
        FROM users u
        INNER JOIN conversation_participants cp ON u.id = cp.user_id
        WHERE cp.conversation_id = ?
        AND cp.left_at IS NULL
        AND u.id != ?
    ";
    
    $stmtPart = $dbc->prepare($participantsQuery);
    $stmtPart->bind_param('ii', $conversation_id, $user_id);
    $stmtPart->execute();
    $participantsResult = $stmtPart->get_result();
    $participants = $participantsResult->fetch_all(MYSQLI_ASSOC);
    
  	if ($row['type'] === 'individual' && count($participants) > 0) {
        $row['display_name'] = $participants[0]['first_name'] . ' ' . $participants[0]['last_name'];
        $row['display_pic'] = $participants[0]['profile_pic'];
    } else if ($row['type'] === 'group') {
        $row['display_name'] = $row['name'] ?: 'Group Chat';
     	
		if (count($participants) > 0) {
            $row['display_pic'] = $participants[0]['profile_pic'];
        } else {
            $row['display_pic'] = 'img/profile_pic/avatar.png';
        }
    }
    
    $row['participants'] = $participants;
    $conversations[] = $row;
}

echo json_encode(['conversations' => $conversations]);
?>