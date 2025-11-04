<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['info_id']) && $_POST['info_id'] != "") {
	$info_id = strip_tags($_POST['info_id']);

    $query = "
		SELECT 
		    i.*, 
		    GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name, ' (', ic.info_date_confirm, ' ', ic.info_time_confirm, ')') SEPARATOR '\n') AS confirmed_users
		FROM info i
		LEFT JOIN info_confirm ic ON i.info_id = ic.info_id
		LEFT JOIN users u ON ic.username = u.user
		WHERE i.info_id = ?
		GROUP BY i.info_id
	";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, "i", $info_id);
    	mysqli_stmt_execute($stmt);
    	$result = mysqli_stmt_get_result($stmt);
	    $response = array();

	    if (mysqli_num_rows($result) > 0) {
	        while ($row = mysqli_fetch_assoc($result)) {
	            $row['confirmed_users'] = !is_null($row['confirmed_users']) ? nl2br($row['confirmed_users']) : '';
	            $response = $row;
	        }
	    } else {
	        $response['status'] = 200;
	        $response['message'] = "Data not found!";
	    }
    	mysqli_stmt_close($stmt);
	} else {
	    $response['status'] = 200;
	    $response['message'] = "Database query failed!";
	}
	echo json_encode($response);
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
}
mysqli_close($dbc);