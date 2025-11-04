<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('kudos_view')) {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];

	$k_query = "SELECT SUM(knowledge) AS total_knowledge, COUNT(knowledge) AS count_knowledge FROM kudos WHERE recipient = ? AND knowledge > 0";
    $k_stmt = mysqli_prepare($dbc, $k_query);
    mysqli_stmt_bind_param($k_stmt, "s", $user);
    mysqli_stmt_execute($k_stmt);
    mysqli_stmt_bind_result($k_stmt, $k_sum, $k_count);
    mysqli_stmt_fetch($k_stmt);
    mysqli_stmt_close($k_stmt);

    $k_sum = is_null($k_sum) ? 0 : round((float) $k_sum, 2);

	$s_query = "SELECT SUM(service) AS total_service, COUNT(service) AS count_service FROM kudos WHERE recipient = ? AND service > 0";
    $s_stmt = mysqli_prepare($dbc, $s_query);
    mysqli_stmt_bind_param($s_stmt, "s", $user);
    mysqli_stmt_execute($s_stmt);
    mysqli_stmt_bind_result($s_stmt, $s_sum, $s_count);
    mysqli_stmt_fetch($s_stmt);
    mysqli_stmt_close($s_stmt);
	
	$s_sum = is_null($s_sum) ? 0 : round((float) $s_sum, 2);
	
	$t_query = "SELECT SUM(teamwork) AS total_teamwork, COUNT(teamwork) AS count_teamwork FROM kudos WHERE recipient = ? AND teamwork > 0";
    $t_stmt = mysqli_prepare($dbc, $t_query);
    mysqli_stmt_bind_param($t_stmt, "s", $user);
    mysqli_stmt_execute($t_stmt);
    mysqli_stmt_bind_result($t_stmt, $t_sum, $t_count);
    mysqli_stmt_fetch($t_stmt);
    mysqli_stmt_close($t_stmt);

    $t_sum = is_null($t_sum) ? 0 : round((float) $t_sum, 2);
	$valid_ratings_count = ($k_count ?: 0) + ($s_count ?: 0) + ($t_count ?: 0);

    if ($valid_ratings_count == 0) {
        $average = "0.00";
    } else {
        $average = ($k_sum + $s_sum + $t_sum) / $valid_ratings_count;
        $average = number_format((float) $average, 2, '.', '');
    }

    echo $average;
}
mysqli_close($dbc);