<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['id'])) {
    $user = $_SESSION['user'];
	$status_icon_away = "<i class='fas fa-eye' id='edit_luncheon_status_away'></i>";
	$query = "SELECT * FROM luncheon WHERE luncheon_sender = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
     	mysqli_stmt_bind_param($stmt, 's', $user);

        if (mysqli_stmt_execute($stmt)) {
         	$select_alerts = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($select_alerts) > 0) {

                while ($row = mysqli_fetch_assoc($select_alerts)) {

                    $luncheon_id = $row['luncheon_id'];
                    $luncheon_color = $row['luncheon_color'];
                    $luncheon_time_start = $row['luncheon_time_start'];
                    $luncheon_time_end = $row['luncheon_time_end'];
                    $luncheon_status = $row['luncheon_status'];

                    if ($luncheon_status == 1) {
                        $status_icon_away = "<i class='fas fa-eye-slash' id='edit_luncheon_status_away'></i>";
                    } else {
                        $status_icon_away = "<i class='fas fa-eye' id='edit_luncheon_status_away'></i>";
                    }
                }
            }
        } else {
            exit();
        }

      	mysqli_stmt_close($stmt);
    } else {
     	exit();
    }

    $data = '<button type="button" class="btn btn-outline-secondary btn-sm" id="edit_luncheon_view_away" onclick="updateLuncheonStatus();">' . $status_icon_away . '</button>';

    echo $data;

	mysqli_close($dbc);
}