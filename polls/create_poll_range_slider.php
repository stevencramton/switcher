<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!empty($_POST["response_answer"])) {
	$query = "INSERT INTO poll_response (question_id, response_type, response_answer, response_info) VALUES (?, ?, ?, ?)";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
        $response_type = 'range_slider';

		foreach ($_POST["response_answer"] as $key => $value) {
            $question_id = $_POST['question_id'][$key];
            $response_answer = $_POST['response_answer'][$key];
            $response_info = $_POST['response_info'][$key];
            
			mysqli_stmt_bind_param($stmt, 'ssss', $question_id, $response_type, $response_answer, $response_info);
            
			if (!mysqli_stmt_execute($stmt)) {
                echo "Error.";
                break;
            }
        }
   	 	mysqli_stmt_close($stmt);
    } else {
        echo "Error.";
    }
}

mysqli_close($dbc);