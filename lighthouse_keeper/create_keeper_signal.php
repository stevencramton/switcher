<?php
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
	http_response_code(401);
	die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['id'];
$is_admin = checkRole('lighthouse_keeper');

// Validate required fields
$required_fields = ['dock_id', 'signal_type', 'title', 'message'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit();
    }
}

$dock_id = (int)$_POST['dock_id'];
$signal_type = trim($_POST['signal_type']);
$title = trim($_POST['title']);
$message = trim($_POST['message']);

// For admins, allow setting priority and assignment
$priority_id = 2; // Default to Medium
$keeper_assigned = NULL;
$service_id = NULL;

if ($is_admin) {
    if (!empty($_POST['priority_id'])) {
        $priority_id = (int)$_POST['priority_id'];
    }
    if (!empty($_POST['keeper_assigned'])) {
        $keeper_assigned = (int)$_POST['keeper_assigned'];
    }
    if (!empty($_POST['service_id'])) {
        $service_id = (int)$_POST['service_id'];
    }
}

// Generate unique signal number: SIG-YYYYMMDD-XXXX
$date_part = date('Ymd');

// Get the highest signal number for today
$max_query = "SELECT signal_number FROM lh_signals 
              WHERE signal_number LIKE CONCAT('SIG-', ?, '-%') 
              ORDER BY signal_number DESC LIMIT 1";
$max_stmt = mysqli_prepare($dbc, $max_query);
mysqli_stmt_bind_param($max_stmt, 's', $date_part);
mysqli_stmt_execute($max_stmt);
$max_result = mysqli_stmt_get_result($max_stmt);

if ($max_row = mysqli_fetch_assoc($max_result)) {
    // Extract the number from the signal number (e.g., "SIG-20251117-0003" -> 3)
    $last_number = (int)substr($max_row['signal_number'], -4);
    $count = $last_number + 1;
} else {
    // No signals today yet
    $count = 1;
}

mysqli_stmt_close($max_stmt);

$signal_number = sprintf('SIG-%s-%04d', $date_part, $count);

// Default status (Open)
$sea_state_id = 1;

// *** CHANGE: Generate timestamp using PHP instead of MySQL DEFAULT CURRENT_TIMESTAMP ***
$current_datetime = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($dbc);

try {
    // Insert signal with explicit timestamps
    $query = "INSERT INTO lh_signals (signal_number, title, message, signal_type, dock_id, service_id, sea_state_id, priority_id, sent_by, keeper_assigned, sent_date, updated_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssssiiiiiiss', 
        $signal_number, 
        $title, 
        $message, 
        $signal_type, 
        $dock_id, 
        $service_id,
        $sea_state_id, 
        $priority_id, 
        $user_id, 
        $keeper_assigned,
        $current_datetime,  // sent_date
        $current_datetime   // updated_date
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to send a signal');
    }
    
    $signal_id = mysqli_insert_id($dbc);
    
    // Log activity with explicit timestamp
    $activity_query = "INSERT INTO lh_signal_activity (signal_id, user_id, activity_type, new_value, created_date) 
                      VALUES (?, ?, 'created', 'Signal created', ?)";
    $activity_stmt = mysqli_prepare($dbc, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'iis', $signal_id, $user_id, $current_datetime);
    
    if (!mysqli_stmt_execute($activity_stmt)) {
        throw new Exception('Failed to log activity');
    }
    
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
                    // Insert into database
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
    
    // Commit transaction
    mysqli_commit($dbc);
    
    $response = [
        'success' => true,
        'signal_id' => $signal_id,
        'signal_number' => $signal_number,
        'message' => 'Signal created successfully'
    ];
    
    if ($attachments_uploaded > 0) {
        $response['attachments_uploaded'] = $attachments_uploaded;
    }
    
    if (!empty($upload_errors)) {
        $response['upload_errors'] = $upload_errors;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send a signal: ' . $e->getMessage()
    ]);
}
?>