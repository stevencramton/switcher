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
?>

<style> .list-group-item {cursor:pointer;} </style>
<script src="js/kudos.js"></script>

<style>
.rating-loading{
	width:25px;
	height:25px;
	font-size:0;
	color:#fff;
    }

.rating-container .rating-stars {
	position:relative;
	cursor:pointer;
	vertical-align:middle;
	display:inline-block;
	overflow:hidden;
	white-space:nowrap;
	line-height: 1.5em !important;
    }
	
.rating-container .rating-input {
	position:absolute;
	cursor:pointer;
	width:100%;
	height:1px;
	bottom:0;left:0;
	font-size:1px;
	border:none;
	background:0 0;
	padding:0;
	margin:0
    }

.rating-disabled .rating-input,.rating-disabled .rating-stars {
	cursor: default;
    }

.rating-container .star {
	display:inline-block;
	margin:0 3px;
	text-align:center
    }

.rating-container .star .far {
	line-height: 1.5;
    }

.rating-container .star .fas {
	line-height: 1.5;
    }
	
.rating-container .empty-stars{
	color:#aaa
    }
	
.rating-container .filled-stars {
	position:absolute;
	left: 0;
	top: 0;
	margin:auto;
	color: #ffc107;
	white-space:nowrap;
	overflow:hidden;
	-webkit-text-stroke: 1px #d29520;
	text-shadow: 1px 1px #bd6e34;
    }
    
.rating-rtl {
	float:right;
	}
	
.rating-animate .filled-stars {
	transition:width .25s ease;
	-o-transition:width .25s ease;
	-moz-transition:width .25s ease;-webkit-transition:width .25s ease;
    }

.rating-rtl .filled-stars {
	left:auto;
	right:0;
	-moz-transform:matrix(-1,0,0,1,0,0) translate3d(0,0,0);
	-webkit-transform:matrix(-1,0,0,1,0,0) translate3d(0,0,0);
	-o-transform:matrix(-1,0,0,1,0,0) translate3d(0,0,0);
	transform:matrix(-1,0,0,1,0,0) translate3d(0,0,0);
	}

.rating-rtl.is-star .filled-stars {
	right:.06em;
	}
	
.rating-rtl.is-heart .empty-stars {
	margin-right:.07em;
	}

.rating-xl {
	font-size:2.6em;
	}

.rating-lg {
	font-size:2.0em
	}

.rating-md {
	font-size:1.5em
	}

.rating-sm {
	font-size:1em
	}

.rating-xs {
	font-size:0.7em
	}
	
.rating-container .clear-rating {
	display: none;
	color:#aaa;
	cursor: default;
	vertical-align:middle;
	font-size:60%;
	padding-right:5px
	}
	
.clear-rating-active {
	cursor:pointer!important;
	display:inline-block !important;
    }
	
.clear-rating-active:hover {
	color:#dc3545;
	}
	
.rating-container .caption {
	color:#999;
	display:inline-block;
	vertical-align:middle;
	font-size:60%;
	margin-top:-.6em;
	margin-left:5px;
	margin-right:0
	}
	
.rating-rtl .caption {
	margin-right:5px;
	margin-left:0
	}

@media print {
	.rating-container .clear-rating {
		display:none
	}
}
</style>

<?php
$user = $_SESSION["user"];

function getIds($user) {
    global $dbc;
	$query = "SELECT DISTINCT question_id FROM poll_ballot WHERE ballot_user = ?";
	$stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "s", $user);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$resultset = [];
    while ($row = mysqli_fetch_array($result)) {
        $resultset[] = $row[0];
    }
	mysqli_stmt_close($stmt);
	return $resultset;
}

$result = getIds($user);

$condition = "";
if (!empty($result)) {
    $condition = " AND inquiry_id NOT IN (" . implode(",", array_map('intval', $result)) . ")";
}

$data = '';

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
    $inquiry_id = $_POST['inquiry_id'];
	$query = "SELECT poll_inquiry.inquiry_id, poll_inquiry.inquiry_author, poll_inquiry.inquiry_creation_date, 
                     poll_inquiry.inquiry_name, poll_inquiry.inquiry_image, poll_inquiry.inquiry_question, 
                     poll_inquiry.inquiry_info, poll_inquiry.inquiry_status, poll_assignment.assignment_user, 
                     poll_assignment.poll_id 
              FROM poll_inquiry 
              INNER JOIN poll_assignment ON poll_inquiry.inquiry_id = poll_assignment.poll_id 
              AND poll_assignment.assignment_user = ? 
              WHERE poll_inquiry.inquiry_id = ? AND inquiry_status = 'Active' " . $condition . " 
              ORDER BY inquiry_display_order ASC 
              LIMIT 1";

	$stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "ss", $user, $inquiry_id);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$questions = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $questions[] = $row;
    }
	mysqli_stmt_close($stmt);
}

function runQuery($query, $params = [], $types = "") {
    global $dbc;
	$stmt = mysqli_prepare($dbc, $query);
	if ($params) {
     	mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$resultset = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $resultset[] = $row;
    }
	mysqli_stmt_close($stmt);
	return $resultset;
}

if (!empty($questions)) {
	$questions_name = $questions[0]["inquiry_name"] ?? '';

    $data .= '<h5 class="dark-gray"><i class="fa-solid fa-square-poll-horizontal"></i> '.$questions_name.' 
                 <button type="button" class="btn btn-light-gray btn-sm float-end" onclick="cancelPollReportDetails();">
                    <i class="fa-solid fa-rotate-left"></i>
                 </button>
              </h5>
              <hr class="">';

    $data .= '<div class="d-flex align-items-center text-break mb-3">
        <div class="flex-shrink-0">
            <img src="'.$questions[0]["inquiry_image"].'" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="read_audit_profile_pic">
        </div>
        <div class="flex-grow-1 ms-3">
            <div class="fs-5">
                <div class="dark-gray fw-bold mt-3">'.$questions[0]["inquiry_question"].'</div>
                <p><small class="text-secondary"><i>'.$questions[0]["inquiry_info"].'</i></small></p>
                <input type="hidden" name="question" id="question" value="'.$questions[0]["inquiry_id"].'">
            </div>
        </div>
    </div>';

    $data .= '<div class="row">
        <div class="col-md-8">';

	$query = "SELECT * FROM poll_response WHERE question_id = ? ORDER BY response_display_order ASC";
    $answers = runQuery($query, [$questions[0]["inquiry_id"]], "i");

    if (!empty($answers)) {
        $response_type = $answers[0]["response_type"] ?? '';

        foreach ($answers as $k => $v) {
            if ($answers[$k]["response_type"] == 'single_select') {
                $data .= '<div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
                            <div class="position-relative">
                                <input type="radio" class="form-check-input position-absolute top-50 end-0 me-3 fs-5" name="answer" id="test_'.$answers[$k]["response_id"].'" value="'.$answers[$k]["response_id"].'">
                                <label class="list-group-item py-3 pe-5" for="test_'.$answers[$k]["response_id"].'">
                                    <strong class="fw-semibold">'.$answers[$k]["response_answer"].'</strong>
                                    <span class="d-block small opacity-75">'.$answers[$k]["response_info"].'</span>
                                </label>
                             </div>
                          </div>';
            } else if ($answers[$k]["response_type"] == 'multi_select') {
                $data .= '<div class="list-group mb-2">
                            <label class="list-group-item d-flex gap-3">
                                <input type="checkbox" class="form-check-input flex-shrink-0" id="test_'.$answers[$k]["response_id"].'" value="'.$answers[$k]["response_id"].'" style="font-size: 1.375em;">
                                <span class="pt-1 form-checked-content">
                                    <strong>'.$answers[$k]["response_answer"].'</strong>
                                    <small class="d-block text-body-secondary">
                                        <svg class="bi me-1" width="1em" height="1em"><use xlink:href="#calendar-event"></use></svg>
                                        '.$answers[$k]["response_info"].'
                                    </small>
                                </span>
                            </label>
                        </div>';
            } else if ($answers[$k]["response_type"] == 'true_false') {
                $data .= '<div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
                            <div class="position-relative">
                                <input type="radio" class="form-check-input position-absolute top-50 end-0 me-3 fs-5" name="answer" id="test_'.$answers[$k]["response_id"].'" value="'.$answers[$k]["response_id"].'">
                                <label class="list-group-item py-3 pe-5" for="test_'.$answers[$k]["response_id"].'">
                                    <strong class="fw-semibold">'.$answers[$k]["response_answer"].'</strong>
                                    <span class="d-block small opacity-75">'.$answers[$k]["response_info"].'</span>
                                </label>
                             </div>
                          </div>';
            } else if ($answers[$k]["response_type"] == 'image_radio') {
                $data .= '<div class="card_col d-inline-block">
                            <!-- Avatar picture & button -->
                            <div class="col ms-2">
                                <img src="img/profile_pic/default_1/avatar.jpg" class="img-thumbnail img-responsive img-radio" id="img_1" style="height:200px; width:200px;">
                                <button type="button" class="btn btn-orange btn-sm btn-radio w-100 mt-1" id="test_'.$answers[$k]["response_id"].'" value="'.$answers[$k]["response_id"].'">
                                <i class="far fa-circle"></i> Select
                                </button>
                                <input type="checkbox" class="d-none">
                            </div>
                        </div>';
            } else if ($answers[$k]["response_type"] == 'star_rating') {
                $data .= '<div class="text-center form-group shadow-sm boarder rounded bg-white p-3">
                            <label for="input-2" class="control-label dark-gray" style="font-weight:600;">Teamwork</label>
                            <div class="container">
                                <input id="input-1" name="input-1" class="rating rating-loading" data-min="0" data-max="5" data-step="0.25" value="" data-size="md">
                            </div>
                        </div>';
            } else if ($answers[$k]["response_type"] == 'range_slider') {
                $data .= '<div class="bg-white border rounded p-3 mb-3">
                            <label for="customRange1" class="form-label">Example range</label>
                            <input type="range" class="form-range" id="customRange1">
                          </div>';
            }
        }

        if ($response_type == 'single_select') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> Single Select
                            </button>
                        </div>';
        } else if ($response_type == 'multi_select') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> Multi Select
                            </button>
                        </div>';
        } else if ($response_type == 'true_false') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> True or False
                            </button>
                        </div>';
        } else if ($response_type == 'image_radio') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> Image Radio
                            </button> 
                        </div>';
        } else if ($response_type == 'star_rating') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> Star Rating
                            </button>
                        </div>';
        } else if ($response_type == 'range_slider') {
            $data .= '<hr style="border-top: 1px dashed red;">
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-lg shadow w-100" id="submit-vote" onclick="addPoll();" disabled>
                                <i class="fa-regular fa-circle-check"></i> Range Slider
                            </button>
                        </div>';
        }
    }

    $data .= '</div>';

    $data .= '<div class="col-md-4">
                <div class="h-100 p-4 bg-white border rounded-3 shadow-sm mb-3">
                    <h5 class="dark-gray"><i class="fa-solid fa-square-poll-horizontal"></i> Poll Details</h5>
                    <hr class="">
                    <div class="mb-2 mt-0"> 
                        <span class="fw-bold">Poll</span>: <span class="text-secondary" id="">'.$questions[0]["inquiry_name"].'</span> <br>
                        <span class="fw-bold">Info</span>: <span class="text-secondary" id="">'.$questions[0]["inquiry_info"].'</span> <br>
                        <span class="fw-bold">Date</span>: <span class="text-secondary" id="read_audit_date">'.$questions[0]["inquiry_creation_date"].'</span> <br>
                        <span class="fw-bold">Author</span>: <span class="text-secondary" id="read_audit_first_name">'.$questions[0]["inquiry_author"].'</span> <br>
                    </div>
                    <hr>';

    $inquiry_id = $questions[0]["inquiry_id"];
	$query_enrollment_count = "SELECT COUNT(*) FROM poll_assignment WHERE poll_id = ?";
    $enrollment_results = runQuery($query_enrollment_count, [$inquiry_id], "i");
    $enrolled_users = $enrollment_results[0]['COUNT(*)'] ?? 0;
	$query_ballot_count = "SELECT COUNT(*) FROM poll_ballot WHERE question_id = ?";
    $ballot_results = runQuery($query_ballot_count, [$inquiry_id], "i");
    $ballot_votes = $ballot_results[0]['COUNT(*)'] ?? 0;
	$participation_rate = ($enrolled_users > 0) ? ($ballot_votes / $enrolled_users) * 100 : 0;
    $percentage_rate = number_format($participation_rate, 2, '.', '');

    $data .= '<div class="mt-0">
                <span class="fw-bold">Assigned</span>: <span class="text-secondary" id="read_audit_last_name">'.$enrolled_users.'</span> <br>
                <span class="fw-bold">Responses</span>: <span class="text-secondary" id="read_audit_last_name">'.$ballot_votes.'</span> <br>
                <span class="fw-bold">Participation</span>: <span class="text-secondary" id="read_audit_last_name">'.$percentage_rate.'%</span> <br>
              </div>
              <hr class="">
              <div>
                  <img src="'.$questions[0]["inquiry_image"].'" class="profile-photo me-2"> 
                  <strong class="mb-3">'.$questions[0]["inquiry_name"].' <small class="text-secondary float-end mt-2">'.$percentage_rate.'%</small></strong>
                  <div class="progress mt-2" style="height: 18px;">
                      <div class="progress-bar progress-bar-striped bg-secondary" role="progressbar" aria-label="Example with label" style="width: '.$percentage_rate.'%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
              </div>
            </div>
          </div>';

} else {
	$date = date_default_timezone_set("America/New_York"); 
    $date = date('m-d-Y g:i A');
    
    $data .= '<div class="tab-content">
                <div class="tab-pane fade in active show mb-3" id="poll_response_tab">    
                    <div class="px-4 py-4 my-4 text-center" style="margin-top:80px !important;">
                    <svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
                        <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                        <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
                    </svg>
                    <h1 class="display-5 fw-bold mt-3 dark-gray">Thank you!</h1>
                    <div class="col-lg-6 mx-auto">
                        <p class="lead mb-4">Your response has been submitted.</p>
                    </div>
                    <div class="col-lg-6 mx-auto">
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <button type="button" class="btn btn-outline-primary btn-lg px-4 gap-3" id="show-poll-results">
                                    <i class="fa-solid fa-square-poll-horizontal"></i> View Poll Results
                                </button>
                                <button type="button" class="btn btn-primary btn-lg px-4 gap-3" onclick="cancelPollReportDetails();">
                                    <i class="fa-solid fa-arrow-rotate-left"></i>
                                </button>
                            </div>
                        </div>
                    </div> <!-- End col-lg-6 mx-auto -->
                </div> <!-- End px-4 py-4 my-4 text-center -->
             </div> <!-- End poll_response_tab -->
            
            <div class="tab-pane fade in" id="poll_results_tab">
                <h5 class="dark-gray">
                    <i class="fa-solid fa-square-poll-horizontal"></i> Poll Results 
                    <button type="button" class="btn btn-light-gray btn-sm float-end" onclick="closePollReportDetails();">
                        <i class="fas fa-undo-alt"></i>
                    </button>
                </h5>
                <hr>';

    $data .= '<div class="accordion accordion-flush" id="accordionPollReports">';
    
	$report_requestor = $_SESSION['user'];
	$query_reports = "SELECT * FROM poll_inquiry 
		JOIN poll_assignment ON poll_inquiry.inquiry_id = poll_assignment.poll_id 
		JOIN poll_ballot ON poll_assignment.poll_id = poll_ballot.question_id 
		WHERE assignment_user = ballot_user AND ballot_user = ? 
		ORDER BY poll_inquiry.inquiry_display_order ASC";

		if ($stmt = mysqli_prepare($dbc, $query_reports)) {
			mysqli_stmt_bind_param($stmt, 's', $report_requestor);
			mysqli_stmt_execute($stmt);
			$result_reports = mysqli_stmt_get_result($stmt);
			
			while ($row = mysqli_fetch_assoc($result_reports)) {
				$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_id']));
				$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author']));
				$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date']));
				$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
				$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
				$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
				$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info']));
				$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status']));
							
				if ($inquiry_image == ''){
					$inquiry_image = 'media/links/default_poll_image.png';
				} else {
					$inquiry_image = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_image']));
				}
						
				// Count poll answers
				$query_poll_answers = "SELECT COUNT(*) FROM poll_response WHERE question_id = ?";
				if ($stmt_poll_answers = mysqli_prepare($dbc, $query_poll_answers)) {
					mysqli_stmt_bind_param($stmt_poll_answers, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_poll_answers);
					mysqli_stmt_bind_result($stmt_poll_answers, $poll_answers_count);
					mysqli_stmt_fetch($stmt_poll_answers);
					mysqli_stmt_close($stmt_poll_answers);
				}			
						
				// Count enrolled users
				$query_enrollment_count = "SELECT COUNT(*) FROM poll_assignment WHERE poll_id = ?";
				if ($stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count)) {
					mysqli_stmt_bind_param($stmt_enrollment_count, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_enrollment_count);
					mysqli_stmt_bind_result($stmt_enrollment_count, $enrolled_users);
					mysqli_stmt_fetch($stmt_enrollment_count);
					mysqli_stmt_close($stmt_enrollment_count);
				}
			
				// Count ballot votes
				$query_ballot_count = "SELECT COUNT(*) FROM poll_ballot WHERE question_id = ?";
				if ($stmt_ballot_count = mysqli_prepare($dbc, $query_ballot_count)) {
					mysqli_stmt_bind_param($stmt_ballot_count, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_ballot_count);
					mysqli_stmt_bind_result($stmt_ballot_count, $ballot_votes);
					mysqli_stmt_fetch($stmt_ballot_count);
					mysqli_stmt_close($stmt_ballot_count);
				}
				
				$participation_rate = ($enrolled_users > 0) ? ($ballot_votes / $enrolled_users) * 100 : 0;
				$percentage_rate = number_format($participation_rate, 2, '.', '');			
			
				$data .= '<div class="accordion-item border mb-2">
				    <h2 class="accordion-header">
				        <button type="button" class="accordion-button d-flex justify-content-between align-items-center collapsed"
				            style="padding: 0.5rem;" data-bs-toggle="collapse" data-bs-target="#accord_' . $inquiry_id . '"
				            aria-expanded="false" aria-controls="flush-collapseOne">
				            <img src="' . $inquiry_image . '" class="profile-photo ms-2"> 
				            <span class="w-25">
				                <strong class="dark-gray ms-3">' . $inquiry_name . '</strong>
				            </span>
				            <span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1"
				                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
				                data-bs-title="' . $inquiry_creation_date . '" disabled>
				                <i class="fa-solid fa-clock text-secondary"></i> Date
				            </span>
				            <span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1"
				                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
				                data-bs-title="' . $inquiry_author . '" disabled>
				                <i class="fa-solid fa-circle-user text-secondary"></i> Author
				            </span>
				            <span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1"
				                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
				                data-bs-title="Poll Answers" disabled>
				                <i class="fa-solid fa-clipboard-question text-secondary"></i> ' . $poll_answers_count . '
				            </span>
				            <span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1"
				                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
				                data-bs-title="Enrolled Users" disabled>
				                <i class="fa-solid fa-users text-secondary"></i> ' . $enrolled_users . '
				            </span>
				            <span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1"
				                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
				                data-bs-title="Ballot Votes" disabled>
				                <i class="fa-solid fa-check-to-slot text-secondary"></i> ' . $ballot_votes . '
				            </span>
				            <span class="flex-grow-1 ms-2" style="width:125px;">
				                <div class="progress" data-bs-toggle="tooltip" data-bs-placement="top"
				                    data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completion Rate ' . $percentage_rate . '%"
				                    role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0"
				                    aria-valuemax="100">
				                    <div class="progress-bar progress-bar-striped bg-info" style="width: ' . $percentage_rate . '%"></div>
				                </div>
				            </span>';			

							if ($inquiry_status == "Active") {
				    			$data .= '<span class="btn btn-light btn-sm bg-mint ms-2 me-2" data-bs-toggle="tooltip"
				        			data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" style="width:50px;"
				        			disabled><i class="fa-solid fa-circle-check"></i></span>';
							} else if ($inquiry_status == "Paused") {
				    			$data .= '<span class="btn btn-light btn-sm bg-hot ms-2 me-2" data-bs-toggle="tooltip"
				        			data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" style="width:50px;"
				       		 		disabled><i class="fa-solid fa-circle-pause"></i></span>';
							} else if ($inquiry_status == "Closed") {
				    			$data .= '<span class="btn btn-light btn-sm bg-concrete ms-2 me-2" data-bs-toggle="tooltip"
				        			data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" style="width:50px;"
				        			disabled><i class="fa-solid fa-folder-closed"></i></span>';
							}

                        $data .= '</button>
				    	</h2>';			
									
						$data .= '<div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionPollReports">
						            <div class="accordion-body">';
                
						$data .= '<div class="fw-bold fs-5 dark-gray mt-2">'.$inquiry_question.'</div>';

						$data .= '<hr>';

						$query_two = "SELECT * FROM poll_response WHERE question_id = ? ORDER BY response_display_order ASC";
						
						if ($stmt_two = mysqli_prepare($dbc, $query_two)) {
						    mysqli_stmt_bind_param($stmt_two, 'i', $inquiry_id);
						    mysqli_stmt_execute($stmt_two);
						    $result_two = mysqli_stmt_get_result($stmt_two);

						    if (mysqli_num_rows($result_two) > 0) {
								$highest_value = 0;
						        $winning_items = array();

						        while ($row = mysqli_fetch_assoc($result_two)) {
									$response_id = htmlspecialchars(strip_tags($row['response_id']));
						            $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
									$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
						            if ($stmt_enrollment = mysqli_prepare($dbc, $query_enrollment_count)) {
						                mysqli_stmt_bind_param($stmt_enrollment, 'i', $inquiry_id);
						                mysqli_stmt_execute($stmt_enrollment);
						                $enrollment_results = mysqli_stmt_get_result($stmt_enrollment);
						                $enrolled_users = mysqli_num_rows($enrollment_results);
						            }

						         	$query_ballot_answer_count = "SELECT * FROM poll_ballot WHERE answer_id = ?";
						            if ($stmt_ballot = mysqli_prepare($dbc, $query_ballot_answer_count)) {
						                mysqli_stmt_bind_param($stmt_ballot, 'i', $response_id);
						                mysqli_stmt_execute($stmt_ballot);
						                $ballot_answer_results = mysqli_stmt_get_result($stmt_ballot);
						                $ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
						            }

						          	$percentage_rate = ($ballot_answer_votes / $ballot_votes) * 100;
									if ($ballot_answer_votes > $highest_value) {
						                $highest_value = $ballot_answer_votes;
						                $winning_items = array($response_answer);
						            } else if ($ballot_answer_votes == $highest_value) {
						                array_push($winning_items, $response_answer);
						            }

						            $percentage_rate_new = round($percentage_rate, 2);

						            $data .= '<span class="badge bg-light-gray mb-1 me-1">'.$ballot_answer_votes.'</span>
						                     <span class="fw-bold text-secondary">'.$response_answer.'</span>
						                     <span class="fw-bold text-secondary float-end">
						                     <small>'.$percentage_rate_new.'%</small>
						                     </span>';

						            if ($percentage_rate >= 0 && $percentage_rate <= 25) {
						              	$data .= '<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
						                            <div class="progress-bar progress-bar-striped bg-danger" style="width: '.$percentage_rate.'%"></div>
						                          </div>';
						            } else if ($percentage_rate >= 26 && $percentage_rate <= 50) {
						              	$data .= '<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
						                            <div class="progress-bar progress-bar-striped bg-warning" style="width: '.$percentage_rate.'%"></div>
						                          </div>';
						            } else if ($percentage_rate >= 51 && $percentage_rate <= 75) {
						              	$data .= '<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
						                            <div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
						                          </div>';
						            } else if ($percentage_rate >= 76 && $percentage_rate <= 100) {
						              	$data .= '<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
						                            <div class="progress-bar progress-bar-striped bg-success" style="width: '.$percentage_rate.'%"></div>
						                          </div>';
						            }

						            $data .= '';
						        }
								
								if (count($winning_items) == 1) {
								    $data .= '<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
								                <div class="d-flex align-items-center text-break">
								                    <div class="flex-shrink-0">
								                        <!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
								                        <img src="img/poll_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
								                    </div>
								                    <div class="flex-grow-1 ms-3">
								                        <div class="fs-5">
								                            <div class="fw-bold mt-3">';

								    if ($enrolled_users != $ballot_votes) {
								        $data .= '<span class="">The leading choice is...</span>';
								    } else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
								        $data .= '<span class="">And the Winning choice is!</span>';
								    } else if ($ballot_votes == 0) {
								        $data .= '<span class="">This poll has no votes.</span>';
								    }

								    $data .= '<div class="btn-group btn-group-sm float-end" role="group" aria-label="Small button group">
								                <span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
								                    <i class="fa-solid fa-users"></i> '.$enrolled_users.'
								                </span>
								                <span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
								                    <i class="fa-solid fa-check-to-slot"></i> '.$ballot_votes.'
								                </span>';

								    if ($enrolled_users != $ballot_votes) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Completed" disabled>
								                    <i class="fa-solid fa-bars-progress"></i> '.$ballot_votes.'
								                  </span>';
								    } else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completed" disabled>
								                    <i class="fa-solid fa-circle-check"></i> '.$ballot_votes.'
								                  </span>';
								    } else if ($ballot_votes == 0) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Started" disabled>
								                    <i class="fa-solid fa-circle-info"></i> '.$ballot_votes.'
								                  </span>';
								    }

								    $data .= '</div></div>
								              <p class="mb-0"><small class=""><i>'.$winning_items[0].'</i></small></p>';

								    $query_user_choice = "SELECT * FROM poll_ballot 
								                            JOIN poll_response ON poll_ballot.question_id = poll_response.question_id 
								                            WHERE poll_ballot.question_id = ? 
								                            AND ballot_user = ? 
								                            AND answer_id = response_id";
								    if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
								        mysqli_stmt_bind_param($stmt_user_choice, 'is', $inquiry_id, $report_requestor);
								        mysqli_stmt_execute($stmt_user_choice);
								        $result_user_choice = mysqli_stmt_get_result($stmt_user_choice);

								        if (mysqli_num_rows($result_user_choice) > 0) {
								            while ($row = mysqli_fetch_assoc($result_user_choice)) {
								                $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
								                $data .= '<small style="color:orange;"><i>Your choice was: '.$response_answer.'</i></small>';
								            }
								        }
								    }

								    $data .= '</div>
								            </div>
								        </div>
								    </div>';

								} else if (count($winning_items) > 1) {
								    $data .= '<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
								                <div class="d-flex align-items-center text-break">
								                    <div class="flex-shrink-0">
								                        <!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
								                        <img src="img/poll_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
								                    </div>
								                    <div class="flex-grow-1 ms-3">
								                        <div class="fs-5">
								                            <div class="fw-bold mt-3">
								                                There is a tie between the following choices:
								                                <div class="btn-group btn-group-sm float-end" role="group" aria-label="Small button group">
								                                    <span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip"
								                                        data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
								                                        <i class="fa-solid fa-users"></i> ' . $enrolled_users . '
								                                    </span>
								                                    <span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip"
								                                        data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
								                                        <i class="fa-solid fa-check-to-slot"></i> ' . $ballot_votes . '
								                                    </span>';

								    if ($enrolled_users != $ballot_votes) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip"
								            data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Completed" disabled>
								            <i class="fa-solid fa-bars-progress"></i> ' . $ballot_votes . '
								        </span>';
								    } else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip"
								            data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completed" disabled>
								            <i class="fa-solid fa-circle-check"></i> ' . $ballot_votes . '
								        </span>';
								    } else if ($ballot_votes == 0) {
								        $data .= '<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip"
								            data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Started" disabled>
								            <i class="fa-solid fa-circle-info"></i> ' . $ballot_votes . '
								        </span>';
								    }

								    $data .= '</div>
								            </div>
								            <p>';

								    $num_winners = count($winning_items);
								    $counter = 1;

								    foreach ($winning_items as $item) {
								        if ($counter == $num_winners) {
								            $data .= '<small class=""><i>' . $item . '</i></small> ';
								        } else {
								            $data .= '<small class=""><i>' . $item . '</i></small>, ';
								        }
								        $counter++;
								    }

								    $data .= '</p>';

								    $query_user_choice = "
								        SELECT *
								        FROM poll_ballot
								        JOIN poll_response
								        ON poll_ballot.question_id = poll_response.question_id
								        WHERE poll_ballot.question_id = ?
								        AND ballot_user = ?
								        AND answer_id = response_id
								    ";

								    if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
								        mysqli_stmt_bind_param($stmt_user_choice, 'is', $inquiry_id, $report_requestor);
								        mysqli_stmt_execute($stmt_user_choice);
								        $result_user_choice = mysqli_stmt_get_result($stmt_user_choice);

								        if (mysqli_num_rows($result_user_choice) > 0) {
								            while ($row = mysqli_fetch_assoc($result_user_choice)) {
								                $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
								                $data .= '<small style="color:orange;"><i>Your choice was... ' . $response_answer . '</i></small>';
								            }
								        }
								        mysqli_stmt_close($stmt_user_choice);
								    }

								    $data .= '</div>
								            </div>
								        </div>
								    </div>';
								}
							}	
						}
		
					$data .='</div>
						</div>
					</div>';

			}
			mysqli_stmt_close($stmt);				 
		}
		
		$data .='</div>';

		 	$data .='<div class="h-100 p-3 bg-white border rounded-3 shadow-sm mb-3 w-50">
			 			<a href="#" class="btn btn-secondary w-100" id="hide-poll-results-tab">
							<i class="fas fa-undo-alt"></i> Close Poll Results
						</a>
				 	 </div>
				</div>
	   		</div>';				
									 
}
	
echo $data;
mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	$("input:checkbox").change(function () {
		$("#submit-vote").prop("disabled", false);}
	);
	$("input:radio").change(function () {
		$("#submit-vote").prop("disabled", false);}
	);
	$("#submit-vote").click(function() {
		$(this).prop("disabled", true);
		$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submit');
	});

});
</script>

<script>
$(document).ready(function(){
	$("#show-poll-results").click(function(){
		$("#poll_response_tab").fadeOut("fast", function(){
			$("#poll_response_tab").removeClass("active show");
			$("#poll_results_tab").fadeIn("fast", function(){
				$("#poll_results_tab").addClass("active show");
			});
		});
	});
	$("#hide-poll-results-tab").click(function(){
		$("#poll_results_tab").fadeOut("fast", function(){
			$("#poll_results_tab").removeClass("active show");
			$("#poll_response_tab").fadeIn("fast", function(){
				$("#poll_response_tab").addClass("active show");
			});
		});
	});	
});
</script>

<script>
function closePollReportDetails() {
	$("#poll_results_tab").fadeOut("fast", function(){
		$("#poll_results_tab").removeClass("active show");
		$("#poll_response_tab").fadeIn("fast", function(){
			$("#poll_response_tab").addClass("active show");
		});
	});
}
</script>
	
<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>

<script>

	!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],e):"object"==typeof module&&module.exports?module.exports=e(require("jquery")):e(window.jQuery)}(function(e){"use strict";e.fn.ratingLocales={},e.fn.ratingThemes={};var t,a;t={NAMESPACE:".rating",DEFAULT_MIN:0,DEFAULT_MAX:5,DEFAULT_STEP:.5,isEmpty:function(t,a){return null===t||void 0===t||0===t.length||a&&""===e.trim(t)},getCss:function(e,t){return e?" "+t:""},addCss:function(e,t){e.removeClass(t).addClass(t)},getDecimalPlaces:function(e){var t=(""+e).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);return t?Math.max(0,(t[1]?t[1].length:0)-(t[2]?+t[2]:0)):0},applyPrecision:function(e,t){return parseFloat(e.toFixed(t))},handler:function(e,a,n,r,i){var l=i?a:a.split(" ").join(t.NAMESPACE+" ")+t.NAMESPACE;r||e.off(l),e.on(l,n)}},a=function(t,a){var n=this;n.$element=e(t),n._init(a)},a.prototype={constructor:a,_parseAttr:function(e,a){var n,r,i,l,s=this,o=s.$element,c=o.attr("type");if("range"===c||"number"===c){switch(r=a[e]||o.data(e)||o.attr(e),e){case"min":i=t.DEFAULT_MIN;break;case"max":i=t.DEFAULT_MAX;break;default:i=t.DEFAULT_STEP}n=t.isEmpty(r)?i:r,l=parseFloat(n)}else l=parseFloat(a[e]);return isNaN(l)?i:l},_parseValue:function(e){var t=this,a=parseFloat(e);return isNaN(a)&&(a=t.clearValue),!t.zeroAsNull||0!==a&&"0"!==a?a:null},_setDefault:function(e,a){var n=this;t.isEmpty(n[e])&&(n[e]=a)},_initSlider:function(e){var a=this,n=a.$element.val();a.initialValue=t.isEmpty(n)?0:n,a._setDefault("min",a._parseAttr("min",e)),a._setDefault("max",a._parseAttr("max",e)),a._setDefault("step",a._parseAttr("step",e)),(isNaN(a.min)||t.isEmpty(a.min))&&(a.min=t.DEFAULT_MIN),(isNaN(a.max)||t.isEmpty(a.max))&&(a.max=t.DEFAULT_MAX),(isNaN(a.step)||t.isEmpty(a.step)||0===a.step)&&(a.step=t.DEFAULT_STEP),a.diff=a.max-a.min},_initHighlight:function(e){var t,a=this,n=a._getCaption();e||(e=a.$element.val()),t=a.getWidthFromValue(e)+"%",a.$filledStars.width(t),a.cache={caption:n,width:t,val:e}},_getContainerCss:function(){var e=this;return"rating-container"+t.getCss(e.theme,"theme-"+e.theme)+t.getCss(e.rtl,"rating-rtl")+t.getCss(e.size,"rating-"+e.size)+t.getCss(e.animate,"rating-animate")+t.getCss(e.disabled||e.readonly,"rating-disabled")+t.getCss(e.containerClass,e.containerClass)},_checkDisabled:function(){var e=this,t=e.$element,a=e.options;e.disabled=void 0===a.disabled?t.attr("disabled")||!1:a.disabled,e.readonly=void 0===a.readonly?t.attr("readonly")||!1:a.readonly,e.inactive=e.disabled||e.readonly,t.attr({disabled:e.disabled,readonly:e.readonly})},_addContent:function(e,t){var a=this,n=a.$container,r="clear"===e;return a.rtl?r?n.append(t):n.prepend(t):r?n.prepend(t):n.append(t)},_generateRating:function(){var a,n,r,i=this,l=i.$element;n=i.$container=e(document.createElement("div")).insertBefore(l),t.addCss(n,i._getContainerCss()),i.$rating=a=e(document.createElement("div")).attr("class","rating-stars").appendTo(n).append(i._getStars("empty")).append(i._getStars("filled")),i.$emptyStars=a.find(".empty-stars"),i.$filledStars=a.find(".filled-stars"),i._renderCaption(),i._renderClear(),i._initHighlight(),n.append(l),i.rtl&&(r=Math.max(i.$emptyStars.outerWidth(),i.$filledStars.outerWidth()),i.$emptyStars.width(r)),l.appendTo(a)},_getCaption:function(){var e=this;return e.$caption&&e.$caption.length?e.$caption.html():e.defaultCaption},_setCaption:function(e){var t=this;t.$caption&&t.$caption.length&&t.$caption.html(e)},_renderCaption:function(){var a,n=this,r=n.$element.val(),i=n.captionElement?e(n.captionElement):"";if(n.showCaption){if(a=n.fetchCaption(r),i&&i.length)return t.addCss(i,"caption"),i.html(a),void(n.$caption=i);n._addContent("caption",'<br><div class="caption">'+a+"</div>"),n.$caption=n.$container.find(".caption")}},_renderClear:function(){var a,n=this,r=n.clearElement?e(n.clearElement):"";if(n.showClear){if(a=n._getClearClass(),r.length)return t.addCss(r,a),r.attr({title:n.clearButtonTitle}).html(n.clearButton),void(n.$clear=r);n._addContent("clear",'<div class="'+a+'" title="'+n.clearButtonTitle+'">'+n.clearButton+"</div>"),n.$clear=n.$container.find("."+n.clearButtonBaseClass)}},_getClearClass:function(){var e=this;return e.clearButtonBaseClass+" "+(e.inactive?"":e.clearButtonActiveClass)},_toggleHover:function(e){var t,a,n,r=this;e&&(r.hoverChangeStars&&(t=r.getWidthFromValue(r.clearValue),a=e.val<=r.clearValue?t+"%":e.width,r.$filledStars.css("width",a)),r.hoverChangeCaption&&(n=e.val<=r.clearValue?r.fetchCaption(r.clearValue):e.caption,n&&r._setCaption(n+"")))},_init:function(t){var a,n=this,r=n.$element.addClass("rating-input");return n.options=t,e.each(t,function(e,t){n[e]=t}),(n.rtl||"rtl"===r.attr("dir"))&&(n.rtl=!0,r.attr("dir","rtl")),n.starClicked=!1,n.clearClicked=!1,n._initSlider(t),n._checkDisabled(),n.displayOnly&&(n.inactive=!0,n.showClear=!1,n.showCaption=!1),n._generateRating(),n._initEvents(),n._listen(),a=n._parseValue(r.val()),r.val(a),r.removeClass("rating-loading")},_initEvents:function(){var e=this;e.events={_getTouchPosition:function(a){var n=t.isEmpty(a.pageX)?a.originalEvent.touches[0].pageX:a.pageX;return n-e.$rating.offset().left},_listenClick:function(e,t){return e.stopPropagation(),e.preventDefault(),e.handled===!0?!1:(t(e),void(e.handled=!0))},_noMouseAction:function(t){return!e.hoverEnabled||e.inactive||t&&t.isDefaultPrevented()},initTouch:function(a){var n,r,i,l,s,o,c,u,d=e.clearValue||0,p="ontouchstart"in window||window.DocumentTouch&&document instanceof window.DocumentTouch;p&&!e.inactive&&(n=a.originalEvent,r=t.isEmpty(n.touches)?n.changedTouches:n.touches,i=e.events._getTouchPosition(r[0]),"touchend"===a.type?(e._setStars(i),u=[e.$element.val(),e._getCaption()],e.$element.trigger("change").trigger("rating.change",u),e.starClicked=!0):(l=e.calculate(i),s=l.val<=d?e.fetchCaption(d):l.caption,o=e.getWidthFromValue(d),c=l.val<=d?o+"%":l.width,e._setCaption(s),e.$filledStars.css("width",c)))},starClick:function(t){var a,n;e.events._listenClick(t,function(t){return e.inactive?!1:(a=e.events._getTouchPosition(t),e._setStars(a),n=[e.$element.val(),e._getCaption()],e.$element.trigger("change").trigger("rating.change",n),void(e.starClicked=!0))})},clearClick:function(t){e.events._listenClick(t,function(){e.inactive||(e.clear(),e.clearClicked=!0)})},starMouseMove:function(t){var a,n;e.events._noMouseAction(t)||(e.starClicked=!1,a=e.events._getTouchPosition(t),n=e.calculate(a),e._toggleHover(n),e.$element.trigger("rating.hover",[n.val,n.caption,"stars"]))},starMouseLeave:function(t){var a;e.events._noMouseAction(t)||e.starClicked||(a=e.cache,e._toggleHover(a),e.$element.trigger("rating.hoverleave",["stars"]))},clearMouseMove:function(t){var a,n,r,i;!e.events._noMouseAction(t)&&e.hoverOnClear&&(e.clearClicked=!1,a='<span class="'+e.clearCaptionClass+'">'+e.clearCaption+"</span>",n=e.clearValue,r=e.getWidthFromValue(n)||0,i={caption:a,width:r,val:n},e._toggleHover(i),e.$element.trigger("rating.hover",[n,a,"clear"]))},clearMouseLeave:function(t){var a;e.events._noMouseAction(t)||e.clearClicked||!e.hoverOnClear||(a=e.cache,e._toggleHover(a),e.$element.trigger("rating.hoverleave",["clear"]))},resetForm:function(t){t&&t.isDefaultPrevented()||e.inactive||e.reset()}}},_listen:function(){var a=this,n=a.$element,r=n.closest("form"),i=a.$rating,l=a.$clear,s=a.events;return t.handler(i,"touchstart touchmove touchend",e.proxy(s.initTouch,a)),t.handler(i,"click touchstart",e.proxy(s.starClick,a)),t.handler(i,"mousemove",e.proxy(s.starMouseMove,a)),t.handler(i,"mouseleave",e.proxy(s.starMouseLeave,a)),a.showClear&&l.length&&(t.handler(l,"click touchstart",e.proxy(s.clearClick,a)),t.handler(l,"mousemove",e.proxy(s.clearMouseMove,a)),t.handler(l,"mouseleave",e.proxy(s.clearMouseLeave,a))),r.length&&t.handler(r,"reset",e.proxy(s.resetForm,a),!0),n},_getStars:function(e){var t,a=this,n='<span class="'+e+'-stars">';for(t=1;t<=a.stars;t++)n+='<span class="star">'+a[e+"Star"]+"</span>";return n+"</span>"},_setStars:function(e){var t=this,a=arguments.length?t.calculate(e):t.calculate(),n=t.$element,r=t._parseValue(a.val);return n.val(r),t.$filledStars.css("width",a.width),t._setCaption(a.caption),t.cache=a,n},showStars:function(e){var t=this,a=t._parseValue(e);return t.$element.val(a),t._setStars()},calculate:function(e){var a=this,n=t.isEmpty(a.$element.val())?0:a.$element.val(),r=arguments.length?a.getValueFromPosition(e):n,i=a.fetchCaption(r),l=a.getWidthFromValue(r);return l+="%",{caption:i,width:l,val:r}},getValueFromPosition:function(e){var a,n,r=this,i=t.getDecimalPlaces(r.step),l=r.$rating.width();return n=r.diff*e/(l*r.step),n=r.rtl?Math.floor(n):Math.ceil(n),a=t.applyPrecision(parseFloat(r.min+n*r.step),i),a=Math.max(Math.min(a,r.max),r.min),r.rtl?r.max-a:a},getWidthFromValue:function(e){var t,a,n=this,r=n.min,i=n.max,l=n.$emptyStars;return!e||r>=e||r===i?0:(a=l.outerWidth(),t=a?l.width()/a:1,e>=i?100:(e-r)*t*100/(i-r))},fetchCaption:function(e){var a,n,r,i,l,s=this,o=parseFloat(e)||s.clearValue,c=s.starCaptions,u=s.starCaptionClasses;return o&&o!==s.clearValue&&(o=t.applyPrecision(o,t.getDecimalPlaces(s.step))),i="function"==typeof u?u(o):u[o],r="function"==typeof c?c(o):c[o],n=t.isEmpty(r)?s.defaultCaption.replace(/\{rating}/g,o):r,a=t.isEmpty(i)?s.clearCaptionClass:i,l=o===s.clearValue?s.clearCaption:n,'<span class="'+a+'">'+l+"</span>"},destroy:function(){var a=this,n=a.$element;return t.isEmpty(a.$container)||a.$container.before(n).remove(),e.removeData(n.get(0)),n.off("rating").removeClass("rating rating-input")},create:function(e){var t=this,a=e||t.options||{};return t.destroy().rating(a)},clear:function(){var e=this,t='<span class="'+e.clearCaptionClass+'">'+e.clearCaption+"</span>";return e.inactive||e._setCaption(t),e.showStars(e.clearValue).trigger("change").trigger("rating.clear")},reset:function(){var e=this;return e.showStars(e.initialValue).trigger("rating.reset")},update:function(e){var t=this;return arguments.length?t.showStars(e):t.$element},refresh:function(t){var a=this,n=a.$element;return t?a.destroy().rating(e.extend(!0,a.options,t)).trigger("rating.refresh"):n}},e.fn.rating=function(n){var r=Array.apply(null,arguments),i=[];switch(r.shift(),this.each(function(){var l,s=e(this),o=s.data("rating"),c="object"==typeof n&&n,u=c.theme||s.data("theme"),d=c.language||s.data("language")||"en",p={},h={};o||(u&&(p=e.fn.ratingThemes[u]||{}),"en"===d||t.isEmpty(e.fn.ratingLocales[d])||(h=e.fn.ratingLocales[d]),l=e.extend(!0,{},e.fn.rating.defaults,p,e.fn.ratingLocales.en,h,c,s.data()),o=new a(this,l),s.data("rating",o)),"string"==typeof n&&i.push(o[n].apply(o,r))}),i.length){case 0:return this;case 1:return void 0===i[0]?this:i[0];default:return i}},e.fn.rating.defaults={theme:"",language:"en",stars:5,filledStar:'<i class="fas fa-star"></i>',emptyStar:'<i class="far fa-star"></i>',containerClass:"",size:"md",animate:!0,displayOnly:!1,rtl:!1,showClear:!0,showCaption:!0,starCaptionClasses:{.5:"label label-danger",1:"label label-danger",1.5:"label label-warning",2:"label label-warning",2.5:"label label-info",3:"label label-info",3.5:"label label-primary",4:"label label-primary",4.5:"label label-success",5:"label label-success"},clearButton:'<i class="fas fa-minus-circle"></i>',clearButtonBaseClass:"clear-rating",clearButtonActiveClass:"clear-rating-active",clearCaptionClass:"label label-default",clearValue:null,captionElement:null,clearElement:null,hoverEnabled:!0,hoverChangeCaption:!0,hoverChangeStars:!0,hoverOnClear:!0,zeroAsNull:!0},e.fn.ratingLocales.en={defaultCaption:"{rating} Stars",starCaptions:{.5:"Half Star",1:"One Star",1.5:"One & Half Star",2:"Two Stars",2.5:"Two & Half Stars",3:"Three Stars",3.5:"Three & Half Stars",4:"Four Stars",4.5:"Four & Half Stars",5:"Five Stars"},clearButtonTitle:"Clear",clearCaption:"Not Rated"},e.fn.rating.Constructor=a,e(document).ready(function(){var t=e("input.rating");t.length&&t.removeClass("rating-loading").addClass("rating-loading").rating()})});

</script>

<script>
    !function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery"],e):"object"==typeof module&&module.exports?module.exports=e(require("jquery")):e(window.jQuery)}(function(e){"use strict";e.fn.ratingLocales={},e.fn.ratingThemes={};var t,a;t={NAMESPACE:".rating",DEFAULT_MIN:0,DEFAULT_MAX:5,DEFAULT_STEP:.5,isEmpty:function(t,a){return null===t||void 0===t||0===t.length||a&&""===e.trim(t)},getCss:function(e,t){return e?" "+t:""},addCss:function(e,t){e.removeClass(t).addClass(t)},getDecimalPlaces:function(e){var t=(""+e).match(/(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/);return t?Math.max(0,(t[1]?t[1].length:0)-(t[2]?+t[2]:0)):0},applyPrecision:function(e,t){return parseFloat(e.toFixed(t))},handler:function(e,a,n,r,i){var l=i?a:a.split(" ").join(t.NAMESPACE+" ")+t.NAMESPACE;r||e.off(l),e.on(l,n)}},a=function(t,a){var n=this;n.$element=e(t),n._init(a)},a.prototype={constructor:a,_parseAttr:function(e,a){var n,r,i,l,s=this,o=s.$element,c=o.attr("type");if("range"===c||"number"===c){switch(r=a[e]||o.data(e)||o.attr(e),e){case"min":i=t.DEFAULT_MIN;break;case"max":i=t.DEFAULT_MAX;break;default:i=t.DEFAULT_STEP}n=t.isEmpty(r)?i:r,l=parseFloat(n)}else l=parseFloat(a[e]);return isNaN(l)?i:l},_parseValue:function(e){var t=this,a=parseFloat(e);return isNaN(a)&&(a=t.clearValue),!t.zeroAsNull||0!==a&&"0"!==a?a:null},_setDefault:function(e,a){var n=this;t.isEmpty(n[e])&&(n[e]=a)},_initSlider:function(e){var a=this,n=a.$element.val();a.initialValue=t.isEmpty(n)?0:n,a._setDefault("min",a._parseAttr("min",e)),a._setDefault("max",a._parseAttr("max",e)),a._setDefault("step",a._parseAttr("step",e)),(isNaN(a.min)||t.isEmpty(a.min))&&(a.min=t.DEFAULT_MIN),(isNaN(a.max)||t.isEmpty(a.max))&&(a.max=t.DEFAULT_MAX),(isNaN(a.step)||t.isEmpty(a.step)||0===a.step)&&(a.step=t.DEFAULT_STEP),a.diff=a.max-a.min},_initHighlight:function(e){var t,a=this,n=a._getCaption();e||(e=a.$element.val()),t=a.getWidthFromValue(e)+"%",a.$filledStars.width(t),a.cache={caption:n,width:t,val:e}},_getContainerCss:function(){var e=this;return"rating-container"+t.getCss(e.theme,"theme-"+e.theme)+t.getCss(e.rtl,"rating-rtl")+t.getCss(e.size,"rating-"+e.size)+t.getCss(e.animate,"rating-animate")+t.getCss(e.disabled||e.readonly,"rating-disabled")+t.getCss(e.containerClass,e.containerClass)},_checkDisabled:function(){var e=this,t=e.$element,a=e.options;e.disabled=void 0===a.disabled?t.attr("disabled")||!1:a.disabled,e.readonly=void 0===a.readonly?t.attr("readonly")||!1:a.readonly,e.inactive=e.disabled||e.readonly,t.attr({disabled:e.disabled,readonly:e.readonly})},_addContent:function(e,t){var a=this,n=a.$container,r="clear"===e;return a.rtl?r?n.append(t):n.prepend(t):r?n.prepend(t):n.append(t)},_generateRating:function(){var a,n,r,i=this,l=i.$element;n=i.$container=e(document.createElement("div")).insertBefore(l),t.addCss(n,i._getContainerCss()),i.$rating=a=e(document.createElement("div")).attr("class","rating-stars").appendTo(n).append(i._getStars("empty")).append(i._getStars("filled")),i.$emptyStars=a.find(".empty-stars"),i.$filledStars=a.find(".filled-stars"),i._renderCaption(),i._renderClear(),i._initHighlight(),n.append(l),i.rtl&&(r=Math.max(i.$emptyStars.outerWidth(),i.$filledStars.outerWidth()),i.$emptyStars.width(r)),l.appendTo(a)},_getCaption:function(){var e=this;return e.$caption&&e.$caption.length?e.$caption.html():e.defaultCaption},_setCaption:function(e){var t=this;t.$caption&&t.$caption.length&&t.$caption.html(e)},_renderCaption:function(){var a,n=this,r=n.$element.val(),i=n.captionElement?e(n.captionElement):"";if(n.showcaption){if(a=n.fetchCaption(r),i&&i.length)return t.addCss(i,"caption"),i.html(a),void(n.$caption=i);n._addContent("caption",'<br><div class="caption">'+a+"</div>"),n.$caption=n.$container.find(".caption")}},_renderClear:function(){var a,n=this,r=n.clearElement?e(n.clearElement):"";if(n.showClear){if(a=n._getClearClass(),r.length)return t.addCss(r,a),r.attr({title:n.clearButtonTitle}).html(n.clearButton),void(n.$clear=r);n._addContent("clear",'<div class="'+a+'" title="'+n.clearButtonTitle+'">'+n.clearButton+"</div>"),n.$clear=n.$container.find("."+n.clearButtonBaseClass)}},_getClearClass:function(){var e=this;return e.clearButtonBaseClass+" "+(e.inactive?"":e.clearButtonActiveClass)},_toggleHover:function(e){var t,a,n,r=this;e&&(r.hoverChangeStars&&(t=r.getWidthFromValue(r.clearValue),a=e.val<=r.clearValue?t+"%":e.width,r.$filledStars.css("width",a)),r.hoverChangeCaption&&(n=e.val<=r.clearValue?r.fetchCaption(r.clearValue):e.caption,n&&r._setCaption(n+"")))},_init:function(t){var a,n=this,r=n.$element.addClass("rating-input");return n.options=t,e.each(t,function(e,t){n[e]=t}),(n.rtl||"rtl"===r.attr("dir"))&&(n.rtl=!0,r.attr("dir","rtl")),n.starClicked=!1,n.clearClicked=!1,n._initSlider(t),n._checkDisabled(),n.displayOnly&&(n.inactive=!0,n.showClear=!1,n.showcaption=!1),n._generateRating(),n._initEvents(),n._listen(),a=n._parseValue(r.val()),r.val(a),r.removeClass("rating-loading")},_initEvents:function(){var e=this;e.events={_getTouchPosition:function(a){var n=t.isEmpty(a.pageX)?a.originalEvent.touches[0].pageX:a.pageX;return n-e.$rating.offset().left},_listenClick:function(e,t){return e.stopPropagation(),e.preventDefault(),e.handled===!0?!1:(t(e),void(e.handled=!0))},_noMouseAction:function(t){return!e.hoverEnabled||e.inactive||t&&t.isDefaultPrevented()},initTouch:function(a){var n,r,i,l,s,o,c,u,d=e.clearValue||0,p="ontouchstart"in window||window.DocumentTouch&&document instanceof window.DocumentTouch;p&&!e.inactive&&(n=a.originalEvent,r=t.isEmpty(n.touches)?n.changedTouches:n.touches,i=e.events._getTouchPosition(r[0]),"touchend"===a.type?(e._setStars(i),u=[e.$element.val(),e._getCaption()],e.$element.trigger("change").trigger("rating.change",u),e.starClicked=!0):(l=e.calculate(i),s=l.val<=d?e.fetchCaption(d):l.caption,o=e.getWidthFromValue(d),c=l.val<=d?o+"%":l.width,e._setCaption(s),e.$filledStars.css("width",c)))},starClick:function(t){var a,n;e.events._listenClick(t,function(t){return e.inactive?!1:(a=e.events._getTouchPosition(t),e._setStars(a),n=[e.$element.val(),e._getCaption()],e.$element.trigger("change").trigger("rating.change",n),void(e.starClicked=!0))})},clearClick:function(t){e.events._listenClick(t,function(){e.inactive||(e.clear(),e.clearClicked=!0)})},starMouseMove:function(t){var a,n;e.events._noMouseAction(t)||(e.starClicked=!1,a=e.events._getTouchPosition(t),n=e.calculate(a),e._toggleHover(n),e.$element.trigger("rating.hover",[n.val,n.caption,"stars"]))},starMouseLeave:function(t){var a;e.events._noMouseAction(t)||e.starClicked||(a=e.cache,e._toggleHover(a),e.$element.trigger("rating.hoverleave",["stars"]))},clearMouseMove:function(t){var a,n,r,i;!e.events._noMouseAction(t)&&e.hoverOnClear&&(e.clearClicked=!1,a='<span class="'+e.clearCaptionClass+'">'+e.clearCaption+"</span>",n=e.clearValue,r=e.getWidthFromValue(n)||0,i={caption:a,width:r,val:n},e._toggleHover(i),e.$element.trigger("rating.hover",[n,a,"clear"]))},clearMouseLeave:function(t){var a;e.events._noMouseAction(t)||e.clearClicked||!e.hoverOnClear||(a=e.cache,e._toggleHover(a),e.$element.trigger("rating.hoverleave",["clear"]))},resetForm:function(t){t&&t.isDefaultPrevented()||e.inactive||e.reset()}}},_listen:function(){var a=this,n=a.$element,r=n.closest("form"),i=a.$rating,l=a.$clear,s=a.events;return t.handler(i,"touchstart touchmove touchend",e.proxy(s.initTouch,a)),t.handler(i,"click touchstart",e.proxy(s.starClick,a)),t.handler(i,"mousemove",e.proxy(s.starMouseMove,a)),t.handler(i,"mouseleave",e.proxy(s.starMouseLeave,a)),a.showClear&&l.length&&(t.handler(l,"click touchstart",e.proxy(s.clearClick,a)),t.handler(l,"mousemove",e.proxy(s.clearMouseMove,a)),t.handler(l,"mouseleave",e.proxy(s.clearMouseLeave,a))),r.length&&t.handler(r,"reset",e.proxy(s.resetForm,a),!0),n},_getStars:function(e){var t,a=this,n='<span class="'+e+'-stars">';for(t=1;t<=a.stars;t++)n+='<span class="star">'+a[e+"Star"]+"</span>";return n+"</span>"},_setStars:function(e){var t=this,a=arguments.length?t.calculate(e):t.calculate(),n=t.$element,r=t._parseValue(a.val);return n.val(r),t.$filledStars.css("width",a.width),t._setCaption(a.caption),t.cache=a,n},showStars:function(e){var t=this,a=t._parseValue(e);return t.$element.val(a),t._setStars()},calculate:function(e){var a=this,n=t.isEmpty(a.$element.val())?0:a.$element.val(),r=arguments.length?a.getValueFromPosition(e):n,i=a.fetchCaption(r),l=a.getWidthFromValue(r);return l+="%",{caption:i,width:l,val:r}},getValueFromPosition:function(e){var a,n,r=this,i=t.getDecimalPlaces(r.step),l=r.$rating.width();return n=r.diff*e/(l*r.step),n=r.rtl?Math.floor(n):Math.ceil(n),a=t.applyPrecision(parseFloat(r.min+n*r.step),i),a=Math.max(Math.min(a,r.max),r.min),r.rtl?r.max-a:a},getWidthFromValue:function(e){var t,a,n=this,r=n.min,i=n.max,l=n.$emptyStars;return!e||r>=e||r===i?0:(a=l.outerWidth(),t=a?l.width()/a:1,e>=i?100:(e-r)*t*100/(i-r))},fetchCaption:function(e){var a,n,r,i,l,s=this,o=parseFloat(e)||s.clearValue,c=s.starCaptions,u=s.starCaptionClasses;return o&&o!==s.clearValue&&(o=t.applyPrecision(o,t.getDecimalPlaces(s.step))),i="function"==typeof u?u(o):u[o],r="function"==typeof c?c(o):c[o],n=t.isEmpty(r)?s.defaultCaption.replace(/\{rating}/g,o):r,a=t.isEmpty(i)?s.clearCaptionClass:i,l=o===s.clearValue?s.clearCaption:n,'<span class="'+a+'">'+l+"</span>"},destroy:function(){var a=this,n=a.$element;return t.isEmpty(a.$container)||a.$container.before(n).remove(),e.removeData(n.get(0)),n.off("rating").removeClass("rating rating-input")},create:function(e){var t=this,a=e||t.options||{};return t.destroy().rating(a)},clear:function(){var e=this,t='<span class="'+e.clearCaptionClass+'">'+e.clearCaption+"</span>";return e.inactive||e._setCaption(t),e.showStars(e.clearValue).trigger("change").trigger("rating.clear")},reset:function(){var e=this;return e.showStars(e.initialValue).trigger("rating.reset")},update:function(e){var t=this;return arguments.length?t.showStars(e):t.$element},refresh:function(t){var a=this,n=a.$element;return t?a.destroy().rating(e.extend(!0,a.options,t)).trigger("rating.refresh"):n}},e.fn.rating=function(n){var r=Array.apply(null,arguments),i=[];switch(r.shift(),this.each(function(){var l,s=e(this),o=s.data("rating"),c="object"==typeof n&&n,u=c.theme||s.data("theme"),d=c.language||s.data("language")||"en",p={},h={};o||(u&&(p=e.fn.ratingThemes[u]||{}),"en"===d||t.isEmpty(e.fn.ratingLocales[d])||(h=e.fn.ratingLocales[d]),l=e.extend(!0,{},e.fn.rating.defaults,p,e.fn.ratingLocales.en,h,c,s.data()),o=new a(this,l),s.data("rating",o)),"string"==typeof n&&i.push(o[n].apply(o,r))}),i.length){case 0:return this;case 1:return void 0===i[0]?this:i[0];default:return i}},e.fn.rating.defaults={theme:"",language:"en",stars:5,filledStar:'<i class="fas fa-star"></i>',emptyStar:'<i class="far fa-star"></i>',containerClass:"",size:"md",animate:!0,displayOnly:!1,rtl:!1,showClear:!0,showcaption:!0,starCaptionClasses:{.5:"badge badge-pill badge-danger",1:"badge badge-pill badge-danger",1.5:"badge badge-pill badge-warning",2:"badge badge-pill badge-warning",2.5:"badge badge-pill badge-info",3:"badge badge-pill badge-info",3.5:"badge badge-pill badge-primary",4:"badge badge-pill badge-primary",4.5:"badge badge-pill badge-success",5:"badge badge-pill badge-success"},clearButton:'<i class="fa fa-minus-circle"></i>',clearButtonBaseClass:"clear-rating",clearButtonActiveClass:"clear-rating-active",clearCaptionClass:"label label-default",clearValue:null,captionElement:null,clearElement:null,hoverEnabled:!0,hoverChangeCaption:!0,hoverChangeStars:!0,hoverOnClear:!0,zeroAsNull:!0},e.fn.ratingLocales.en={defaultCaption:"{rating} Stars",starCaptions:{.5:"Half Star",1:"One Star",1.5:"One & Half Star",2:"Two Stars",2.5:"Two & Half Stars",3:"Three Stars",3.5:"Three & Half Stars",4:"Four Stars",4.5:"Four & Half Stars",5:"Five Stars"},clearButtonTitle:"Clear",clearCaption:"Not Rated"},e.fn.rating.Constructor=a,e(document).ready(function(){var t=e("input.rating");t.length&&t.removeClass("rating-loading").addClass("rating-loading").rating()})});
</script>