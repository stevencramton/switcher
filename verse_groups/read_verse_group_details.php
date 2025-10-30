<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_view')) {
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['verse_group_id'])) {
	$verse_group_id = mysqli_real_escape_string($dbc, $_POST['verse_group_id']);
	$query = "SELECT * FROM verse_groups WHERE verse_group_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "i", $verse_group_id);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$response = array();

    if (mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
        $response = $row;
    } else {
        $response['status'] = 200;
        $response['message'] = "Data not found!";
    }
	echo json_encode($response);
	mysqli_stmt_close($stmt);
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}
mysqli_close($dbc);