<?php
session_start();
include 'functions.php';

if (!isset($_SESSION['id'])){
    header("Location:../../index.php?msg1");
    exit();
}

$stegoDir = __DIR__ . '/uploads/stego/';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['stego_image'])) {
    $stegoImagePath = $stegoDir . basename($_FILES['stego_image']['name']);

    if (!file_exists($stegoDir)) {
        mkdir($stegoDir, 0755, true);
    }
	if (move_uploaded_file($_FILES['stego_image']['tmp_name'], $stegoImagePath)) {
        $extractedMessage = extractMessage($stegoImagePath);

        if ($extractedMessage !== false) {
            if ($extractedMessage === '203420985234') {
                $_SESSION['discovery_allowed'] = true;
                echo json_encode(['success' => true, 'message' => $extractedMessage, 'showButton' => true]);
            } else {
                echo json_encode(['success' => true, 'message' => $extractedMessage, 'showButton' => false]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to extract message.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload stego image.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}