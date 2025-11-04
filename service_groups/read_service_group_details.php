<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['group_id']) && $_POST['group_id'] !== "") {
	$group_id = mysqli_real_escape_string($dbc, strip_tags($_POST['group_id']));
	$query = "SELECT * FROM service_groups WHERE group_id = ?";
 	$response = array();
	
	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, "i", $group_id);
        
		if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
     	   	if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $response = $row;
            } else {
                $response['status'] = 200;
                $response['message'] = "Data not found!";
            }
        } else {
            $response['status'] = 200;
            $response['message'] = "Error executing query!";
        }
		mysqli_stmt_close($stmt);
    } else {
        $response['status'] = 200;
        $response['message'] = "Error preparing statement!";
    }
	echo json_encode($response);
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}
mysqli_close($dbc);