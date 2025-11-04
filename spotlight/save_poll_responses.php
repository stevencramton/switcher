<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
    header("Location:../../index.php?msg1");
	exit();
}

function runQuery($query) {
    global $dbc;
    $result = mysqli_query($dbc, $query);

    while ($row = mysqli_fetch_array($result)) {
        $resultset[] = $row;
    }

    if (!empty($resultset)) {
        return $resultset;
    }
}

function insertQuery($query) {
    global $dbc;
    mysqli_query($dbc, $query);
    $insert_id = mysqli_insert_id($dbc);
    return $insert_id;
}

$answer_id = mysqli_real_escape_string($dbc, strip_tags($_POST['answer']));
$question_id = mysqli_real_escape_string($dbc, strip_tags($_POST['question']));

$inquiry_id = $question_id;

$query = "INSERT INTO spotlight_ballot(question_id, answer_id, ballot_user) VALUES ('" . $question_id . "','" . $answer_id . "','" . $_SESSION["user"] . "')";
$insert_id = insertQuery($query);

if (!empty($insert_id)) {
    $query = "SELECT * FROM spotlight_nominee WHERE question_id = " . $question_id;
    $answer = runQuery($query);
}

if (!empty($answer)) {
    $data = '';

    $data .= '<div class="h-100 p-4 bg-white border rounded-3 shadow-sm mb-3">
                <h5 class="dark-gray"><i class="fa-solid fa-ranking-star"></i> Spotlight Results </h5>
                <hr class="hr-line">';

    $query = "SELECT count(answer_id) as total_count FROM spotlight_ballot WHERE question_id = " . $question_id;
    $total_rating = runQuery($query);

    foreach ($answer as $k => $v) {
        $query = "SELECT count(answer_id) as answer_count FROM spotlight_ballot WHERE question_id = " . $question_id . " AND answer_id = " . $answer[$k]["assignment_id"];
        $answer_rating = runQuery($query);

        $answers_count = 0;

        if (!empty($answer_rating)) {
            $answers_count = $answer_rating[0]["answer_count"];
        }

        $percentage = 0;

        if (!empty($total_rating)) {
            $percentage = ($answers_count / $total_rating[0]["total_count"]) * 100;

            if (is_float($percentage)) {
                $percentage = number_format($percentage, 2);
            }
        }

        if (80 <= $percentage && $percentage <= 100) {
            $percentage_class = 'bg-success';
        } else if (50 <= $percentage && $percentage <= 79) {
            $percentage_class = 'bg-info';
        } else if (25 <= $percentage && $percentage <= 49) {
            $percentage_class = 'bg-warning';
        } else if (1 <= $percentage && $percentage <= 24) {
            $percentage_class = 'bg-danger';
        } else {
            $percentage_class = '';
        }

     	$query = "SELECT first_name, last_name FROM users WHERE user = '" . $answer[$k]["assignment_user"] . "'";
        $user_info = runQuery($query);

        if (!empty($user_info)) {
            $full_name = $user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'];
        } else {
            $full_name = $answer[$k]["assignment_user"];
        }

        $data .= '<div class="mb-3">
                    <strong class="mb-3">' . $full_name . '<small class="text-secondary float-end">' . $percentage . ' %</small></strong>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped ' . $percentage_class . '" role="progressbar" aria-label="Example with label" style="width:' . $percentage . '%" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>';
    }

    $data .= '</div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" onclick="readspotlightReportDetails(' . $inquiry_id . ');"><i class="fa-solid fa-circle-arrow-right"></i> Next</button>
                        </div>
                    </div>
                </div>';

    echo $data;
}
?>
