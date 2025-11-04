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
    $value = mysqli_real_escape_string($dbc, $_POST['value']);

	if (strlen($value) !== 12 || !preg_match('/^\d{3}-\d{3}-\d{4}$/', $value)) {
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Cell Phone # must be entered in XXX-XXX-XXXX format.";
    } else {
    	$query = "UPDATE users SET cell = ? WHERE switch_id = ?";
        $stmt = mysqli_prepare($dbc, $query);

     	mysqli_stmt_bind_param($stmt, "ss", $value, $id);
		$result = mysqli_stmt_execute($stmt);

        if ($result) {
        	$help_query = "SELECT last_name FROM users WHERE switch_id = ?";
            $stmt_help = mysqli_prepare($dbc, $help_query);

       	 	mysqli_stmt_bind_param($stmt_help, "s", $id);
			mysqli_stmt_execute($stmt_help);
			mysqli_stmt_bind_result($stmt_help, $help_name);
			mysqli_stmt_fetch($stmt_help);
			mysqli_stmt_close($stmt_help);

          	$change_query = "UPDATE switchboard_contacts SET cell = ? WHERE last_name = ?";
            $stmt_change = mysqli_prepare($dbc, $change_query);

         	mysqli_stmt_bind_param($stmt_change, "ss", $value, $help_name);
			mysqli_stmt_execute($stmt_change);
			mysqli_stmt_close($stmt_change);

            echo "Cell Phone # Successfully Updated";
        } else {
            error_log("Failed to update cell phone #.");
            http_response_code(500);
            echo "Failed to update cell phone #. Please try again later.";
        }

        mysqli_stmt_close($stmt);
    }
}

mysqli_close($dbc);
?>