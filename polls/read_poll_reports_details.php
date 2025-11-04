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

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
	$inquiry_id = $_POST['inquiry_id'];
	$query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";
	$stmt = mysqli_prepare($dbc, $query_ballot_count);
	mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
 	mysqli_stmt_execute($stmt);
 	$ballot_results = mysqli_stmt_get_result($stmt);
  	$ballot_votes = mysqli_num_rows($ballot_results);
 	mysqli_stmt_close($stmt);

 	$query_responses = "SELECT * FROM poll_response WHERE question_id = ? ORDER BY response_display_order ASC";
	$stmt = mysqli_prepare($dbc, $query_responses);
	mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
 	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

 		$query_name = "SELECT * FROM poll_inquiry WHERE inquiry_id = ?";
        $stmt_name = mysqli_prepare($dbc, $query_name);
        mysqli_stmt_bind_param($stmt_name, 'i', $inquiry_id);
        mysqli_stmt_execute($stmt_name);
        $result_name = mysqli_stmt_get_result($stmt_name);

        $data = '';

        if (mysqli_num_rows($result_name) > 0) {
            while ($row = mysqli_fetch_assoc($result_name)) {
                $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
                $data .= '<h5 class="dark-gray">
                            <i class="fa-solid fa-square-poll-horizontal"></i> <span class="" id="">' . $inquiry_name . '</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm float-end" onclick="cancelPollReportDetails();">
                                <i class="fa-solid fa-backward"></i>
                            </button>
                        </h5><hr>';
            }
        }

        $data .= '<div class="table-responsive mb-3">
                    <table class="table mb-3" id="" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>Photo</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th colspan="2">Answer</th>
                            </tr>
                        </thead>
                        <tbody id="">';

        $query_user_choice = "SELECT * FROM poll_ballot 
                              JOIN poll_response ON poll_ballot.question_id = poll_response.question_id 
                              JOIN users ON poll_ballot.ballot_user = users.user 
                              WHERE poll_ballot.question_id = ? AND answer_id = response_id 
                              ORDER BY response_answer ASC";
        $stmt_user_choice = mysqli_prepare($dbc, $query_user_choice);
        mysqli_stmt_bind_param($stmt_user_choice, 'i', $inquiry_id);
        mysqli_stmt_execute($stmt_user_choice);
        $result_user_choice = mysqli_stmt_get_result($stmt_user_choice);

        if (mysqli_num_rows($result_user_choice) > 0) {
            while ($row = mysqli_fetch_assoc($result_user_choice)) {
                $user_profile_pic = htmlspecialchars(strip_tags($row['profile_pic']));
                $ballot_user_first_name = htmlspecialchars(strip_tags($row['first_name']));
                $ballot_user_last_name = htmlspecialchars(strip_tags($row['last_name']));
                $ballot_user_full_name = $ballot_user_first_name . ' ' . $ballot_user_last_name;
                $ballot_username = htmlspecialchars(strip_tags($row['ballot_user']));
                $response_answer = htmlspecialchars(strip_tags($row['response_answer']));

                $data .= '<tr>
                            <td class="align-middle" style="width:6%;"><img src="' . $user_profile_pic . '" class="profile-photo"></td>
                            <td class="align-middle" style="width:15%;">' . $ballot_user_full_name . '</td>
                            <td class="align-middle">' . $ballot_username . '</td>
                            <td class="align-middle">' . $response_answer . '</td>
                        </tr>';
            }
        }

        $data .= '</tbody></table></div>';

        $data .= '<div class="table-responsive">
                    <table class="table mb-3" id="add_new_answer_row" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>Votes</th>
                                <th colspan="2">Count</th>
                            </tr>
                        </thead>
                        <tbody id="">';

        $data .= '<tr class="" id="" data-id="">
                    <td>Total votes</td>
                    <td colspan="2">' . $ballot_votes . '</td>
                </tr>
                <thead class="table-light">
                    <tr>
                        <th>Answers</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody class="">';

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response_id = htmlspecialchars(strip_tags($row['response_id']));
                $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
				$query_ballot_answer_count = "SELECT * FROM poll_ballot WHERE answer_id = ?";
                $stmt_ballot_answer_count = mysqli_prepare($dbc, $query_ballot_answer_count);
                mysqli_stmt_bind_param($stmt_ballot_answer_count, 'i', $response_id);
                mysqli_stmt_execute($stmt_ballot_answer_count);
                $ballot_answer_results = mysqli_stmt_get_result($stmt_ballot_answer_count);
                $ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
                mysqli_stmt_close($stmt_ballot_answer_count);

                $data .= '<tr>
                            <td>' . $response_answer . '</td>
                            <td>' . $ballot_answer_votes . '</td>
                            <td>' . $ballot_answer_votes . '</td>
                        </tr>';
            }
        } else {
            $data .= '<p>No data available</p>';
        }

        $data .= '</tbody>
                </table>
            </div>';

        echo $data;
    }

mysqli_close($dbc);