<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')){
    header("Location:index.php?msg1");
    exit();
}

if (isset($_POST['selected_values'])) {
	$share_ids = array_map('intval', explode(',', $_POST['selected_values']));
    $server_name = mysqli_real_escape_string($dbc, $_POST['server_name']);
	$placeholders = implode(',', array_fill(0, count($share_ids), '?'));
	$map_query = "SELECT * FROM shares WHERE share_id IN ($placeholders)";
    $map_stmt = mysqli_prepare($dbc, $map_query);
	$types = str_repeat('i', count($share_ids));
    mysqli_stmt_bind_param($map_stmt, $types, ...$share_ids);
    mysqli_stmt_execute($map_stmt);
    $map_result = mysqli_stmt_get_result($map_stmt);

    while ($map_row = mysqli_fetch_array($map_result, MYSQLI_ASSOC)) {
   	 	$share_id = $map_row['share_id'];
        $current_server_name = $map_row['share_server'];
        $share_drive_name = $map_row['share_drive_name'];

        if ($server_name == $current_server_name) {
			$update_query = "UPDATE shares SET share_server = ? WHERE share_id = ?";
            $update_stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'si', $server_name, $share_id);

        } else {
            $new_mapping = "//" . $server_name . ".plymouth.edu/" . $share_drive_name;
			$update_query = "UPDATE shares SET share_server = ?, share_mapping = ? WHERE share_id = ?";
            $update_stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'ssi', $server_name, $new_mapping, $share_id);
        }

        if (!mysqli_stmt_execute($update_stmt)) {
            echo("Error description.");
        }
		mysqli_stmt_close($update_stmt);
    }
	mysqli_stmt_close($map_stmt);
}
mysqli_close($dbc);