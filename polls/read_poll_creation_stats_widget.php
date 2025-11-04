<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {
	$data = '';

	$query = "SELECT * FROM poll_inquiry";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            exit();
        }
        mysqli_stmt_close($stmt);
    }

  	$total_poll_count = "SELECT COUNT(*) FROM poll_inquiry";
    if ($stmt = mysqli_prepare($dbc, $total_poll_count)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_polls);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
	
	$total_active_poll_count = "SELECT COUNT(*) FROM poll_inquiry WHERE inquiry_status = 'Active'";
    if ($stmt = mysqli_prepare($dbc, $total_active_poll_count)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_active_polls);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

	$total_paused_poll_count = "SELECT COUNT(*) FROM poll_inquiry WHERE inquiry_status = 'Paused'";
    if ($stmt = mysqli_prepare($dbc, $total_paused_poll_count)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_paused_polls);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    $total_closed_poll_count = "SELECT COUNT(*) FROM poll_inquiry WHERE inquiry_status = 'Closed'";
    if ($stmt = mysqli_prepare($dbc, $total_closed_poll_count)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_closed_polls);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    $total_enrolled_count = "SELECT COUNT(*) FROM poll_assignment";
    if ($stmt = mysqli_prepare($dbc, $total_enrolled_count)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $total_enrolled_users);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    $data .= '<ul class="list-group mb-3">
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0">Total Polls</h6>
                        <small class="text-body-secondary">All Polls</small>
                    </div>
                    <span class="text-body-secondary"><span class="badge bg-cool-ice">' . $total_polls . '</span></span>
                </li>
            
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0">Active</h6>
                        <small class="text-body-secondary">Active Polls</small>
                    </div>
                    <span class="text-body-secondary"><span class="badge bg-cool-ice">' . $total_active_polls . '</span></span>
                </li>
                
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0">Paused</h6>
                        <small class="text-body-secondary">Paused Polls</small>
                    </div>
                    <span class="text-body-secondary"><span class="badge bg-cool-ice">' . $total_paused_polls . '</span></span>
                </li>
                
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0">Closed</h6>
                        <small class="text-body-secondary">Closed Polls</small>
                    </div>
                    <span class="text-body-secondary"><span class="badge bg-cool-ice">' . $total_closed_polls . '</span></span>
                </li>
                
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0">All Enrollments</h6>
                        <small class="text-body-secondary">Total Enrollments</small>
                    </div>
                    <span class="text-body-secondary"><span class="badge bg-cool-ice">' . $total_enrolled_users . '</small></span>
                </li>
            </ul>';

    echo $data;

}
mysqli_close($dbc);