<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isset($_POST['id'])) {
    $delete_my_links = strip_tags($_SESSION['user']);
    $id = strip_tags($_POST['id']);
  	$query = "DELETE FROM my_links WHERE my_link_created_by = ? AND my_link_id = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "ss", $delete_my_links, $id);
	mysqli_stmt_execute($stmt);
	confirmQuery(mysqli_stmt_affected_rows($stmt));
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);