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
	$prod_ids = array_map('strip_tags', explode(',', $_POST['prod_id']));
    $product_name = strip_tags($_POST['product_name']);
    $product_info = strip_tags($_POST['product_info']);
    $product_tags = strip_tags($_POST['product_tags']);
    $product_private = strip_tags($_POST['product_private']);
	$placeholders = implode(',', array_fill(0, count($prod_ids), '?'));

	$query = "UPDATE service_products SET product_name = ?, product_info = ?, product_private = ?, product_tags = ? WHERE product_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
      	$types = str_repeat('i', count($prod_ids));
        $types = 'ssss' . $types;
		$params = array_merge([$types, $product_name, $product_info, $product_private, $product_tags], $prod_ids);
		mysqli_stmt_bind_param($stmt, ...$params);

     	if (!mysqli_stmt_execute($stmt)) {
            $response = "failure";
            exit();
        } else {
            $response = "success";
        }
		echo json_encode($response);
        mysqli_stmt_close($stmt);
    } else {
        $response = "failure";
        echo json_encode($response);
    }
}
mysqli_close($dbc);