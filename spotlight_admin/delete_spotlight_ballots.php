<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id']) && isset($_POST['ballot_id'])) {
	$ballot_id = mysqli_real_escape_string($dbc, strip_tags($_POST['ballot_id']));
	$query = "DELETE FROM spotlight_ballot WHERE ballot_id in ($ballot_id)";
    
	if (!$result = mysqli_query($dbc, $query)) {
        exit();
    }
}

mysqli_close($dbc);