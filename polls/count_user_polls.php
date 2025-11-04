<?php
session_start();

if (!isset($_SESSION['id'])) {
	header("Location:../../index.php?msg1");
	exit();
} else {

include '../../mysqli_connect.php';
include '../../templates/counters.php';
include '../../templates/functions.php';

$kudos_total = countUserKudos();
$count = $kudos_total;
$data = "$count";

echo $data;
}