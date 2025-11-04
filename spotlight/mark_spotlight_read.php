<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
	header("Location:../../index.php?msg1");
	exit();
}

if (!isset($_SESSION['id'])) {
	header("Location:../../index.php?msg1");
	exit();
} else {
	
	if (isset($_POST['inquiry_id'])) {
		$spotlight_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$assignment_read = mysqli_real_escape_string($dbc, strip_tags($_POST['assignment_read']));
		$query = "UPDATE spotlight_assignment SET assignment_read = 0 WHERE assignment_user = '$spotlight_user' AND spotlight_id = '$inquiry_id'";
		$result = mysqli_query($dbc, $query);
		confirmQuery($result);
	}
}

mysqli_close($dbc);