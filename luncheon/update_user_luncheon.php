<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['luncheon_id'])) {
	$luncheon_id = mysqli_real_escape_string($dbc, strip_tags($_POST['luncheon_id']));
    $luncheon_time_start = mysqli_real_escape_string($dbc, strip_tags($_POST['luncheon_time_start']));
    $luncheon_time_end = mysqli_real_escape_string($dbc, strip_tags($_POST['luncheon_time_end']));
    $highlighted_cells = mysqli_real_escape_string($dbc, strip_tags($_POST['highlighted_cells']));
    $unhighlighted_cells = mysqli_real_escape_string($dbc, strip_tags($_POST['unhighlighted_cells']));
	$highlighted_cells = explode(',', $highlighted_cells);
    $unhighlighted_cells = explode(',', $unhighlighted_cells);
	$highlighted_params = [];
    $unhighlighted_params = [];
	$query_parts = array();
	$query_parts[] = "luncheon_time_start = ?";
    $query_parts[] = "luncheon_time_end = ?";
    $bind_params = 'ss';

	if (!empty($highlighted_cells[0])) {
        foreach ($highlighted_cells as $key => $highlighted_cell) {
            $query_parts[] = "$highlighted_cell = ?";
            $highlighted_params[] = 1;
            $bind_params .= 'i';
        }
    }

    if (!empty($unhighlighted_cells[0])) {
        foreach ($unhighlighted_cells as $key => $unhighlighted_cell) {
            $query_parts[] = "$unhighlighted_cell = ?";
            $unhighlighted_params[] = 0;
            $bind_params .= 'i';
        }
    }

	if (!empty($query_parts)) {
        $query_string = implode(', ', $query_parts);
    } else {
        $query_string = '';
    }

	if (!empty($query_string)) {
        $query = "UPDATE luncheon SET $query_string WHERE luncheon_id = ?";
        
        if ($stmt = mysqli_prepare($dbc, $query)) {

         	$params = array_merge([$luncheon_time_start, $luncheon_time_end], $highlighted_params, $unhighlighted_params, [$luncheon_id]);
            mysqli_stmt_bind_param($stmt, $bind_params . 's', ...$params);
            
         	if (mysqli_stmt_execute($stmt)) {
                echo "Update successful.";
            } else {
                echo "Error executing query.";
            }
        	mysqli_stmt_close($stmt);
        } else {
            echo "Error preparing statement.";
        }
    } else {
        echo "No cells selected to update.";
    }

    mysqli_close($dbc);

}
?>