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
	$on_point_backup_id = $_POST['pk'];
    $value = $_POST['value'];
	if (empty($value)){
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid name";
    } else {
		$query = "UPDATE on_point_backup SET on_point_backup_thursday = ? WHERE on_point_backup_id = ?";
        $stmt = mysqli_prepare($dbc, $query);
		mysqli_stmt_bind_param($stmt, "si", $value, $on_point_backup_id);
		$result = mysqli_stmt_execute($stmt);
		if ($result) {
            echo "Thursday Backup Successfully Updated";
			unset($on_point_backup_thursday);
            $on_point_backup_thursday = $value;
        } else {
			error_log("Failed to update Thursday Backup for ID.");
            http_response_code(500);
            echo "Failed to update Thursday Backup. Please try again later.";
        }
		mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);
?>