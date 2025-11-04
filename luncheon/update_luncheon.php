<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['luncheon_id'])) {
    function safe_get_post($dbc, $key) {
        if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
            return null;
        }
        return mysqli_real_escape_string($dbc, strip_tags($_POST[$key]));
    }
    
    $luncheon_id = safe_get_post($dbc, 'luncheon_id');
    $luncheon_color = safe_get_post($dbc, 'luncheon_color');
    $luncheon_status = safe_get_post($dbc, 'luncheon_status');
    $luncheon_time_start = safe_get_post($dbc, 'luncheon_time_start');
    $luncheon_time_end = safe_get_post($dbc, 'luncheon_time_end');
    $highlighted_cells = safe_get_post($dbc, 'highlighted_cells');
    $unhighlighted_cells = safe_get_post($dbc, 'unhighlighted_cells');
    
    $highlighted_cells = $highlighted_cells ? explode(',', $highlighted_cells) : [];
    $unhighlighted_cells = $unhighlighted_cells ? explode(',', $unhighlighted_cells) : [];
    
    $highlighted_query_string = !empty($highlighted_cells) ? implode(' = 1, ', $highlighted_cells) . ' = 1' : '';
    $unhighlighted_query_string = !empty($unhighlighted_cells) ? implode(' = 0, ', $unhighlighted_cells) . ' = 0' : '';
    
    // Build query dynamically based on what fields have values
    $query_parts = [];
    $params = [];
    $types = '';
    
    if ($luncheon_color !== null) {
        $query_parts[] = "luncheon_color = ?";
        $params[] = $luncheon_color;
        $types .= 's';
    }
    
    if ($luncheon_status !== null) {
        $query_parts[] = "luncheon_status = ?";
        $params[] = $luncheon_status;
        $types .= 's';
    }
    
    if ($luncheon_time_start !== null) {
        $query_parts[] = "luncheon_time_start = ?";
        $params[] = $luncheon_time_start;
        $types .= 's';
    }
    
    if ($luncheon_time_end !== null) {
        $query_parts[] = "luncheon_time_end = ?";
        $params[] = $luncheon_time_end;
        $types .= 's';
    }
    
    if ($highlighted_query_string) {
        $query_parts[] = $highlighted_query_string;
    }
    
    if ($unhighlighted_query_string) {
        $query_parts[] = $unhighlighted_query_string;
    }
    
    if (empty($query_parts)) {
        echo "No fields to update.";
        mysqli_close($dbc);
        exit;
    }
    
    $query_string = implode(', ', $query_parts);
    $query = "UPDATE luncheon SET $query_string WHERE luncheon_id = ?";
    
    // Add luncheon_id to params
    $params[] = $luncheon_id;
    $types .= 's';
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Update successful.";
        } else {
            echo "Error executing query: " . mysqli_stmt_error($stmt);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($dbc);
    }
}

mysqli_close($dbc);
?>