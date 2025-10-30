<?php
session_start();
include '../../templates/functions.php';
if (!checkRole('system_alerts')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['toast_notification'], $_POST['toast_message'])) {
        date_default_timezone_set("America/New_York");
        
   	 	$toast_time = isset($_POST['toast_time']) && !empty($_POST['toast_time']) 
            ? trim($_POST['toast_time']) 
            : date('g:i A');
            
        $toast_notification = trim($_POST['toast_notification']);
        $toast_message = trim($_POST['toast_message']);
        $toast_type = isset($_POST['toast_type']) ? trim($_POST['toast_type']) : 'info';
        $toast_sound = isset($_POST['toast_sound']) ? trim($_POST['toast_sound']) : '';
        
        if ($toast_notification === '' || $toast_message === '') {
            echo json_encode(['success' => false, 'error' => 'Toast notification and message are required.']);
            exit;
        }
        
        $toast_notification_safe = htmlspecialchars($toast_notification, ENT_QUOTES, 'UTF-8');
        $toast_message_safe = htmlspecialchars($toast_message, ENT_QUOTES, 'UTF-8');
        $toast_sound_safe = htmlspecialchars($toast_sound, ENT_QUOTES, 'UTF-8');
        
        $data = [
            'message' => [
                'toast_time' => $toast_time,
                'toast_notification' => $toast_notification_safe,
                'toast_message' => $toast_message_safe,
                'toast_type' => $toast_type,
                'toast_sound' => $toast_sound_safe
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
?>