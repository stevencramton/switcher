<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}

if(isset($_GET["sort_product_order"])) {
	$id_ary = explode(",", strip_tags($_GET["sort_product_order"]));
	$query = "UPDATE service_products SET product_display_order = ? WHERE product_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    for($i = 0; $i < count($id_ary); $i++) {
        $product_display_order = $i;
        $product_id = strip_tags($id_ary[$i]);
		mysqli_stmt_bind_param($stmt, 'is', $product_display_order, $product_id);
        mysqli_stmt_execute($stmt) or die("database error.");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);