<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['picPath'])) {
    $pic_path = $_POST['picPath'];

	if (!preg_match('/^img\/profile_pic\/.+$/', $pic_path)) {
        http_response_code(400);
        die("Invalid input");
    }

	$pic_path = mysqli_real_escape_string($dbc, $pic_path);
	$user_session = $_SESSION['user'];
	$file_name = basename($pic_path);

	if (!str_starts_with($file_name, $user_session . '_')) {
        $response = "unauthorized";
    } else {
		$base_dir = realpath('../../img/profile_pic');
        $full_path = realpath("../../" . $pic_path);

		if ($full_path && strpos($full_path, $base_dir) === 0 && is_file($full_path)) {
        	if (!unlink($full_path)) {
                $response = "failure";
            } else {
                $response = "success";
            }
        } else {
            $response = "file_not_found";
        }
    }
	echo json_encode($response);
}
mysqli_close($dbc);