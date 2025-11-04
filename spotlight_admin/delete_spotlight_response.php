<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_POST['response_id'])) {
	$response_id = mysqli_real_escape_string($dbc, strip_tags($_POST['response_id']));
	$query = "DELETE FROM spotlight_response WHERE response_id = '$response_id' LIMIT 1";
    $result = mysqli_query($dbc, $query); 
     
	if (!$result = mysqli_query($dbc, $query)) {
		$response = "failure";
		exit();
		  
	} else {
  	  $response = "success";
	}
	
	echo json_encode($response);
}

mysqli_close($dbc);