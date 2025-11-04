<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_categories')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_GET["sort_order"])) {
    $id_ary = explode(",", $_GET["sort_order"]);
    $total_categories = count($id_ary);

	$query = "UPDATE switchboard_categories SET switchboard_cat_display_order = ? WHERE switchboard_cat_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
        for ($i = 0; $i < $total_categories; $i++) {
            $id = intval($id_ary[$i]);
            mysqli_stmt_bind_param($stmt, 'ii', $i, $id);
            mysqli_stmt_execute($stmt) or die("database error.");
        }
        mysqli_stmt_close($stmt);

		$first_cat_id = $id_ary[0];
        $name_query = "SELECT switchboard_cat_name FROM switchboard_categories WHERE switchboard_cat_id = ?";
        $name_stmt = mysqli_prepare($dbc, $name_query);
        mysqli_stmt_bind_param($name_stmt, "i", $first_cat_id);
        mysqli_stmt_execute($name_stmt);
        mysqli_stmt_bind_result($name_stmt, $first_cat_name);
        mysqli_stmt_fetch($name_stmt);
        mysqli_stmt_close($name_stmt);

		$audit_user = strip_tags($_SESSION['user']);
        $audit_first_name = strip_tags($_SESSION['first_name']);
        $audit_last_name = strip_tags($_SESSION['last_name']);
        $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
        $switch_id = strip_tags($_SESSION['switch_id']);
        date_default_timezone_set("America/New_York");
        $audit_date = date('m-d-Y g:i A');
        $audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fas fa-info-circle"></i> Updated Contact Category Order</span>';
        $audit_action = 'Updated Category Display Order';
        $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
        $audit_source = strip_tags($_SERVER['REQUEST_URI']);
        $audit_source_path = strtok($audit_source, '?');
        $audit_domain = strip_tags($_SERVER['SERVER_NAME']);
        $audit_detailed_action = 'On ' . $audit_date . ' ' . $audit_first_name . ' ' . $audit_last_name . ' updated the display order of ' . $total_categories . ' contact category(ies), starting with category "' . $first_cat_name . '" (ID: ' . $first_cat_id . ')';

        $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $first_cat_name, $audit_detailed_action, $audit_ip, $audit_source_path, $audit_domain);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) <= 0) {
            die('Query Failed');
        }
		mysqli_stmt_close($stmt);
		
    } else {
        die("database error.");
    }
}

mysqli_close($dbc);