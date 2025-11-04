<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (!isset($_SESSION['id'])) {
	header("Location:../../index.php?msg1");
	exit();
} else {
	if (isset($_POST['inquiry_id']) && isset($_POST['inquiry_id']) != "") {
   	 	$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
    	$result = mysqli_query($dbc, $query);
    	$response = array();
    
		if (mysqli_num_rows($result) > 0) {
        
			while ($row = mysqli_fetch_assoc($result)) {
            	$response = $row;
        	}
		
    	} else {
        	$response['status'] = 200;
        	$response['message'] = "Data not found!";
   	 	}
		
    	echo json_encode($response);

	} else {
    	$response['status'] = 200;
    	$response['message'] = "Invalid Request!";
	}
}

mysqli_close($dbc);
?>