<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('kudos_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['kudos_id'])) {
	$kudos_id = $_POST['kudos_id'];
	$kudos_ids = array_map('intval', explode(',', $kudos_id));
	$placeholders = implode(',', array_fill(0, count($kudos_ids), '?'));
	$query = "UPDATE kudos SET read_flag = 0 WHERE id IN ($placeholders)";
	$stmt = mysqli_prepare($dbc, $query);
    
    if ($stmt === false) {
		die('Error failed');
    }
    
	$types = str_repeat('i', count($kudos_ids));
    $params = array_merge([$types], $kudos_ids);
	$bind_names[] = $types;
    for ($i=0; $i < count($kudos_ids); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $kudos_ids[$i];
        $bind_names[] = &$$bind_name;
    }
    
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);
	mysqli_stmt_execute($stmt);
    
    if (mysqli_stmt_affected_rows($stmt) === -1) {
		die('Error failed');
    }
 	confirmQuery($stmt);
 	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);