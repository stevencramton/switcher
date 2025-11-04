<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

function safePostValue($key, $default = '') {
    return isset($_POST[$key]) && $_POST[$key] !== null ? 
           mysqli_real_escape_string($GLOBALS['dbc'], strip_tags($_POST[$key])) : 
           $default;
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {

    $inquiry_id = safePostValue('inquiry_id');
	$inquiry_name = safePostValue('inquiry_name');
 	$inquiry_image = safePostValue('inquiry_image');
	
    if (empty($inquiry_image)) {
        $inquiry_image = safePostValue('inquiry_spotlight_image_link_edit');
    }
    
    $inquiry_nominee_image = safePostValue('inquiry_nominee_image');
    $nominee_name = safePostValue('nominee_name');
    $inquiry_overview = safePostValue('inquiry_overview');
    $inquiry_info = safePostValue('inquiry_info');
    $bullet_one = safePostValue('bullet_one');
    $bullet_two = safePostValue('bullet_two');
    $bullet_three = safePostValue('bullet_three');
    $special_preview = safePostValue('special_preview');
    $inquiry_opening = safePostValue('inquiry_opening');
    $inquiry_closing = safePostValue('inquiry_closing');
    $showcase_start_date = safePostValue('showcase_start_date');
    $showcase_end_date = safePostValue('showcase_end_date');
    $inquiry_status = safePostValue('inquiry_status');
    
	if (empty($showcase_start_date)) {
        $showcase_start_date = null;
    }
    if (empty($showcase_end_date)) {
        $showcase_end_date = null;
    }
    
	if (!empty($inquiry_status)) {
 	   	$allowed_statuses = array('Active', 'Paused', 'Closed');
        
		if (!in_array($inquiry_status, $allowed_statuses)) {
            error_log("Invalid status attempted: $inquiry_status for spotlight ID: $inquiry_id");
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status value']);
            mysqli_close($dbc);
            exit();
        }
        
     	if ($inquiry_status === 'Active' && !empty($inquiry_closing)) {
            $current_timestamp = time();
         	$closing_timestamp = false;
            
          	$date_formats = [
                'm/d/Y h:i A',
                'm/d/Y H:i',
                'Y-m-d H:i:s',
                'm-d-Y h:i A',
            ];
            
            foreach ($date_formats as $format) {
                $closing_datetime = DateTime::createFromFormat($format, $inquiry_closing);
                if ($closing_datetime !== false) {
                    $closing_timestamp = $closing_datetime->getTimestamp();
                    break;
                }
            }
            
        	if ($closing_timestamp !== false && $current_timestamp > $closing_timestamp) {
                $inquiry_status = 'Closed';
           	}
        }
    }
    
 	$updateFields = array();
    $updateValues = array();
    
    if (!empty($inquiry_name)) {
        $updateFields[] = "inquiry_name = ?";
        $updateValues[] = $inquiry_name;
    }
    
    if (!empty($inquiry_image)) {
        $updateFields[] = "inquiry_image = ?";
        $updateValues[] = $inquiry_image;
    }
    
    if (!empty($inquiry_nominee_image)) {
        $updateFields[] = "inquiry_nominee_image = ?";
        $updateValues[] = $inquiry_nominee_image;
    }
    
    if (!empty($nominee_name)) {
        $updateFields[] = "nominee_name = ?";
        $updateValues[] = $nominee_name;
    }
    
    if (!empty($inquiry_overview)) {
        $updateFields[] = "inquiry_overview = ?";
        $updateValues[] = $inquiry_overview;
    }
    
    if (!empty($inquiry_info)) {
        $updateFields[] = "inquiry_info = ?";
        $updateValues[] = $inquiry_info;
    }
    
    if (!empty($bullet_one)) {
        $updateFields[] = "bullet_one = ?";
        $updateValues[] = $bullet_one;
    }
    
    if (!empty($bullet_two)) {
        $updateFields[] = "bullet_two = ?";
        $updateValues[] = $bullet_two;
    }
    
    if (!empty($bullet_three)) {
        $updateFields[] = "bullet_three = ?";
        $updateValues[] = $bullet_three;
    }
    
    if (!empty($special_preview)) {
        $updateFields[] = "special_preview = ?";
        $updateValues[] = $special_preview;
    }
    
    if (!empty($inquiry_opening)) {
        $updateFields[] = "inquiry_opening = ?";
        $updateValues[] = $inquiry_opening;
    }
    
    if (!empty($inquiry_closing)) {
        $updateFields[] = "inquiry_closing = ?";
        $updateValues[] = $inquiry_closing;
    }
    
 	if (isset($_POST['showcase_start_date'])) {
        $updateFields[] = "showcase_start_date = ?";
        $updateValues[] = $showcase_start_date;
    }
    
    if (isset($_POST['showcase_end_date'])) {
        $updateFields[] = "showcase_end_date = ?";
        $updateValues[] = $showcase_end_date;
    }
    
    if (!empty($inquiry_status)) {
        $updateFields[] = "inquiry_status = ?";
        $updateValues[] = $inquiry_status;
    }
    
 	if (!empty($updateFields)) {
        $query = "UPDATE spotlight_inquiry SET " . implode(', ', $updateFields) . " WHERE inquiry_id = ?";
      	$updateValues[] = $inquiry_id;
        
     	if ($stmt = mysqli_prepare($dbc, $query)) {
        	$types = str_repeat('s', count($updateValues));
         	mysqli_stmt_bind_param($stmt, $types, ...$updateValues);
         	$result = mysqli_stmt_execute($stmt);
            
            if (!$result) {
              	http_response_code(500);
                echo json_encode(['error' => 'Update failed']);
            } else {
                echo json_encode(['success' => 'Spotlight updated successfully']);
            }
         	mysqli_stmt_close($stmt);
        } else {
            error_log("Spotlight update prepare failed: " . mysqli_error($dbc));
            http_response_code(500);
            echo json_encode(['error' => 'Database prepare failed']);
        }
    } else {
      	echo json_encode(['warning' => 'No fields to update']);
    }
    
} else {
   	http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}

mysqli_close($dbc);
?>