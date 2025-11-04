<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['id'])) {
	$luncheon_sender = filter_var($_POST['luncheon_sender'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $switch_id = filter_var($_POST['switch_id'], FILTER_SANITIZE_NUMBER_INT);
    $luncheon_time_start = filter_var($_POST['luncheon_time_start'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $luncheon_time_end = filter_var($_POST['luncheon_time_end'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $luncheon_color = filter_var($_POST['luncheon_color'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $luncheon_status = filter_var($_POST['luncheon_status'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $highlighted_cells = filter_var($_POST['highlighted_cells'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $unhighlighted_cells = filter_var($_POST['unhighlighted_cells'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$highlighted_cells_array = !empty($highlighted_cells) ? explode(',', $highlighted_cells) : [];
    $unhighlighted_cells_array = !empty($unhighlighted_cells) ? explode(',', $unhighlighted_cells) : [];
	$columns = ['luncheon_sender', 'switch_id', 'luncheon_time_start', 'luncheon_time_end', 'luncheon_color', 'luncheon_status'];
    $values = [$luncheon_sender, $switch_id, $luncheon_time_start, $luncheon_time_end, $luncheon_color, $luncheon_status];
    $placeholders = ['?', '?', '?', '?', '?', '?'];

	function sanitize_column($column) {
   	 	return preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    }

	foreach ($highlighted_cells_array as $cell) {
        $sanitized_cell = sanitize_column($cell);
        if ($sanitized_cell && !in_array($sanitized_cell, $columns)) {
            $columns[] = $sanitized_cell;
            $values[] = 1;
            $placeholders[] = '?';
        } else {
            error_log('Invalid column name or duplicate: ' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8'));
        }
    }

	foreach ($unhighlighted_cells_array as $cell) {
        $sanitized_cell = sanitize_column($cell);
        if ($sanitized_cell && !in_array($sanitized_cell, $columns)) {
            $columns[] = $sanitized_cell;
            $values[] = 0;
            $placeholders[] = '?';
        } else {
            error_log('Invalid column name or duplicate: ' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8'));
        }
    }

	$sanitized_columns = array_map('sanitize_column', $columns);
	$luncheon_query = "INSERT INTO luncheon (" . implode(', ', $sanitized_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

	if ($stmt = mysqli_prepare($dbc, $luncheon_query)) {
   	 	$types = '';
        foreach ($values as $value) {
        	$types .= is_int($value) ? 'i' : 's';
        }

		mysqli_stmt_bind_param($stmt, $types, ...$values);

		if (!mysqli_stmt_execute($stmt)) {
            error_log('QUERY EXECUTION FAILED: ' . mysqli_stmt_error($stmt));
            die('An error occurred. Please try again later.');
        }

        mysqli_stmt_close($stmt);
    } else {
        error_log('QUERY PREPARATION FAILED: ' . mysqli_error($dbc));
        die('An error occurred. Please try again later.');
    }

    mysqli_close($dbc);

} else {
    http_response_code(403);
}