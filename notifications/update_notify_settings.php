<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('notification_settings')){
    header("Location:../../index.php?msg1");
    exit();
}

$response = ['status' => 'error', 'message' => 'Unknown error'];

if (isset($_SESSION['switch_id'])) { 
    $switch_id = $_SESSION['switch_id'];
    
    // Updated fields array to include notify_spotlights
    $fields = [
        'notify_all',
        'notify_totals',
        'notify_alerts',
        'notify_blog_posts',
        'notify_messages',
        'notify_kudos',
        'notify_badges',
        'notify_pass_resets',
        'notify_polls',
        'notify_feedback',
        'notify_gems',
        'notify_spotlights'  // Added this field
    ];
    
    $dbc->begin_transaction();
    
    try {
        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '0';
            
            // Validate that the value is either 0 or 1
            if (!in_array($value, ['0', '1', 0, 1])) {
                throw new Exception("Invalid value for $field");
            }
            
            $query = "UPDATE user_notify SET $field = ? WHERE user_notify_switch_id = ?";
            $stmt = $dbc->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement for $field: " . $dbc->error);
            }
            
            $stmt->bind_param('is', $value, $switch_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update $field: " . $stmt->error);
            }
            
            $stmt->close();
        }
        
        $dbc->commit();
        $response['status'] = 'success';
        $response['message'] = 'Notifications updated successfully';
        
    } catch (Exception $e) {
        $dbc->rollback();
        $response['message'] = $e->getMessage();
        error_log("Notification settings update error: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Session not found';
}

mysqli_close($dbc);
echo json_encode($response);
?>