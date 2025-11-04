<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if(isset($_SESSION['user'])){
	$user = $_SESSION['user'];
	$query = "SELECT xp FROM user_xp WHERE user = ? AND user != 'infotech'";
	$stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
		die('MySQL prepare error.');
    }
	mysqli_stmt_bind_param($stmt, 's', $user);
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $xp);
	mysqli_stmt_fetch($stmt);

	if(isset($xp)){
        $data = '<span>' . htmlspecialchars($xp) . ' XP</span>';
    } else {
        $data = '<span>0 XP</span>';
    }
	mysqli_stmt_close($stmt);
	echo $data;
}
mysqli_close($dbc);