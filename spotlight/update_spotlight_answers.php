<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_POST['response_answer'])) {
	
	foreach ($_POST["response_answer"] as $key => $value) {
    	$response_id = mysqli_real_escape_string($dbc, $_POST['response_id'][$key]);
        $question_id = mysqli_real_escape_string($dbc, $_POST['question_id'][$key]);
		$response_answer = mysqli_real_escape_string($dbc, $_POST['response_answer'][$key]); 
        $response_info = mysqli_real_escape_string($dbc, $_POST['response_info'][$key]);
        $check_query = "SELECT response_id FROM spotlight_response WHERE response_id = '$response_id'";
        $check_result = mysqli_query($dbc, $check_query);
        
        if(mysqli_num_rows($check_result) > 0){ 
            
			$update_query = "UPDATE spotlight_response SET question_id = '$question_id', response_answer = '$response_answer', response_info = '$response_info' WHERE response_id = '$response_id'";
            
			if (!$result = mysqli_query($dbc, $update_query)) {
                echo("Error description.");
            } 
			
        } else {
            
			$insert_query = "INSERT INTO spotlight_response (question_id, response_answer, response_info) VALUES ('$question_id', '$response_answer', '$response_info')";
            
			if (!$result = mysqli_query($dbc, $insert_query)) {
                echo("Error description.");
            }
			
        }
    }
}

mysqli_close($dbc);

?>