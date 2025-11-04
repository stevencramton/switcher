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

if (isset($_POST['selected_values'])) {
    $ids = explode(',', $_POST['selected_values']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
	$query = "DELETE FROM shares WHERE share_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);

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