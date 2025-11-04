<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!isset($_SESSION['user'])) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!checkRole('user_role')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['id']) && !empty($_POST['id'])) {
	$user_id = $_POST['id'];
	$query = "SELECT * FROM users WHERE id = ?";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
		confirmQuery($result);

        $response = array();
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response = $row;
            }
        } else {
            $response['status'] = 200;
            $response['message'] = "Data not found!";
        }
        mysqli_stmt_close($stmt);
		echo json_encode($response);

    } else {
        $response['status'] = 500;
        $response['message'] = "Failed to prepare statement!";
        echo json_encode($response);
    }

} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
    echo json_encode($response);
}
mysqli_close($dbc);