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

    $data = '<div class="row stat-cards gx-3">';

    if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
        $inquiry_id = $_POST['inquiry_id'];
		$query = "SELECT * FROM poll_inquiry WHERE inquiry_id = ?";
        if ($stmt = mysqli_prepare($dbc, $query)) {
          	mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
          	mysqli_stmt_execute($stmt);
         	$result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
                    $inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status']));
                    
                    $query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
                    if ($stmt_enrollment = mysqli_prepare($dbc, $query_enrollment_count)) {
                        mysqli_stmt_bind_param($stmt_enrollment, 'i', $inquiry_id);
                        mysqli_stmt_execute($stmt_enrollment);
                        $enrollment_results = mysqli_stmt_get_result($stmt_enrollment);
                        $enrolled_users = mysqli_num_rows($enrollment_results);
                        mysqli_stmt_close($stmt_enrollment);
                    }
                    
                    $query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";
                    if ($stmt_ballot = mysqli_prepare($dbc, $query_ballot_count)) {
                        mysqli_stmt_bind_param($stmt_ballot, 'i', $inquiry_id);
                        mysqli_stmt_execute($stmt_ballot);
                        $ballot_results = mysqli_stmt_get_result($stmt_ballot);
                        $ballot_votes = mysqli_num_rows($ballot_results);
                        mysqli_stmt_close($stmt_ballot);
                    }
                    
                    $not_started = $enrolled_users - $ballot_votes;

                    $data .= '<div class="col-md-6 col-xl-3 mb-3">
                                <article class="stat-cards-item border shadow-sm">
                                    <div class="stat-cards-icon primary">
                                        <i class="fa-solid fa-users fa-xl"></i>
                                    </div>
                                    <div class="stat-cards-info">
                                        <p class="stat-cards-info__num">' . $enrolled_users . '</p>
                                        <p class="stat-cards-info__title">Total enrollments</p>
                                    </div>
                                </article>
                              </div>
                              <div class="col-md-6 col-xl-3 mb-3">
                                <article class="stat-cards-item border shadow-sm">
                                    <div class="stat-cards-icon warning">
                                        <i class="fa-solid fa-user-check fa-xl"></i>
                                    </div>
                                    <div class="stat-cards-info">
                                        <p class="stat-cards-info__num">' . $ballot_votes . '</p>
                                        <p class="stat-cards-info__title">Total completed</p>
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
                                        <p class="stat-cards-info__title">Total not started</p>
                                    </div>
                                </article>
                              </div>
                              <div class="col-md-6 col-xl-3 mb-3">
                                <article class="stat-cards-item border shadow-sm mb-3">';

                    if ($inquiry_status == "Active") {
                        $data .= '<div class="stat-cards-icon success">
                                    <i class="fa-regular fa-circle-check fa-xl"></i>
                                  </div>
                                  <div class="stat-cards-info">
                                    <p class="stat-cards-info__num">Active</p>
                                    <p class="stat-cards-info__title">Poll status</p>
                                  </div>';
                    } else if ($inquiry_status == "Paused") {
                        $data .= '<div class="stat-cards-icon danger">
                                    <i class="fa-regular fa-circle-pause fa-xl"></i>
                                  </div>
                                  <div class="stat-cards-info">
                                    <p class="stat-cards-info__num">Paused</p>
                                    <p class="stat-cards-info__title">Poll status</p>
                                  </div>';
                    } else if ($inquiry_status == "Closed") {
                        $data .= '<div class="stat-cards-icon bg-light-gray">
                                    <i class="fa-solid fa-check-to-slot fa-xl"></i>
                                  </div>
                                  <div class="stat-cards-info">
                                    <p class="stat-cards-info__num">Closed</p>
                                    <p class="stat-cards-info__title">Poll status</p>
                                  </div>';
                    } else if ($inquiry_status == "Completed") {
                        $data .= '<div class="stat-cards-icon primary">
                                    <i class="fa-solid fa-check-to-slot fa-xl"></i>
                                  </div>
                                  <div class="stat-cards-info">
                                    <p class="stat-cards-info__num">Completed</p>
                                    <p class="stat-cards-info__title">Poll status</p>
                                  </div>';
                    }
                    $data .= '</article></div>';
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            exit('Error preparing the SQL statement.');
        }
    }

    $data .= '</div>';

    echo $data;
}
mysqli_close($dbc);