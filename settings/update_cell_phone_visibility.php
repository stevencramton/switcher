<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['switch_id'])) {
 	echo json_encode("error");
    exit();
}

if (!isset($_POST['switch_id']) || !isset($_POST['cell_phone_visibility'])) {
  	echo json_encode("error");
    exit();
}

$switch_id = (int)$_SESSION['switch_id'];
$cell_phone_visibility = (int)$_POST['cell_phone_visibility'];

if ($cell_phone_visibility !== 0 && $cell_phone_visibility !== 1) {
    error_log("Cell visibility update: Invalid visibility value: $cell_phone_visibility");
    echo json_encode("error");
    exit();
}

if ($switch_id != (int)$_POST['switch_id']) {
    error_log("Cell visibility update: Switch ID mismatch. Session: $switch_id, Posted: " . $_POST['switch_id']);
    echo json_encode("error");
    exit();
}

try {
 	$check_query = "SELECT user_search_id FROM user_settings_search WHERE user_settings_switch_id = ?";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $switch_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $has_settings = mysqli_num_rows($check_result) > 0;
    mysqli_stmt_close($check_stmt);
    
    if (!$has_settings) {
     	if (function_exists('initializeUserSearchSettings')) {
            if (!initializeUserSearchSettings($dbc, $switch_id)) {
                error_log("Cell visibility update: Failed to initialize search settings for user $switch_id");
                echo json_encode("error");
                exit();
            }
        } else {
        	$init_query = "INSERT INTO user_settings_search 
                          (user_settings_switch_id, search_type, search_email, search_notes, 
                           search_user_agency, search_area_location, search_department, search_pronouns, search_cell) 
                          VALUES (?, 'category', 1, 1, 1, 1, 1, 1, ?)";
            $init_stmt = mysqli_prepare($dbc, $init_query);
            mysqli_stmt_bind_param($init_stmt, "ii", $switch_id, $cell_phone_visibility);
            
            if (!mysqli_stmt_execute($init_stmt)) {
                error_log("Cell visibility update: Failed to initialize search settings: " . mysqli_stmt_error($init_stmt));
                mysqli_stmt_close($init_stmt);
                echo json_encode("error");
                exit();
            }
            mysqli_stmt_close($init_stmt);
            
      	  	$categories_query = "SELECT switchboard_cat_id FROM switchboard_categories";
            $categories_result = mysqli_query($dbc, $categories_query);
            
            while ($category = mysqli_fetch_assoc($categories_result)) {
                $cat_id = (int) $category['switchboard_cat_id'];
                
                $cat_query = "INSERT INTO user_settings_search_categories 
                             (user_settings_switch_id, switchboard_cat_id, is_enabled) 
                             VALUES (?, ?, 1)";
                
                $cat_stmt = mysqli_prepare($dbc, $cat_query);
                mysqli_stmt_bind_param($cat_stmt, 'ii', $switch_id, $cat_id);
                mysqli_stmt_execute($cat_stmt);
                mysqli_stmt_close($cat_stmt);
            }
        }
    } else {
     	$update_query = "UPDATE user_settings_search SET search_cell = ?, updated_at = CURRENT_TIMESTAMP WHERE user_settings_switch_id = ?";
        $update_stmt = mysqli_prepare($dbc, $update_query);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "ii", $cell_phone_visibility, $switch_id);
            $result = mysqli_stmt_execute($update_stmt);
            
            if ($result) {
                $affected_rows = mysqli_stmt_affected_rows($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                if ($affected_rows > 0) {
                 	echo json_encode("success");
                } else {
                  	echo json_encode("error");
                }
            } else {
              	mysqli_stmt_close($update_stmt);
                echo json_encode("error");
            }
        } else {
          	echo json_encode("error");
        }
    }
    
} catch (Exception $e) {
    error_log("Cell phone visibility update error: " . $e->getMessage());
    echo json_encode("error");
}

mysqli_close($dbc);
?>