<?php
ob_start();
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['pk'])) {
    $id = mysqli_real_escape_string($dbc, $_POST['pk']);

    if ($id !== $_SESSION['id']) {
        http_response_code(403);
        die("Unauthorized action.");
    }
    
 	$value = strip_tags($_POST['value']);
	$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

    if (empty($value)) {
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid display name";
    } else {
  	  	$query = "UPDATE users SET display_name = ? WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $query);

    	mysqli_stmt_bind_param($stmt, "si", $value, $id);
		$result = mysqli_stmt_execute($stmt);

     	if ($result) {
            unset($_SESSION['display_name']);
            $_SESSION['display_name'] = $value;
        } else {
        	error_log("Failed to update display name.");
            http_response_code(500);
            echo "Failed to update display name. Please try again later.";
        }
		mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);