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
	$prod_ids = explode(',', $_POST['prod_id']);
    $placeholders = implode(',', array_fill(0, count($prod_ids), '?'));
	$query = "DELETE FROM service_products WHERE product_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);
	$types = str_repeat('i', count($prod_ids));
    mysqli_stmt_bind_param($stmt, $types, ...$prod_ids);

	if (!mysqli_stmt_execute($stmt)) {
        exit("Error.");
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);