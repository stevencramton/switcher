<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['id']) || !checkRole('spotlight_admin')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
$award_type = $_POST['award_type'] ?? '';

if ($inquiry_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid inquiry ID']);
    exit();
}

$valid_award_types = ['none', 'certificate', 'badge', 'both', 'gem'];
if (!in_array($award_type, $valid_award_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid award type']);
    exit();
}

try {
 	$column_check_query = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_type'";
    $column_result = mysqli_query($dbc, $column_check_query);
    $award_type_exists = mysqli_num_rows($column_result) > 0;
    
    if (!$award_type_exists) {
        echo json_encode(['status' => 'error', 'message' => 'Database not updated. Please run the migration script to add award_type column.']);
        exit();
    }
    
	$award_settings = [];
    
  	if (in_array($award_type, ['badge', 'both'])) {
	    $badge_id = isset($_POST['badge_id']) ? (int)$_POST['badge_id'] : null;
	    if (!$badge_id) {
	        echo json_encode([
	            'status' => 'error', 
	            'message' => 'Badge selection is required when badge awards are enabled'
	        ]);
	        exit();
	    }
    
	 	$badge_check_query = "SELECT id FROM badges WHERE id = ?";
	    $stmt_check = mysqli_prepare($dbc, $badge_check_query);
	    mysqli_stmt_bind_param($stmt_check, 'i', $badge_id);
	    mysqli_stmt_execute($stmt_check);
	    $result_check = mysqli_stmt_get_result($stmt_check);
    
	    if (mysqli_num_rows($result_check) == 0) {
	        mysqli_stmt_close($stmt_check);
	        echo json_encode([
	            'status' => 'error', 
	            'message' => 'Selected badge is not valid'
	        ]);
	        exit();
	    }
	    mysqli_stmt_close($stmt_check);
    
	    $award_settings['badge_id'] = $badge_id;
	}
    
 	$award_settings_json = json_encode($award_settings);
    
 	$settings_column_check = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_settings'";
    $settings_result = mysqli_query($dbc, $settings_column_check);
    $settings_exists = mysqli_num_rows($settings_result) > 0;
    
    if ($settings_exists) {
    	$update_query = "UPDATE spotlight_inquiry SET award_type = ?, award_settings = ? WHERE inquiry_id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        
        if (!$stmt) {
            throw new Exception('Database preparation failed: ' . mysqli_error($dbc));
        }
        
        mysqli_stmt_bind_param($stmt, 'ssi', $award_type, $award_settings_json, $inquiry_id);
    } else {
     	$update_query = "UPDATE spotlight_inquiry SET award_type = ? WHERE inquiry_id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        
        if (!$stmt) {
            throw new Exception('Database preparation failed: ' . mysqli_error($dbc));
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $award_type, $inquiry_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        
        $user = $_SESSION['user'] ?? 'unknown';
        
     	$log_message = "User {$user} updated award type for spotlight {$inquiry_id} to '{$award_type}'";
        if (!empty($award_settings)) {
            $log_message .= " with settings: " . $award_settings_json;
        }
        error_log($log_message);
        
        $response_message = '';
        switch ($award_type) {
            case 'none':
                $response_message = 'Awards disabled for this spotlight';
                break;
            case 'certificate':
                $response_message = 'Certificate awards enabled for this spotlight';
                break;
            case 'badge':
                $response_message = 'Badge awards enabled for this spotlight';
                break;
            case 'both':
                $response_message = 'Certificate and badge awards enabled for this spotlight';
                break;
            case 'gem':
                $response_message = 'Gem awards enabled for this spotlight';
                break;
        }
        
        echo json_encode([
            'status' => 'success', 
            'message' => $response_message
        ]);
    } else {
        mysqli_stmt_close($stmt);
        throw new Exception('Failed to update award settings: ' . mysqli_error($dbc));
    }
    
} catch (Exception $e) {
    error_log('Award settings update error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred while updating award settings'
    ]);
}

exit();
?>