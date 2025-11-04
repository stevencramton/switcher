<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_groups_manage')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_POST['serve_id'])) {
	$serve_id = strip_tags($_POST['serve_id']);
	$query = "SELECT * FROM service_groups WHERE group_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, "i", $serve_id);
        
		if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
      	  	$response = array();
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $response = $row;
                }
            }
        	mysqli_free_result($result);
        } else {
            $response = array();
        }
  	  	mysqli_stmt_close($stmt);
    } else {
        $response = array();
    }
	echo json_encode($response);
}
mysqli_close($dbc);