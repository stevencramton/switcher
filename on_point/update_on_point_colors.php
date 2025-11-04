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

if(isset($_POST['color_data'])){
	$color_data = json_decode($_POST['color_data'], 'associative');

	foreach($color_data as $id => $color_array){
		$id_day = explode("_", $id);
		$id = $id_day[0];

		foreach ($color_array as $color_day => $color_rgb){
			$day = mysqli_real_escape_string($dbc, strip_tags($color_array['color_day']));
			$color = mysqli_real_escape_string($dbc, strip_tags($color_array['color_rgb']));
			$color_text = mysqli_real_escape_string($dbc, strip_tags($color_array['color_text']));
    		$stmt = mysqli_prepare($dbc, "UPDATE on_point SET on_point_" . $day . "_color = ?, on_point_" . $day . "_text_color = ? WHERE on_point_id = ?");
        
			if ($stmt) {
				mysqli_stmt_bind_param($stmt, "ssi", $color, $color_text, $id);
            
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