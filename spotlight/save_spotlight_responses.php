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
		$resultset = array();
		
		while($row = mysqli_fetch_array($result)) {
			$resultset[] = $row;
		}

		if(!empty($resultset)) 
			return $resultset;
		return array();
	}
	
	function insertQuery($query) {
		global $dbc;
	    mysqli_query($dbc, $query);
	    $insert_id = mysqli_insert_id($dbc);
	    return $insert_id;
	}
	
	$answer_id = '';
	$question_id = '';
	
	if (isset($_POST['answer']) && !empty($_POST['answer'])) {
		$answer_id = mysqli_real_escape_string($dbc, strip_tags($_POST['answer']));
	}
	
	if (isset($_POST['question']) && !empty($_POST['question'])) {
		$question_id = mysqli_real_escape_string($dbc, strip_tags($_POST['question']));
	}
	
	if (empty($answer_id) && isset($_POST['answer_id']) && !empty($_POST['answer_id'])) {
		$answer_id = mysqli_real_escape_string($dbc, strip_tags($_POST['answer_id']));
	}
	
	if (empty($question_id) && isset($_POST['question_id']) && !empty($_POST['question_id'])) {
		$question_id = mysqli_real_escape_string($dbc, strip_tags($_POST['question_id']));
	}
	
	if (empty($answer_id) || empty($question_id)) {
		echo json_encode(array('error' => 'Missing required parameters'));
		exit();
	}
	
	$inquiry_id = $question_id;
	$check_query = "SELECT * FROM spotlight_ballot WHERE question_id = '$question_id' AND ballot_user = '" . $_SESSION["user"] . "'";
	$existing_vote = runQuery($check_query);
	
	if (!empty($existing_vote)) {
		echo json_encode(array('error' => 'You have already voted on this spotlight'));
		exit();
	}
	
	$query = "INSERT INTO spotlight_ballot(question_id, answer_id, ballot_user) VALUES ('" . $question_id ."','" . $answer_id . "','" . $_SESSION["user"] . "')";
    $insert_id = insertQuery($query);
    
    $data = '';
    
    if(!empty($insert_id)) {
		
      	$query = "SELECT sn.*, u.first_name, u.last_name 
                  FROM spotlight_nominee sn 
                  JOIN users u ON sn.assignment_user = u.user 
                  WHERE sn.question_id = " . $question_id;
        $nominees = runQuery($query);

		$data .='<div class="h-100 p-4 bg-white border rounded-3 shadow-sm mb-3">
					<h5 class="dark-gray"><i class="fa-solid fa-ranking-star"></i> Spotlight Results </h5>
					<hr class="hr-line">';

		if (!empty($nominees)) {
			$query = "SELECT count(answer_id) as total_count FROM spotlight_ballot WHERE question_id = " . $question_id;
			$total_rating = runQuery($query);

			foreach($nominees as $k=>$v) {
				
				$assignment_id = $nominees[$k]["assignment_id"];
				$nominee_first_name = htmlspecialchars(strip_tags($nominees[$k]["first_name"] ?? ''));
				$nominee_last_name = htmlspecialchars(strip_tags($nominees[$k]["last_name"] ?? ''));
				$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;

				$query = "SELECT count(answer_id) as answer_count FROM spotlight_ballot WHERE question_id = " .$question_id . " AND answer_id = " . $assignment_id;
				$answer_rating = runQuery($query);

				$answers_count = 0;

				if(!empty($answer_rating)) {
					$answers_count = $answer_rating[0]["answer_count"];
				}

				$percentage = 0;

				if(!empty($total_rating) && $total_rating[0]["total_count"] > 0) {

					$percentage = ( $answers_count / $total_rating[0]["total_count"] ) * 100;

					if(is_float($percentage)) {
						$percentage = number_format($percentage,2);
					}
				}

				if (80 <= $percentage && $percentage <= 100) {
					$percentage_class = 'bg-success';
				} else if (50 <= $percentage && $percentage <= 79){
					$percentage_class = 'bg-info';
				} else if (25 <= $percentage && $percentage <= 49){
					$percentage_class = 'bg-warning';
				} else if (1 <= $percentage && $percentage <= 24){
					$percentage_class = 'bg-danger';
				} else { 
					$percentage_class = 'bg-secondary'; 
				}

				$data .='<div class="mb-3">
					<strong class="mb-3">'.$nominee_full_name.' <small class="text-secondary float-end">'.$percentage.' %</small></strong>
					<div class="progress">
						<div class="progress-bar progress-bar-striped '.$percentage_class.'" role="progressbar" aria-label="Example with label" style="width:'.$percentage.'%" aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>';
			}
		}

		$data .='</div>

			<div class="row">
				<div class="col-md-8">
					<div class="">
						<button type="button" class="btn btn-primary btn-lg shadow w-100" onclick="readspotlightReportDetails('.$inquiry_id.');"><i class="fa-solid fa-circle-arrow-right"></i> Next</button>
					</div>
				</div>
			</div>';
    } else {
		$data = '<div class="alert alert-danger">Error submitting your vote. Please try again.</div>';
	}
		
echo $data;
	
?>