<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    die("Forbidden");
}

if (!isset($_POST['username'])) {
    http_response_code(400);
    die("Invalid request");
}

$username = strip_tags($_POST['username']);
date_default_timezone_set("America/New_York");
$request_password_date = date('m-d-Y g:i A');

$query = "SELECT * FROM users WHERE user = ?";
if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $count = mysqli_num_rows($result);

        if ($count == 0) {
            $response = "Failure";
        } else {
       	 	$update_query = "UPDATE users SET password_change = 1, password_request_date = ? WHERE user = ?";
            if ($update_stmt = mysqli_prepare($dbc, $update_query)) {
                mysqli_stmt_bind_param($update_stmt, 'ss', $request_password_date, $username);
                mysqli_stmt_execute($update_stmt);

                if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                    $response = "Success";
                } else {
                    $response = "update_failed";
                }
                mysqli_stmt_close($update_stmt);
            } else {
                $response = "update_failed";
            }
        }
        echo json_encode($response);
    } else {
        echo json_encode("query_failed");
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode("query_failed");
}
mysqli_close($dbc);