<?php
session_start();
require_once '../../vendor/autoload.php';
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

$username = strip_tags($_POST['username']);
$password = strip_tags($_POST['password']);
$id_token = strip_tags($_POST['id_token']);
$CLIENT_ID = "";
$client = new Google_Client(['client_id' => $CLIENT_ID]);
$payload = $client->verifyIdToken($id_token);

if ($payload) {
	$userid = strip_tags($payload['sub']);
    $google_email = strip_tags($payload['email']);

    $query = "SELECT * FROM users WHERE user = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $count = mysqli_num_rows($result);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$hash = $row['password'];

        if ($count == 1 && password_verify($password, $hash)) {
            $update_query = "UPDATE users SET personal_email = ? WHERE user = ?";
            $update_stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'ss', $google_email, $username);
            mysqli_stmt_execute($update_stmt);
            $response = "success";
        } else {
            $response = "user";
        }
		echo json_encode($response);
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);