<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['skill_id'])) {
	$skill_ids = explode(',', $_POST['skill_id']);
    $placeholders = implode(',', array_fill(0, count($skill_ids), '?'));
	$query = "DELETE FROM skills WHERE skill_id IN ($placeholders)";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        $types = str_repeat('i', count($skill_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$skill_ids);
        
        if (!mysqli_stmt_execute($stmt)) {
            exit();
        }
        
        mysqli_stmt_close($stmt);
    } else {
        exit();
    }
}
mysqli_close($dbc);