<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_products_manage')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['product_id'])) {
	$product_id = strip_tags($_POST['product_id']);
	$product_group_query = "SELECT * FROM service_products WHERE product_id = ?";
    $stmt = mysqli_prepare($dbc, $product_group_query);
    mysqli_stmt_bind_param($stmt, 's', $product_id);
    mysqli_stmt_execute($stmt);
    $product_group_result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($product_group_result);
	$product_group_name = strip_tags($row['product_name']);
    
	$query = "DELETE FROM service_products WHERE product_id = ? LIMIT 1";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 's', $product_id);
    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        $response = "failure";
        exit();
    } else {
        $response = "success";
    }

    echo json_encode($response);

 	$audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
	$switch_id = strip_tags($_SESSION['switch_id']);
	    date_default_timezone_set("America/New_York");
	    $audit_date = date('m-d-Y g:i A');
	    $audit_action_tag = '<span class="badge bg-audit-hot shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i> Deleted Service Group Product</span>';
	    $audit_action = 'Deleted Service Group Product';
	    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
	    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
	    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
	    $audit_detailed_action = 'On ' . $audit_date . ' ' . $audit_first_name . ' ' . $audit_last_name . ' deleted Service Group Product named: ' . $product_group_name;
	    $product_group_name = (strlen($product_group_name) > 30) ? substr($product_group_name, 0, 30) . '...' : $product_group_name;

		$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	    $audit_stmt = mysqli_prepare($dbc, $audit_query);
	    mysqli_stmt_bind_param($audit_stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $product_group_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
	    mysqli_stmt_execute($audit_stmt);
	    confirmQuery($audit_stmt);

	}

mysqli_close($dbc);