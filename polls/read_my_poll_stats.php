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

    $data = '<ul class="list-group mb-2">';

    $poll_user = $_SESSION['user'];
 	$query_my_poll_count = "SELECT COUNT(*) FROM poll_assignment WHERE assignment_user = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query_my_poll_count)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit();
    }

	$query_my_poll_active = "SELECT COUNT(*) FROM poll_assignment 
                             JOIN poll_inquiry ON poll_assignment.poll_id = poll_inquiry.inquiry_id 
                             WHERE assignment_user = ? AND poll_inquiry.inquiry_status = 'Active'";

    if ($stmt = mysqli_prepare($dbc, $query_my_poll_active)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_active);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit();
    }

 	$query_my_poll_paused = "SELECT COUNT(*) FROM poll_assignment 
                             JOIN poll_inquiry ON poll_assignment.poll_id = poll_inquiry.inquiry_id 
                             WHERE assignment_user = ? AND poll_inquiry.inquiry_status = 'Paused'";

    if ($stmt = mysqli_prepare($dbc, $query_my_poll_paused)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_paused);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit();
    }

   	$query_my_poll_closed = "SELECT COUNT(*) FROM poll_assignment 
                             JOIN poll_inquiry ON poll_assignment.poll_id = poll_inquiry.inquiry_id 
                             WHERE assignment_user = ? AND poll_inquiry.inquiry_status = 'Closed'";

    if ($stmt = mysqli_prepare($dbc, $query_my_poll_closed)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_closed);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit();
    }

    $data .= '<li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Total Polls</h6>
                    <small class="text-body-secondary">All Polls</small>
                </div>
                <div>
                    <span class="badge bg-cool-ice">' . $my_poll_count . '</span>
                </div>
             </li>
             <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Active</h6>
                    <small class="text-body-secondary">Active Polls</small>
                </div>
                <div>
                    <span class="badge bg-cool-ice">' . $my_poll_active . '</span>
                </div>
             </li>
             <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Paused</h6>
                    <small class="text-body-secondary">Paused Polls</small>
                </div>
                <div>
                    <span class="badge bg-cool-ice">' . $my_poll_paused . '</span>
                </div>
             </li>
             <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                    <h6 class="my-0">Closed</h6>
                    <small class="text-body-secondary">Closed Polls</small>
                </div>
                <div>
                    <span class="badge bg-cool-ice">' . $my_poll_closed . '</span>
                </div>
             </li>';

    $data .= '</ul>
        <div class="d-grid">
            <button type="button" class="btn btn-outline-secondary w-100" id="" onclick="showClosedPollResults();">
                <i class="fa-solid fa-square-poll-horizontal"></i> View Poll Results
            </button>
        </div>';

    echo $data;
}

mysqli_close($dbc);