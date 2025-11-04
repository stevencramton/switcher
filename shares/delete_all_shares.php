<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')){
    header("Location:index.php?msg1");
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $query = "TRUNCATE TABLE shares";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {

        } else {
            echo "Error executing statement.";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
}

mysqli_close($dbc);