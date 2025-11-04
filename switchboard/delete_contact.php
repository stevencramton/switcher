<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_contacts')){
    header("Location: ../../index.php?msg1");
    exit();
}

if(isset($_POST['selected_values'])){
	$ids = $_POST['selected_values'];
    $ids = explode(',', $ids);
    $ids = array_map('intval', $ids);
	$placeholders = implode(',', array_fill(0, count($ids), '?'));
	$query = "DELETE FROM switchboard_contacts WHERE switchboard_id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
      	$types = str_repeat('i', count($ids));
        $bind_params = array_merge(array($types), $ids);
		$params = array();
        foreach ($bind_params as $key => $value) {
            $params[$key] = &$bind_params[$key];
        }
		if (!call_user_func_array(array($stmt, 'bind_param'), $params)) {
            echo("Binding parameters failed.");
        } else {
        	if (!mysqli_stmt_execute($stmt)) {
                echo("Execution failed.");
            } else {
                echo "Records deleted successfully.";
            }
        }
		mysqli_stmt_close($stmt);
    } else {
        echo("Error in preparing statement.");
    }
}
mysqli_close($dbc);