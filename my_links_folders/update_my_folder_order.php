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

if(isset($_GET["sort_order"])) {
    $id_ary = explode(",", $_GET["sort_order"]);
	$query = "UPDATE my_links_folders SET my_folder_display_order = ? WHERE my_folder_id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
		for ($i = 0; $i < count($id_ary); $i++) {
            $order_value = $i;
            $folder_id = $id_ary[$i];
            
            mysqli_stmt_bind_param($stmt, 'is', $order_value, $folder_id);
            mysqli_stmt_execute($stmt) or die("database error.");
        }
		mysqli_stmt_close($stmt);
    } else {
		die('Error failed');
    }
    mysqli_close($dbc);
}