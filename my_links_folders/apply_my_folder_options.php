<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['emp_id'])) {
    $switch_id = strip_tags($_SESSION['switch_id']);
    $emp_id = strip_tags($_POST['emp_id']);
    
    $query = "UPDATE user_settings SET my_folder_select = ? WHERE user_settings_switch_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'ss', $emp_id, $switch_id);
        if (!mysqli_stmt_execute($stmt)) {
            die('Error failed');
        }
        mysqli_stmt_close($stmt);
    } else {
        die('Error failed');
    }
}
mysqli_close($dbc);
?>
