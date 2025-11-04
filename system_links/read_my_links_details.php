<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isset($_POST['id']) && isset($_POST['id']) != "") {
	$my_link_id = mysqli_real_escape_string($dbc, strip_tags($_POST['id']));
	$query = "SELECT * FROM my_links WHERE my_link_id = '$my_link_id'";
    $result = mysqli_query($dbc, $query);
    confirmQuery($result);
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
mysqli_close($dbc);