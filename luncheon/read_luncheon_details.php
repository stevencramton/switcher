<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['luncheon_id']) && $_POST['luncheon_id'] != "") {
	$user = $_SESSION['user'];
    $luncheon_id = $_POST['luncheon_id'];
    
	$query = "SELECT * FROM luncheon WHERE luncheon_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
   	 	mysqli_stmt_bind_param($stmt, 's', $luncheon_id);
        
		if (mysqli_stmt_execute($stmt)) {
         	$result = mysqli_stmt_get_result($stmt);
       	 	$response = array();
            
           	if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $response = $row;
                }
            } else {
                $response['status'] = 200;
                $response['message'] = "Data not found!";
            }
            
        	mysqli_stmt_close($stmt);
            
        } else {
       	 	$response['status'] = 500;
            $response['message'] = "Error executing statement.";
        }
        
    } else {
   	 	$response['status'] = 500;
        $response['message'] = "Error preparing statement.";
    }
    
	echo json_encode($response);

} else {
	$response['status'] = 400;
    $response['message'] = "Invalid Request!";
    
	echo json_encode($response);
}

mysqli_close($dbc);