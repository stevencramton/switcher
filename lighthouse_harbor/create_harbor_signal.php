<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_harbor')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$user_id = $_SESSION['id'];

// Validate required fields
if (empty($_POST['title']) || empty($_POST['message']) || empty($_POST['priority_id'])) {
    die(json_encode(['success' => false, 'message' => 'Please fill in all required fields']));
}

$title = trim($_POST['title']);
$message = trim($_POST['message']);
$priority_id = (int)$_POST['priority_id'];

// Generate signal number
$today = date('Ymd');

// Get the highest signal number for today
$max_query = "SELECT signal_number FROM lh_signals 
              WHERE signal_number LIKE CONCAT('SIG-', ?, '-%') 
              ORDER BY signal_number DESC LIMIT 1";
$max_stmt = mysqli_prepare($dbc, $max_query);
mysqli_stmt_bind_param($max_stmt, 's', $today);
mysqli_stmt_execute($max_stmt);
$max_result = mysqli_stmt_get_result($max_stmt);

if ($max_row = mysqli_fetch_assoc($max_result)) {
    // Extract the number from the signal number (e.g., "SIG-20251117-0003" -> 3)
    $last_number = (int)substr($max_row['signal_number'], -4);
    $daily_count = $last_number + 1;
} else {
    // No signals today yet
    $daily_count = 1;
}

mysqli_stmt_close($max_stmt);

$signal_number = sprintf('SIG-%s-%04d', $today, $daily_count);

// Get default sea state (first active one, typically "Incoming")
$default_state_query = "SELECT sea_state_id FROM lh_sea_states WHERE is_active = 1 ORDER BY sea_state_order LIMIT 1";
$default_state_result = mysqli_query($dbc, $default_state_query);
$default_state = mysqli_fetch_assoc($default_state_result);
$sea_state_id = $default_state['sea_state_id'];

// Get default dock (if set)
$dock_id = null;
$default_dock_query = "SELECT dock_id FROM lh_docks WHERE is_default = 1 AND is_active = 1 LIMIT 1";
$default_dock_result = mysqli_query($dbc, $default_dock_query);
if ($default_dock_result && $default_dock = mysqli_fetch_assoc($default_dock_result)) {
    $dock_id = $default_dock['dock_id'];
}

// Get current datetime for consistent timestamps (America/New_York)
$current_datetime = date('Y-m-d H:i:s');

// Insert signal with explicit timestamps and default dock
$insert_query = "INSERT INTO lh_signals (signal_number, title, message, priority_id, sea_state_id, dock_id, sent_by, sent_date, updated_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = mysqli_prepare($dbc, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 'sssiiiiss', $signal_number, $title, $message, $priority_id, $sea_state_id, $dock_id, $user_id, $current_datetime, $current_datetime);

if (mysqli_stmt_execute($insert_stmt)) {
    $signal_id = mysqli_insert_id($dbc);
    
    // Log activity with explicit timestamp
    $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                       VALUES (?, ?, 'created', 'Signal created', ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iis', $signal_id, $user_id, $current_datetime);
    mysqli_stmt_execute($activity_stmt);
    
    // Handle file uploads
    $attachments_uploaded = 0;
    $upload_errors = [];
    
    if (isset($_FILES['signal_attachments']) && !empty($_FILES['signal_attachments']['name'][0])) {
        $upload_dir = '../../img/signals/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_count = count($_FILES['signal_attachments']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['signal_attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES['signal_attachments']['name'][$i]);
                $file_tmp = $_FILES['signal_attachments']['tmp_name'][$i];
                $file_size = $_FILES['signal_attachments']['size'][$i];
                $file_type = $_FILES['signal_attachments']['type'][$i];
                
                // Validate file size (5MB max)
                if ($file_size > 5242880) {
                    $upload_errors[] = "$file_name is too large (max 5MB)";
                    continue;
                }
                
                // Validate file type
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    $upload_errors[] = "$file_name has an invalid file type";
                    continue;
                }
                
                // Generate unique filename
                $unique_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                $file_path = $upload_dir . $unique_name;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert into database with explicit timestamp
                    $attachment_query = "INSERT INTO lh_signal_attachments (signal_id, uploaded_by, file_name, file_path, file_size, file_type, uploaded_date) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $attachment_stmt = mysqli_prepare($dbc, $attachment_query);
                    $db_file_path = 'img/signals/' . $unique_name;
                    mysqli_stmt_bind_param($attachment_stmt, 'iississ', $signal_id, $user_id, $file_name, $db_file_path, $file_size, $file_type, $current_datetime);
                    
                    if (mysqli_stmt_execute($attachment_stmt)) {
                        $attachments_uploaded++;
                    } else {
                        $upload_errors[] = "Failed to save $file_name to database";
                        unlink($file_path); // Delete the uploaded file
                    }
                } else {
                    $upload_errors[] = "Failed to upload $file_name";
                }
            }
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Signal created successfully',
        'signal_id' => $signal_id,
        'signal_number' => $signal_number
    ];
    
    if ($attachments_uploaded > 0) {
        $response['attachments_uploaded'] = $attachments_uploaded;
    }
    
    if (!empty($upload_errors)) {
        $response['upload_errors'] = $upload_errors;
    }
    
    echo json_encode($response);
} else {
    $error = mysqli_stmt_error($insert_stmt);
    $error_message = 'Failed to create signal';
    
    // Check if it's a duplicate key error
    if (strpos($error, 'Duplicate entry') !== false && strpos($error, 'signal_number') !== false) {
        $error_message = 'Signal number already exists. Please try again.';
    }
    
    echo json_encode(['success' => false, 'message' => $error_message]);
}

mysqli_stmt_close($insert_stmt);
mysqli_close($dbc);
?>