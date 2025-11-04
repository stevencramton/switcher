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
        'message' => 'You must be logged in to update your profile cover image.'
    ]);
    exit;
}

if (!isset($_POST['cover_image'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Cover image URL is required.'
    ]);
    exit;
}

$user_id = (int)$_SESSION['id'];
$cover_image = trim($_POST['cover_image']);

if (empty($cover_image)) {
    $cover_image = 'img/profile_page/profile-cover.jpg';
}

if ($cover_image !== 'img/profile_page/profile-cover.jpg') {
	$valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    $file_extension = strtolower(pathinfo(parse_url($cover_image, PHP_URL_PATH), PATHINFO_EXTENSION));

    if (!in_array($file_extension, $valid_extensions)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please provide a valid image URL with a supported file extension (.jpg, .png, .gif, etc.).'
        ]);
        exit;
    }

	if (preg_match('/[-_\/]$/', $cover_image)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'The image URL appears to be incomplete. Please provide a complete image URL.'
        ]);
        exit;
    }

	if (filter_var($cover_image, FILTER_VALIDATE_URL)) {
      	if (!filter_var($cover_image, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please provide a valid image URL.'
            ]);
            exit;
        }
    } else {
     	if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+\.' . implode('|', $valid_extensions) . '$/i', $cover_image)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please provide a valid relative path to an image file.'
            ]);
            exit;
        }
    }
}

if (strlen($cover_image) > 500) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Image URL is too long. Maximum 500 characters allowed.'
    ]);
    exit;
}

try {
 	$stmt = $dbc->prepare("UPDATE users SET profile_cover_image = ? WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $dbc->error);
    }
    
  	$stmt->bind_param("si", $cover_image, $user_id);
    
	if ($stmt->execute()) {
    	if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile cover image updated successfully!',
                'cover_image' => $cover_image
            ]);
        } else {
          	echo json_encode([
                'status' => 'info',
                'message' => 'No changes were made. The cover image may already be set to this URL.',
                'cover_image' => $cover_image
            ]);
        }
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
 	echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while updating your profile cover image. Please try again.'
    ]);
}

$dbc->close();
?>