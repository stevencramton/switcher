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
    $data = '<div class="col-lg-12 mx-auto">
                <div class="row stat-cards">';

    $poll_user = $_SESSION['user'];
 	$query_my_poll_enrollment_count = "SELECT COUNT(*) FROM poll_assignment WHERE assignment_user = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query_my_poll_enrollment_count)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_enrollments);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit("Error.");
    }

	$query_my_poll_completed_count = "SELECT COUNT(*) FROM poll_ballot WHERE ballot_user = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query_my_poll_completed_count)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_completed);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit("Error.");
    }

    $not_started = $my_poll_enrollments - $my_completed;
	$query_my_poll_status = "SELECT COUNT(*) FROM poll_assignment WHERE assignment_user = ? AND assignment_read = '1'";
    
    if ($stmt = mysqli_prepare($dbc, $query_my_poll_status)) {
        mysqli_stmt_bind_param($stmt, "s", $poll_user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $my_poll_status);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        exit("Error.");
    }

    $data .= '<div class="col-md-6 col-xl-3 mb-3">
                <article class="stat-cards-item border shadow-sm mb-3">
                    <div class="stat-cards-icon warning">
                        <i class="fa-solid fa-bell fa-xl"></i>
                    </div>
                    <div class="stat-cards-info">
                        <p class="stat-cards-info__num">' . $my_poll_status . '</p>
                        <p class="stat-cards-info__title">New polls</p>
                    </div>
                </article>
             </div>
             <div class="col-md-6 col-xl-3 mb-3">
                <article class="stat-cards-item border shadow-sm mb-3">
                    <div class="stat-cards-icon primary">
                        <i class="fa-solid fa-user-plus fa-xl"></i>
                    </div>
                    <div class="stat-cards-info">
                        <p class="stat-cards-info__num">' . $my_poll_enrollments . '</p>
                        <p class="stat-cards-info__title">My enrollments</p>
                    </div>
                </article>
             </div>
             <div class="col-md-6 col-xl-3 mb-3">
                <article class="stat-cards-item border shadow-sm mb-3">
                    <div class="stat-cards-icon danger">
                        <i class="fa-solid fa-hourglass fa-xl"></i>
                    </div>
                    <div class="stat-cards-info">
                        <p class="stat-cards-info__num">' . $not_started . '</p>
                        <p class="stat-cards-info__title">Not started</p>
                    </div>
                </article>
             </div>
             <div class="col-md-6 col-xl-3 mb-3">
                <article class="stat-cards-item border shadow-sm mb-3">
                    <div class="stat-cards-icon success">
                        <i class="fa-solid fa-user-check fa-xl"></i>
                    </div>
                    <div class="stat-cards-info">
                        <p class="stat-cards-info__num">' . $my_completed . '</p>
                        <p class="stat-cards-info__title">My completed</p>
                    </div>
                </article>
             </div>';
    
    $data .= '</div>
        </div>';

    echo $data;
}
mysqli_close($dbc);