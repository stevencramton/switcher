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

function runQuery($query, $params = [], $types = '') {
    global $dbc;

    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die("Database error.");
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $resultset = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $resultset[] = $row;
    }

    mysqli_stmt_close($stmt);

    return !empty($resultset) ? $resultset : null;
}

function insertQuery($query, $params = [], $types = '') {
    global $dbc;

    $stmt = mysqli_prepare($dbc, $query);
    if ($stmt === false) {
        die("Database error.");
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $insert_id = mysqli_insert_id($dbc);

    mysqli_stmt_close($stmt);

    return $insert_id;
}

$answer_id = htmlspecialchars(strip_tags($_POST['answer']), ENT_QUOTES, 'UTF-8');
$question_id = htmlspecialchars(strip_tags($_POST['question']), ENT_QUOTES, 'UTF-8');
$inquiry_id = $question_id;

$query = "INSERT INTO poll_ballot (question_id, answer_id, ballot_user) VALUES (?, ?, ?)";
$insert_id = insertQuery($query, [$question_id, $answer_id, $_SESSION["user"]], 'iis');

if (!empty($insert_id)) {
    $query = "SELECT * FROM poll_response WHERE question_id = ?";
    $answer = runQuery($query, [$question_id], 'i');
}

if (!empty($answer)) {
    $data = '';
    $data .= '<div class="h-100 p-4 bg-white border rounded-3 shadow-sm mb-3">
                <h5 class="dark-gray"><i class="fa-solid fa-square-poll-horizontal"></i> Poll Results </h5>
                <hr class="">';

    $query = "SELECT count(answer_id) as total_count FROM poll_ballot WHERE question_id = ?";
    $total_rating = runQuery($query, [$question_id], 'i');

    foreach ($answer as $k => $v) {
        $query = "SELECT count(answer_id) as answer_count FROM poll_ballot WHERE question_id = ? AND answer_id = ?";
        $answer_rating = runQuery($query, [$question_id, $answer[$k]["response_id"]], 'ii');
		$answers_count = !empty($answer_rating) ? $answer_rating[0]["answer_count"] : 0;
		$percentage = !empty($total_rating) ? ($answers_count / $total_rating[0]["total_count"]) * 100 : 0;

        if (is_float($percentage)) {
            $percentage = number_format($percentage, 2);
        }

        $percentage_class = '';
        if (80 <= $percentage && $percentage <= 100) {
            $percentage_class = 'bg-success';
        } else if (50 <= $percentage && $percentage <= 79) {
            $percentage_class = 'bg-info';
        } else if (25 <= $percentage && $percentage <= 49) {
            $percentage_class = 'bg-warning';
        } else if (1 <= $percentage && $percentage <= 24) {
            $percentage_class = 'bg-danger';
        }

        $data .= '<div class="mb-3">
                    <strong class="mb-3">' . htmlspecialchars($answer[$k]["response_answer"], ENT_QUOTES, 'UTF-8') . '<small class="text-secondary float-end">' . htmlspecialchars($percentage, ENT_QUOTES, 'UTF-8') . ' %</small></strong>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped ' . htmlspecialchars($percentage_class, ENT_QUOTES, 'UTF-8') . '" role="progressbar" aria-label="Example with label" style="width:' . htmlspecialchars($percentage, ENT_QUOTES, 'UTF-8') . '%" aria-valuenow="' . htmlspecialchars($percentage, ENT_QUOTES, 'UTF-8') . '" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>';
    }

    $data .= '</div>
            <div class="row">
                <div class="col-md-8">
                    <div class="">
                        <button type="button" class="btn btn-primary btn-lg shadow w-100" onclick="readPollReportDetails(' . htmlspecialchars($inquiry_id, ENT_QUOTES, 'UTF-8') . ');"><i class="fa-solid fa-circle-arrow-right"></i> Next</button>
                    </div>
                </div>
            </div>';

    echo $data;
}

mysqli_close($dbc);