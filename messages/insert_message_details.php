<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!isset($_SESSION['id'])) {
    header("Location:../../index.php?msg1");
    exit();
} else {
	if (isset($_POST['id'])) {
        $message_id = htmlspecialchars(strip_tags($_POST['id']));
		$query = "SELECT * FROM messages_sent WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $query);
        if ($stmt === false) {
            die('Error.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $message_id);
        $execute_result = mysqli_stmt_execute($stmt);
        if ($execute_result === false) {
            die('Error.');
        }

        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            die('Error.');
        }

        $response = array();
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response = $row;
            }
        }
		echo json_encode($response);
		mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);