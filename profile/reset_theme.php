<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_POST['data_theme'])) {
 	$id = $_SESSION['id'];
	$data_theme = mysqli_real_escape_string($dbc, strip_tags($_POST['data_theme']));
    $data_bg = mysqli_real_escape_string($dbc, strip_tags($_POST['data_bg']));
    $theme_bg = mysqli_real_escape_string($dbc, strip_tags($_POST['theme_bg']));
    $data_nav = mysqli_real_escape_string($dbc, strip_tags($_POST['theme_nav']));

	$user_query = "UPDATE users SET data_theme = ?, theme_bg = ?, data_bg = ?, data_nav = ?, data_right = 'default' WHERE id = ?";
	$stmt = mysqli_prepare($dbc, $user_query);

    if ($stmt === false) {
   	 	die('MySQL prepare error.');
    }

	mysqli_stmt_bind_param($stmt, 'ssssi', $data_theme, $theme_bg, $data_bg, $data_nav, $id);
	$user_result = mysqli_stmt_execute($stmt);

	if ($user_result) {
        $_SESSION['data_theme'] = $data_theme;
        $_SESSION['theme_bg'] = $theme_bg;
        $_SESSION['data_bg'] = $data_bg;
        $_SESSION['data_nav'] = $data_nav;
    } else {
   	 	die('Update failed.');
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);