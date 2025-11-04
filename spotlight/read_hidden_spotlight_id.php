<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

$data = '';

if (isset($_SESSION['id'])) {
	$query = "SELECT * FROM spotlight_inquiry ORDER BY inquiry_id DESC LIMIT 1";
	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
 
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? ''));
			$data = '<input type="hidden" class="form-control" id="create_hidden_spotlight_id" value="'.$inquiry_id.'">';
		}
	} else {
		$data = '<input type="hidden" class="form-control" id="create_hidden_spotlight_id" value="0">';
	}
	
	echo $data;
}

mysqli_close($dbc);		
?>