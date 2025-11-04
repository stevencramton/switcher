<?php
ob_start();
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['emp_id'])) {
    $switch_id = strip_tags($_SESSION['switch_id']);
    $selection = strip_tags($_POST['selection']);
    $search_email = (int) strip_tags($_POST['search_email']);
    $search_notes = (int) strip_tags($_POST['search_notes']);
    $search_user_agency = (int) strip_tags($_POST['search_user_agency']);
    $search_department = (int) strip_tags($_POST['search_department']);
    $search_area_location = (int) strip_tags($_POST['search_area_location']);
    $search_pronouns = (int) strip_tags($_POST['search_pronouns']);
    $emp_id = strip_tags($_POST['emp_id']);
    
 	$selected_categories = array_map('trim', explode(',', $emp_id));
  	$selected_categories = array_filter($selected_categories, function($value) {
        return $value !== '' && $value !== null;
    });
 	$selected_categories = array_map('intval', $selected_categories);
    
  	mysqli_autocommit($dbc, false);
    
    try {
      	$query = "INSERT INTO user_settings_search 
                  (user_settings_switch_id, search_type, search_email, search_notes, search_user_agency, search_area_location, search_department, search_pronouns) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                  search_type = VALUES(search_type),
                  search_email = VALUES(search_email),
                  search_notes = VALUES(search_notes),
                  search_user_agency = VALUES(search_user_agency),
                  search_area_location = VALUES(search_area_location),
                  search_department = VALUES(search_department),
                  search_pronouns = VALUES(search_pronouns),
                  updated_at = CURRENT_TIMESTAMP";
        
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, 'isiiiiii', $switch_id, $selection, $search_email, $search_notes, $search_user_agency, $search_area_location, $search_department, $search_pronouns);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update search settings");
        }
        mysqli_stmt_close($stmt);
        
   	 	$all_categories_query = "SELECT switchboard_cat_id FROM switchboard_categories";
        $all_categories_result = mysqli_query($dbc, $all_categories_query);
        $all_category_ids = [];
        while ($row = mysqli_fetch_assoc($all_categories_result)) {
            $all_category_ids[] = (int) $row['switchboard_cat_id']; // Ensure integer
        }
        
     	foreach ($all_category_ids as $cat_id) {
            $is_enabled = in_array($cat_id, $selected_categories) ? 1 : 0;
            
         	$cat_query = "INSERT INTO user_settings_search_categories 
                          (user_settings_switch_id, switchboard_cat_id, is_enabled) 
                          VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE 
                          is_enabled = VALUES(is_enabled),
                          updated_at = CURRENT_TIMESTAMP";
            
            $cat_stmt = mysqli_prepare($dbc, $cat_query);
            mysqli_stmt_bind_param($cat_stmt, 'iii', $switch_id, $cat_id, $is_enabled);
            
            if (!mysqli_stmt_execute($cat_stmt)) {
                throw new Exception("Failed to update category settings for category $cat_id");
            }
            mysqli_stmt_close($cat_stmt);
        }
        
      	mysqli_commit($dbc);
        
      	$_SESSION['search'] = $selection;
        $_SESSION['search_email'] = $search_email;
        $_SESSION['search_notes'] = $search_notes;
        $_SESSION['search_user_agency'] = $search_user_agency;
        $_SESSION['search_department'] = $search_department;
        $_SESSION['search_area_location'] = $search_area_location;
        $_SESSION['search_pronouns'] = $search_pronouns;
        $_SESSION['search_select'] = $emp_id;
        
    } catch (Exception $e) {
     	mysqli_rollback($dbc);
      	http_response_code(500);
        echo json_encode(['error' => 'Failed to update search settings']);
        exit();
    }
    
  	 mysqli_autocommit($dbc, true);
}

mysqli_close($dbc);
?>