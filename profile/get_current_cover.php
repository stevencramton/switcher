<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to view your profile cover image.'
    ]);
    exit;
}

$user_id = (int)$_SESSION['id'];

try {
 	$stmt = $dbc->prepare("SELECT profile_cover_image FROM users WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $dbc->error);
    }
    
  	$stmt->bind_param("i", $user_id);
    
  	if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'status' => 'success',
                'cover_image' => $row['profile_cover_image'] ?: 'img/profile_page/profile-cover.jpg'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found.'
            ]);
        }
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
	echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while retrieving your profile cover image.'
    ]);
}

$dbc->close();
?>