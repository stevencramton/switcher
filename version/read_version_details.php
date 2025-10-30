<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('version_edit')){
    header("Location:../../index.php?msg1");
    exit();
}

$response = array();

if (isset($_POST['id']) && !empty($_POST['id'])) {
	$id = intval($_POST['id']);

	$query = "SELECT * FROM versions WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $response = mysqli_fetch_assoc($result);
        } else {
            $response['status'] = 200;
            $response['message'] = "Data not found!";
        }

        mysqli_stmt_close($stmt);
    } else {
        $response['status'] = 500;
        $response['message'] = "Database query failed";
    }
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
}

echo json_encode($response);
mysqli_close($dbc);
?>