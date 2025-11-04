<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("Invalid request.");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {
    $id_ary = explode(",", $_GET["sort_order"]);
 	$query = "UPDATE info SET info_display_order = ? WHERE info_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    
    if ($stmt) {
        for ($i = 0; $i < count($id_ary); $i++) {
            $info_display_order = $i;
            $info_id = $id_ary[$i];
            
            mysqli_stmt_bind_param($stmt, 'ii', $info_display_order, $info_id);
            mysqli_stmt_execute($stmt) or die("database error.");
        }
        
        mysqli_stmt_close($stmt);
    } else {
        die("Prepare failed.");
    }
}
mysqli_close($dbc);