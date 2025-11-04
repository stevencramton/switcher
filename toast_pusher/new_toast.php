<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('system_alerts')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

if (isset($_POST['toast_notification'])) {

	date_default_timezone_set("America/New_York");
	$toast_time = date('g:i A');
    $toast_notification = mysqli_real_escape_string($dbc, $_POST['toast_notification']);
    $toast_message = mysqli_real_escape_string($dbc, $_POST['toast_message']);
    $toast_sound = mysqli_real_escape_string($dbc, $_POST['toast_sound']);
    $toast_persist = mysqli_real_escape_string($dbc, $_POST['toast_persist']);

	if (empty($toast_notification) || empty($toast_message)) {
		echo json_encode(['success' => false, 'error' => 'Toast notification and message are required.']);
        exit;
    }

	$toast_notification_safe = htmlspecialchars($toast_notification, ENT_QUOTES, 'UTF-8');
    $toast_message_safe = htmlspecialchars($toast_message, ENT_QUOTES, 'UTF-8');

  	$data = [
        'message' => [
            'toast_time' => $toast_time,
            'toast_notification' => $toast_notification_safe,
            'toast_message' => $toast_message_safe,
            'toast_sound' => $toast_sound,
            'toast_persist' => $toast_persist
        ]
    ];

	echo json_encode(['success' => true, 'data' => $data]);
} else {
	echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}