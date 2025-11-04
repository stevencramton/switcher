<?php
session_start();
include 'functions.php';

if (!isset($_SESSION['id'])){
    header("Location:../../index.php?msg1");
    exit();
}

$originalDir = __DIR__ . '/uploads/original/';
$stegoDir = __DIR__ . '/uploads/stego/';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'User session not set.']);
    exit();
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['message'])) {
    $imagePath = $originalDir . $user . '_' . basename($_FILES['image']['name']);
    $message = $_POST['message'];
    $outputPath = $stegoDir . $user . '_stego_' . basename($_FILES['image']['name']);

	if (!file_exists($originalDir)) {
        mkdir($originalDir, 0755, true);
    }
    if (!file_exists($stegoDir)) {
        mkdir($stegoDir, 0755, true);
    }

	deleteFiles($originalDir, $user);
    deleteFiles($stegoDir, $user);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        embedMessage($imagePath, $message, $outputPath);

   	 	$relativeOutputPath = str_replace(__DIR__, '', $outputPath);
        $relativeOutputPath = ltrim($relativeOutputPath, '/');
        $downloadUrl = 'https://switchboardapp.net/dashboard/ajax/stego/' . $relativeOutputPath;
		
		echo json_encode(['success' => true, 'imageUrl' => $downloadUrl]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload image.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}

function deleteFiles($directory, $user) {
    $files = glob($directory . $user . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}