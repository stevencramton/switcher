<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])) {
    header("Location:../../index.php?msg1");
    exit();
} else {
    if (isset($_POST['inquiry_id'])) {
        $poll_user = strip_tags($_SESSION['user']);
        $inquiry_id = strip_tags($_POST['inquiry_id']);
        $assignment_read = strip_tags($_POST['assignment_read']);

        $query = "UPDATE poll_assignment SET assignment_read = ? WHERE assignment_user = ? AND poll_id = ?";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, "isi", $assignment_read, $poll_user, $inquiry_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
    	} else {
      	}
		mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);