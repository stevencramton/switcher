<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    header("Location:../../index.php?msg1");
}

if (isset($_POST['color_backup_data'])) {
	$color_backup_data = json_decode($_POST['color_backup_data'], 'associative');

	foreach($color_backup_data as $id_backup => $color_backup_array){
		$id_day_backup = explode("_", $id_backup);
		$id_backup = $id_day_backup[0];
  	
		foreach ($color_backup_array as $color_backup_day => $color_backup_rgb){
			$day_backup = mysqli_real_escape_string($dbc, strip_tags($color_backup_array['color_backup_day']));
			$color_backup = mysqli_real_escape_string($dbc, strip_tags($color_backup_array['color_backup_rgb']));
			$id_backup = (int) $id_backup;
			$query = "UPDATE on_point_backup SET on_point_backup_".$day_backup."_color = ? WHERE on_point_backup_id = ?";

			if ($stmt = mysqli_prepare($dbc, $query)) {
				mysqli_stmt_bind_param($stmt, "si", $color_backup, $id_backup);

				if (mysqli_stmt_execute($stmt)) {
			        $response = "success";
			    } else {
			        $response = "failure";
			    }
				mysqli_stmt_close($stmt);
			} else {
			    $response = "failure";
			}
		}
		echo json_encode($response);
	}
}
mysqli_close($dbc);