<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) {
	$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
	$inquiry_name = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_name']));
	$inquiry_info = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_info']));
	$inquiry_question = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_question']));
	$inquiry_status = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_status']));
	$inquiry_image = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_image']));
	
    $query = "UPDATE spotlight_inquiry SET inquiry_name = '$inquiry_name', inquiry_info = '$inquiry_info', inquiry_question = '$inquiry_question', inquiry_status = '$inquiry_status', inquiry_image = '$inquiry_image' WHERE inquiry_id = '$inquiry_id'";

	$result = mysqli_query($dbc, $query);

}

mysqli_close($dbc);

?>