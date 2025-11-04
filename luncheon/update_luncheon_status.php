<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['selected_values'])) {
	$selected_values = $_POST['selected_values'];
 	if (!is_array($selected_values)) {
        $selected_values = explode(',', $selected_values);
    }
 	$placeholders = implode(',', array_fill(0, count($selected_values), '?'));
    $select_query = "SELECT luncheon_id, luncheon_status FROM luncheon WHERE luncheon_id IN ($placeholders)";
    
	if ($stmt_select = mysqli_prepare($dbc, $select_query)) {
 	 	$types = str_repeat("i", count($selected_values));
        mysqli_stmt_bind_param($stmt_select, $types, ...$selected_values);
        
     	if (mysqli_stmt_execute($stmt_select)) {
            $select_result = mysqli_stmt_get_result($stmt_select);
            
            while ($select_row = mysqli_fetch_array($select_result)) {
                $luncheon_id = $select_row['luncheon_id'];
                $luncheon_status = $select_row['luncheon_status'];
              	$new_status = ($luncheon_status == 0) ? 1 : 0;
              	$update_query = "UPDATE luncheon SET luncheon_status = ? WHERE luncheon_id = ?";
                $stmt_update = mysqli_prepare($dbc, $update_query);
              	mysqli_stmt_bind_param($stmt_update, "ii", $new_status, $luncheon_id);
                
             	if (!mysqli_stmt_execute($stmt_update)) {
                    echo "Error updating luncheon ID.";
                }
                
        		mysqli_stmt_close($stmt_update);
            }
        } else {
            echo "Error executing select statement.";
        }
        
		mysqli_stmt_close($stmt_select);
        
    } else {
        echo "Prepare statement error.";
    }
}

mysqli_close($dbc);