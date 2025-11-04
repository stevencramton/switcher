<?php
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isset($_POST['selected_values']) && $_POST['selected_values'] !== "") {
	$ids = mysqli_real_escape_string($dbc, $_POST['selected_values']);

	$query = "SELECT spotlight_nominee.*, users.first_name, users.last_name, users.profile_pic, users.display_name, users.display_title, users.display_agency, users.user_location, users.pronouns
              FROM spotlight_nominee 
              JOIN users ON spotlight_nominee.assignment_user = users.user 
              WHERE spotlight_nominee.assignment_id = '$ids'";

    $result = mysqli_query($dbc, $query);
    confirmQuery($result);

    $response = array();
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $response = $row;
    } else {
        $response['status'] = "Data not found!";
        $response['message'] = "Data not found!";
    }
	echo json_encode($response);
    
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}

mysqli_close($dbc); 
?>