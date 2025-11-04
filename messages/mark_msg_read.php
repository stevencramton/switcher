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
		$id = $_POST['id'];
        $status = $_POST['msg_status'];
        $message_force = $_POST['message_force'];

		$query = "UPDATE messages SET message_read = ?, message_force = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_bind_param($stmt, 'ssi', $status, $message_force, $id);

            if (!mysqli_stmt_execute($stmt)) {
                die('Error.');
            }

            mysqli_stmt_close($stmt);
        } else {
            die('Error.');
        }

		$query_sent = "SELECT * FROM messages_sent WHERE id = ?";
        if ($stmt_sent = mysqli_prepare($dbc, $query_sent)) {
            mysqli_stmt_bind_param($stmt_sent, 'i', $id);
            mysqli_stmt_execute($stmt_sent);
            $result_sent = mysqli_stmt_get_result($stmt_sent);

            if (mysqli_num_rows($result_sent) > 0) {
				$query_update_sent = "UPDATE messages_sent SET message_read = ?, message_force = ? WHERE id = ?";
                if ($stmt_update_sent = mysqli_prepare($dbc, $query_update_sent)) {
                    mysqli_stmt_bind_param($stmt_update_sent, 'ssi', $status, $message_force, $id);

                    if (!mysqli_stmt_execute($stmt_update_sent)) {
                        die('Error.');
                    }

                    mysqli_stmt_close($stmt_update_sent);
                } else {
                    die('Error.');
                }
            }

            mysqli_stmt_close($stmt_sent);
        } else {
            die('Error.');
        }

		mysqli_close($dbc);
    }
}
