<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')) {
	header("Location:../../index.php?msg1");
	exit();
}

if (!empty($_POST["response_answer"])) {
    $parts = array();

    foreach ($_POST["response_answer"] as $key => $value) {
        $question_id = mysqli_real_escape_string($dbc, strip_tags($_POST['question_id'][$key]));
        $response_type = mysqli_real_escape_string($dbc, strip_tags($_POST['response_type'][$key]));
        $response_key = isset($_POST['response_key'][$key]) ? 1 : 0;
        $response_answer = mysqli_real_escape_string($dbc, strip_tags($_POST['response_answer'][$key]));
        $response_info = mysqli_real_escape_string($dbc, strip_tags($_POST['response_info'][$key]));

        $parts[] = "('$question_id', '$response_type', '$response_key', '$response_answer', '$response_info')";
    }

    $query = "INSERT INTO spotlight_response (question_id, response_type, response_key, response_answer, response_info) VALUES " . implode(', ', $parts);
    echo "Query: $query";

    if (!$result = mysqli_query($dbc, $query)) {
        echo("Error description: " . $dbc->error);
    }
}
?>