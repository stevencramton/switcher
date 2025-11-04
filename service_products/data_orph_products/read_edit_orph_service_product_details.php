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
	$query = "SELECT * FROM service_products WHERE product_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $prod_id);
    
	if (!mysqli_stmt_execute($stmt)) {
        exit("Error.");
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $response = array();
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $response = $row;
        }
    }
	echo json_encode($response);
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);