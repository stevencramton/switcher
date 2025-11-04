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
	$query = "SELECT AVG(knowledge) AS average FROM kudos WHERE recipient = ? AND knowledge > 0";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, "s", $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_array($result);
        $average = $row['average'];
        $average = is_null($average) ? 0 : number_format((float) $average, 2);
        echo $average === 0 ? "0.00" : $average;
    } else {
        echo "0.00";
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($dbc);