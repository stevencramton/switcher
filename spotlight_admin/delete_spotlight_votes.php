<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_POST['id'])) {
	$spotlight_id = mysqli_real_escape_string($dbc, strip_tags($_POST['id']));
	$query = "DELETE FROM spotlight_ballot WHERE question_id in ($spotlight_id)";
    
	if (!$result = mysqli_query($dbc, $query)) {
		exit();
    }
	
    $query_two = "UPDATE spotlight_assignment SET assignment_read = 1 WHERE spotlight_id in ($spotlight_id)";
	$result_two = mysqli_query($dbc, $query_two);
}

mysqli_close($dbc);