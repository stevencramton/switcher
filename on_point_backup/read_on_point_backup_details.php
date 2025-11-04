<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_POST['on_point_backup_id']) && !empty($_POST['on_point_backup_id'])) {
	$on_point_backup_id = intval($_POST['on_point_backup_id']);
	$query = "SELECT * FROM on_point_backup WHERE on_point_backup_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
		mysqli_stmt_bind_param($stmt, 'i', $on_point_backup_id);
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
     	$response['status'] = 500;
        $response['message'] = "Failed to prepare the SQL statement!";
    }
	echo json_encode($response);

} else {
	$response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}

mysqli_close($dbc);