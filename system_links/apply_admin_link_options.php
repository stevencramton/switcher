<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isset($_POST['emp_id'])) {
	$switch_id = strip_tags($_SESSION['switch_id']);
    $emp_id = strip_tags($_POST['emp_id']);
	$query = "UPDATE user_settings SET admin_link_select = ? WHERE user_settings_switch_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

	mysqli_stmt_bind_param($stmt, "ss", $emp_id, $switch_id);
	mysqli_stmt_execute($stmt);
	
	if (mysqli_stmt_affected_rows($stmt) > 0) {
        $_SESSION['admin_link_select'] = $emp_id;
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);