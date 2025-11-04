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
        'notify_spotlights'
    ];
    
    $dbc->begin_transaction();
    
    try {
        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '0';
            
          	if (!in_array($value, ['0', '1', 0, 1])) {
                throw new Exception("Invalid value for $field");
            }
            
            $query = "UPDATE user_notify SET $field = ? WHERE user_notify_switch_id = ?";
            $stmt = $dbc->prepare($query);
            
            if (!$stmt) {
             	error_log("Update Notify Settings - Prepare Error: " . $dbc->error . " | Field: $field | Switch ID: $switch_id");
              	throw new Exception("Failed to prepare statement for $field");
            }
            
            $stmt->bind_param('is', $value, $switch_id);
            
            if (!$stmt->execute()) {
             	error_log("Update Notify Settings - Execute Error: " . $stmt->error . " | Field: $field | Switch ID: $switch_id");
             	throw new Exception("Failed to update $field");
            }
            
            $stmt->close();
        }
        
        $dbc->commit();
        $response['status'] = 'success';
        $response['message'] = 'Notifications updated successfully';
        
    } catch (Exception $e) {
        $dbc->rollback();
        
      	error_log("Update Notify Settings - Exception: " . $e->getMessage() . " | Switch ID: " . $switch_id);
        
      	$error_message = 'Failed to update notification settings';
        
    	if (strpos($e->getMessage(), 'Invalid value for') === 0) {
            $error_message = 'Invalid notification setting value';
        }
        
        $response['message'] = $error_message;
        http_response_code(500);
    }
} else {
    $response['message'] = 'Session not found';
    http_response_code(401);
}

mysqli_close($dbc);
echo json_encode($response);
?>