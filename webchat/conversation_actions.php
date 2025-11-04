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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createConversation($dbc, $user_id);
        break;
    
    case 'delete':
        deleteConversation($dbc, $user_id);
        break;
    
    case 'get_users':
        getAvailableUsers($dbc, $user_id);
        break;
    
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function createConversation($dbc, $user_id) {
    $participant_ids = $_POST['participant_ids'] ?? [];
    $name = $_POST['name'] ?? null;
    $type = $_POST['type'] ?? 'individual';
    
    if (!is_array($participant_ids) || empty($participant_ids)) {
        echo json_encode(['error' => 'No participants selected']);
        exit();
    }
    
 	foreach ($participant_ids as $id) {
        if (!is_numeric($id) || $id <= 0) {
            echo json_encode(['error' => 'Invalid participant ID']);
            exit();
        }
    }
    
	if ($type === 'individual' && count($participant_ids) === 1) {
        $checkQuery = "
            SELECT c.id FROM conversations c
            INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id
            INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id
            WHERE c.type = 'individual' 
            AND c.deleted_at IS NULL
            AND cp1.user_id = ? 
            AND cp2.user_id = ?
            AND cp1.left_at IS NULL
            AND cp2.left_at IS NULL
            LIMIT 1
        ";
        
        $stmt = $dbc->prepare($checkQuery);
        $stmt->bind_param('ii', $user_id, $participant_ids[0]);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'conversation_id' => $row['id'],
                'exists' => true
            ]);
            exit();
        }
    }
    
	$dbc->begin_transaction();
    
    try {
        $insertConvQuery = "INSERT INTO conversations (name, type, created_by) VALUES (?, ?, ?)";
        $stmt = $dbc->prepare($insertConvQuery);
        $stmt->bind_param('ssi', $name, $type, $user_id);
        $stmt->execute();
        $conversation_id = $stmt->insert_id;
        
      	$all_participants = array_unique(array_merge([$user_id], $participant_ids));
        $insertPartQuery = "INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)";
        $stmtPart = $dbc->prepare($insertPartQuery);
        
        foreach ($all_participants as $participant_id) {
            $stmtPart->bind_param('ii', $conversation_id, $participant_id);
            $stmtPart->execute();
        }
        
        $dbc->commit();
        
        echo json_encode([
            'success' => true,
            'conversation_id' => $conversation_id,
            'exists' => false
        ]);
        
    } catch (Exception $e) {
        $dbc->rollback();
        echo json_encode(['error' => 'Failed to create conversation: ' . $e->getMessage()]);
    }
}

function deleteConversation($dbc, $user_id) {
    $conversation_id = $_POST['conversation_id'] ?? 0;
    
    if ($conversation_id <= 0) {
        echo json_encode(['error' => 'Invalid conversation ID']);
        exit();
    }
    
	$checkQuery = "
        SELECT created_by, type FROM conversations 
        WHERE id = ? AND deleted_at IS NULL
    ";
    
    $stmt = $dbc->prepare($checkQuery);
    $stmt->bind_param('i', $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Conversation not found']);
        exit();
    }
    
    $conversation = $result->fetch_assoc();
    
  	if ($conversation['type'] === 'individual') {
        $updateQuery = "
            UPDATE conversation_participants 
            SET left_at = NOW() 
            WHERE conversation_id = ? AND user_id = ?
        ";
        
        $stmt = $dbc->prepare($updateQuery);
        $stmt->bind_param('ii', $conversation_id, $user_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
    	if ($conversation['created_by'] != $user_id) {
            echo json_encode(['error' => 'Only group creator can delete this conversation']);
            exit();
        }
        
        $deleteQuery = "UPDATE conversations SET deleted_at = NOW() WHERE id = ?";
        $stmt = $dbc->prepare($deleteQuery);
        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
    }
}

function getAvailableUsers($dbc, $user_id) {
    $query = "
        SELECT id, first_name, last_name, user, profile_pic, display_name
        FROM users 
        WHERE id != ? 
        AND account_delete = 0
        ORDER BY first_name, last_name
    ";
    
    $stmt = $dbc->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['users' => $users]);
}
?>