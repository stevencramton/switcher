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

if (isset($_POST['product_name'])) {
	$service_product_name = strip_tags($_POST['product_name']);
    $service_product_info = strip_tags($_POST['product_info']);
	$affiliation_unh = strip_tags($_POST['affiliation_unh']);
    $affiliation_psu = strip_tags($_POST['affiliation_psu']);
    $affiliation_ksc = strip_tags($_POST['affiliation_ksc']);
  	$affiliation_usnh = strip_tags($_POST['affiliation_usnh']);
    $affiliation_unh_manch = strip_tags($_POST['affiliation_unh_manch']);
    $affiliation_unh_law = strip_tags($_POST['affiliation_unh_law']);
    $service_product_tags = strip_tags($_POST['product_tags']);
    $product_created_by = strip_tags($_SESSION['display_name']);
    date_default_timezone_set("America/New_York");
    $product_date_created = date('m-d-Y g:i A');
    $service_product_service_group = strip_tags($_POST['product_service_group']);
    $product_private = strip_tags($_POST['product_private']);
	$product_steps = $_POST['product_steps'];

    $query = "INSERT INTO service_products (product_name, product_info, product_private, product_steps, affiliation_unh, affiliation_psu, affiliation_ksc, affiliation_usnh, affiliation_unh_manch, affiliation_unh_law, product_tags, product_created_by, product_date_created, product_service_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssssssssssssss', $service_product_name, $service_product_info, $product_private, $product_steps, $affiliation_unh, $affiliation_psu, $affiliation_ksc, $affiliation_usnh, $affiliation_unh_manch, $affiliation_unh_law, $service_product_tags, $product_created_by, $product_date_created, $service_product_service_group);
    mysqli_stmt_execute($stmt);
    confirmQuery($stmt);

    $service_group_query = "SELECT * FROM service_groups WHERE group_id = ?";
    $service_group_stmt = mysqli_prepare($dbc, $service_group_query);
    mysqli_stmt_bind_param($service_group_stmt, 's', $service_product_service_group);
    mysqli_stmt_execute($service_group_stmt);
    $service_group_result = mysqli_stmt_get_result($service_group_stmt);
    $row = mysqli_fetch_array($service_group_result);
  	$service_group_name = strip_tags($row['group_name']);

    $audit_user = strip_tags($_SESSION['user']);
    $audit_first_name = strip_tags($_SESSION['first_name']);
    $audit_last_name = strip_tags($_SESSION['last_name']);
    $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
    $switch_id = strip_tags($_SESSION['switch_id']);
    date_default_timezone_set("America/New_York");
    $audit_date = date('m-d-Y g:i A');
    $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-solid fa-circle-check"></i> Created Service Group Product</span>';
    $audit_action = 'Created Service Group Product' . ' ' . $service_product_name;
    $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
    $audit_source = strip_tags($_SERVER['REQUEST_URI']);
    $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
    $audit_detailed_action = 'On' . ' ' . $audit_date . ' ' . $audit_first_name . ' ' . $audit_last_name . ' ' . 'created a new Service Group Product:' . ' ' . $service_product_name . ' ' . 'and assigned it to Service Group:' . ' ' . $service_group_name;
    $service_product_name = (strlen($service_product_name) > 30) ? substr($service_product_name, 0, 30).'...' : $service_product_name;

  	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $audit_stmt = mysqli_prepare($dbc, $audit_query);
    mysqli_stmt_bind_param($audit_stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $service_product_name, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
    mysqli_stmt_execute($audit_stmt);
    confirmQuery($audit_stmt);

}
mysqli_close($dbc);