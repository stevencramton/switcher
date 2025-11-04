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
    
   	$query = "DELETE FROM spotlight_inquiry WHERE inquiry_id in ($spotlight_id)";
    if (!$result = mysqli_query($dbc, $query)) {
        exit();
    }
	
   	$query_two = "DELETE FROM spotlight_response WHERE question_id in ($spotlight_id)";
    if (!$result_two = mysqli_query($dbc, $query_two)) {
        exit();
    }
	
   	$query_three = "DELETE FROM spotlight_ballot WHERE question_id in ($spotlight_id)";
    if (!$result_three = mysqli_query($dbc, $query_three)) {
        exit();
    } 
	
  	$query_four = "DELETE FROM spotlight_assignment WHERE spotlight_id in ($spotlight_id)";
    if (!$result_four = mysqli_query($dbc, $query_four)) {
        exit();
    }
    
 	$query_five = "DELETE FROM spotlight_nominee WHERE question_id in ($spotlight_id)";
    if (!$result_five = mysqli_query($dbc, $query_five)) {
        exit();
    }
}

mysqli_close($dbc);
?>