<?php
session_start();

if (!isset($_SESSION['id'])) {
	header("Location:../../index.php?msg1");
	exit();
} else {
	include '../../mysqli_connect.php';
	include '../../templates/functions.php';
	include '../../templates/counters.php';
	$msg_total = countUnreadMessages();
	$data = "$msg_total";
	echo $data;
}