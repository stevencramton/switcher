<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_products_manage')) {
    header("Location:../../../index.php?msg1");
	exit();
}

if (isset($_POST['prod_id'])) {
	$prod_id = strip_tags($_POST['prod_id']);
    $product_name = strip_tags($_POST['product_name']);
    $product_info = strip_tags($_POST['product_info']);
    $product_private = strip_tags($_POST['product_private']);
    $product_tags = strip_tags($_POST['product_tags']);
    $product_service_group = strip_tags($_POST['product_service_group'] ?? '');
    
    $query = "UPDATE service_products SET product_name = ?, product_info = ?, product_private = ?, product_tags = ?, product_service_group = ? WHERE product_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sssssi', $product_name, $product_info, $product_private, $product_tags, $product_service_group, $prod_id);
    
 	if (!mysqli_stmt_execute($stmt)) {
        $response = "failure";
        exit("Error.");
    } else {
        $response = "success";
    }
    
    echo json_encode($response);
 	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);