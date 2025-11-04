<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
} 

if(isset($_POST['selected_values'])){
	$spotlight_ids = mysqli_real_escape_string($dbc, strip_tags($_POST['selected_values']));
	$query = "UPDATE spotlight_inquiry SET inquiry_status = 'Active' WHERE inquiry_id IN ($spotlight_ids)";
	$result = mysqli_query($dbc, $query);
} else {
	$post_id = mysqli_real_escape_string($dbc, strip_tags($_POST['id']));
	$query = "UPDATE spotlight_inquiry SET inquiry_status = 'Active' WHERE inquiry_id = '$spotlight_id'";
	$result = mysqli_query($dbc, $query);
}
 
mysqli_close($dbc);