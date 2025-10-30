<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['pk'])) {
    $id = mysqli_real_escape_string($dbc, $_POST['pk']);
    $value = mysqli_real_escape_string($dbc, $_POST['value']);

    if (empty($value)){
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid email";
    } else {
      	$query = "UPDATE users SET microsoft_email = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt_update, "si", $value, $id);
        $result_update = mysqli_stmt_execute($stmt_update);

        if ($result_update) {
          	echo "Microsoft Email Successfully Updated";
        } else {
          	error_log("Failed to update Microsoft email.");
            http_response_code(500);
            echo "Failed to update Microsoft email. Please try again later.";
        }
		mysqli_stmt_close($stmt_update);
    }
}
mysqli_close($dbc);
?>