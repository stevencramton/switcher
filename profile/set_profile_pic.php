<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['picPath'])) {
    $picPath = $_POST['picPath'];
    $imgSubstr = substr($picPath, 0, 15);

    if ($imgSubstr !== "img/profile_pic") {
        $response = "error";
    } else {
        $user = $_SESSION['user'];
		$query = "UPDATE users SET profile_pic = ? WHERE user = ?";
		$stmt = mysqli_prepare($dbc, $query);

        if ($stmt === false) {
       	 	die('MySQL prepare error.');
        }

        mysqli_stmt_bind_param($stmt, 'ss', $picPath, $user);
		$result = mysqli_stmt_execute($stmt);

        if ($result) {
            $_SESSION['profile_pic'] = $picPath;
            $response = "success";
        } else {
       	 	$response = "error";
            die('Update failed.');
        }
		mysqli_stmt_close($stmt);
    }
} else {
    $user = $_SESSION['user'];
    $default_pic = "img/profile_pic/avatar.png";
	$query = "UPDATE users SET profile_pic = ? WHERE user = ?";
	$stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
 	   die('MySQL prepare error.');
    }

	mysqli_stmt_bind_param($stmt, 'ss', $default_pic, $user);
	$result = mysqli_stmt_execute($stmt);

	if ($result) {
        $_SESSION['profile_pic'] = $default_pic;
        $response = "success";
    } else {
		$response = "error";
        die('Update failed.');
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);
echo $response;