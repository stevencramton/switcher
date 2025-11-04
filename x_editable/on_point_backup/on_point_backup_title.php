<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_admin')){
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['pk'])) {
	$on_point_backup_title_id = $_POST['pk'];
    $value = $_POST['value'];
	if (empty($value)){
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid title";
    } else {
      	$query = "UPDATE on_point_backup_title SET on_point_backup_title = ? WHERE on_point_backup_title_id = ?";
        $stmt = mysqli_prepare($dbc, $query);
		mysqli_stmt_bind_param($stmt, "si", $value, $on_point_backup_title_id);
		$result = mysqli_stmt_execute($stmt);
		if ($result) {
            echo "Backup Title Successfully Updated";
			unset($on_point_backup_title);
            $on_point_backup_title = $value;
			$audit_user = $_SESSION['user'];
            $audit_first_name = $_SESSION['first_name'];
            $audit_last_name = $_SESSION['last_name'];
            $audit_profile_pic = $_SESSION['profile_pic'];
            $switch_id = $_SESSION['switch_id'];
            date_default_timezone_set("America/New_York");
            $audit_date = date('m-d-Y g:i A');
            $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-calendar-alt"></i> Updated On Point Backup Title</span>';
            $audit_action = 'Updated On Point Backup Title';
            $audit_ip = $_SERVER['REMOTE_ADDR'];
            $audit_source = $_SERVER['REQUEST_URI'];
            $audit_domain = $_SERVER['SERVER_NAME'];
            $audit_detailed_action = '<span class="dark-gray fw-bold">From</span>:' . ' ' . htmlspecialchars($on_point_backup_title) . '<br>' . '<span class="dark-gray fw-bold">To</span>:' . ' ' . htmlspecialchars($value);

        	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_audit = mysqli_prepare($dbc, $audit_query);
            mysqli_stmt_bind_param($stmt_audit, "ssssissssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $value, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
            mysqli_stmt_execute($stmt_audit);
            mysqli_stmt_close($stmt_audit);
        } else {
            error_log("Failed to update Backup Title for ID.");
            http_response_code(500);
            echo "Failed to update Backup Title. Please try again later.";
        }
		mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);
?>