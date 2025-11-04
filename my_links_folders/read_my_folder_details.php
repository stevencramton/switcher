<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['my_folder_id']) && !empty($_POST['my_folder_id'])) {
	$my_folder_id = strip_tags($_POST['my_folder_id']);
	$query = "SELECT * FROM my_links_folders WHERE my_folder_id = ?";
	
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $my_folder_id);
        mysqli_stmt_execute($stmt);
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
		die('Error failed');
    }
	echo json_encode($response);
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}
mysqli_close($dbc);