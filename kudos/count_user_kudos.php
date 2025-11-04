<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/counters.php';
include '../../templates/functions.php';

$kudos_total = countUserKudos();
$count = $kudos_total;
$data = "$count";
echo $data;
?>