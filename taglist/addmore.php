<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (!empty($_POST["question_title"])) {
	$placeholders = array();
    $params = array();
    
    foreach ($_POST["question_title"] as $key => $value) {
        $question_title = $_POST['question_title'][$key];
        $question_note = $_POST['question_note'][$key];
        $answer_title = $_POST['answer_title'][$key];
        $answer_note = $_POST['answer_note'][$key];
  		$placeholders[] = "(?, ?, ?, ?)";
     	$params[] = $question_title;
        $params[] = $question_note;
        $params[] = $answer_title;
        $params[] = $answer_note;
    }
    
	$query = "INSERT INTO tagslist (question_title, question_note, answer_title, answer_note) VALUES " . implode(', ', $placeholders);
	$stmt = mysqli_prepare($dbc, $query);
    
    if ($stmt) {
   	 	$bindTypes = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $bindTypes, ...$params);
		if (mysqli_stmt_execute($stmt)) {
            echo "Records inserted successfully.";
        } else {
            echo "Error executing statement.";
        }
    	mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
}
mysqli_close($dbc);