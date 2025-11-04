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

if (!isset($_SESSION['user'])) {
    die('Error: User not authenticated');
	exit();
}

$session_sender = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = strip_tags($_POST['recipient']);
    $notes_public = strip_tags($_POST['notes_public']);
    $notes_personal = strip_tags($_POST['notes_personal']);
    $teamwork = strip_tags($_POST['teamwork']);
    $knowledge = strip_tags($_POST['knowledge']);
    $service = strip_tags($_POST['service']);
    date_default_timezone_set('America/New_York');
    $date = date("m-d-Y");
    $time = date("h:i A");

    $user_query = "SELECT * FROM users WHERE user = ?";
    $stmt_user = mysqli_prepare($dbc, $user_query);
    mysqli_stmt_bind_param($stmt_user, 's', $recipient);
    mysqli_stmt_execute($stmt_user);
    $user_result = mysqli_stmt_get_result($stmt_user);
    $user_row = mysqli_fetch_array($user_result);

    if (!$user_row) {
        die('User not found');
    }

    $first_name = strip_tags($user_row['first_name']);
    $last_name = strip_tags($user_row['last_name']);
    $full_name = $first_name .' '. $last_name;

    $query = "INSERT INTO kudos (recipient, recipient_name, kudos_date, kudos_time, kudos_from, teamwork, knowledge, service, notes_public, notes_personal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);

	$teamwork_value = empty($teamwork) ? null : $teamwork;
	$knowledge_value = empty($knowledge) ? null : $knowledge;
	$service_value = empty($service) ? null : $service;

	mysqli_stmt_bind_param($stmt, 'ssssssssss', $recipient, $full_name, $date, $time, $session_sender, $teamwork_value, $knowledge_value, $service_value, $notes_public, $notes_personal);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        $response = "success";
    } else {
        $response = "error";
    }
	echo $response;
	mysqli_stmt_close($stmt_user);
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);