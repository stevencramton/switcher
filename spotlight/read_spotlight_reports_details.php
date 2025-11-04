<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_reports')){
	header("Location:../../index.php?msg1");
	exit();
}
?>

<?php

if (isset($_SESSION['id'])) {
	if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
  	  	$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
					
		if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
        	$ballot_votes = mysqli_num_rows($ballot_results);
		} 
					
		$query = "SELECT * FROM spotlight_response WHERE question_id = '$inquiry_id' ORDER BY response_display_order ASC";	
					
			if (!$result = mysqli_query($dbc, $query)) {
				exit();
			}
					
			$query_name = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";	
					
			if (!$result_name = mysqli_query($dbc, $query_name)) {
				exit();
			}
					
			if (mysqli_num_rows($result_name) > 0) {
	  
				while ($row = mysqli_fetch_assoc($result_name)) {
					
					$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
					
					$data ='<h5 class="dark-gray">
								<i class="fa-solid fa-square-spotlight-horizontal"></i> <span class="" id="">'.$inquiry_name.'</span>
								<button type="button" class="btn btn-outline-secondary btn-sm shadow-sm float-end" onclick="cancelspotlightReportDetails();">
									<i class="fa-solid fa-backward"></i>
								</button>
							</h5><hr>';
				}
										
			}
			
			$data .='<div class="table-responsive mb-3">
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
			
			$query_user_choice = "SELECT * FROM spotlight_ballot JOIN spotlight_response ON spotlight_ballot.question_id = spotlight_response.question_id JOIN users ON spotlight_ballot.ballot_user = users.user WHERE spotlight_ballot.question_id = '$inquiry_id' AND answer_id = response_id ORDER BY response_answer ASC";	
			
			if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
				exit();
			}

			if (mysqli_num_rows($result_user_choice) > 0) {

				while ($row = mysqli_fetch_assoc($result_user_choice)) {
					
					$user_profile_pic = mysqli_real_escape_string($dbc, strip_tags($row['profile_pic']));
					$ballot_user_first_name = htmlspecialchars(strip_tags($row['first_name']));
					$ballot_user_last_name = htmlspecialchars(strip_tags($row['last_name']));
					$ballot_user_full_name = $ballot_user_first_name . ' ' . $ballot_user_last_name;
					$ballot_username = htmlspecialchars(strip_tags($row['ballot_user']));
					$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
					
					$data .='<tr>
								<td class="align-middle" style="width:6%;"><img src="' . $user_profile_pic . '" class="profile-photo"></td>
								<td class="align-middle" style="width:15%;">'.$ballot_user_full_name.'</td>
								<td class="align-middle">'.$ballot_username.'</td> 
								<td class="align-middle">'.$response_answer.'</td>
							</tr>';
				}
			}
			
			$data .='</tbody></table></div>';
			
			$data .='<div class="table-responsive">
						<table class="table mb-3" id="add_new_answer_row" width="100%">
							<thead class="table-light">
								<tr>
									<th>Votes</th>
									<th colspan="2">Count</th>
								</tr>
							</thead>
				
							<tbody id="">';
				
						$data .='<tr class="" id="" data-id="">
									<td>Total votes</td>
									<td colspan="2">'.$ballot_votes.'</td>
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
						
										$response_id = mysqli_real_escape_string($dbc, strip_tags($row['response_id']));
										$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
										$query_ballot_answer_count = "SELECT * FROM spotlight_ballot WHERE answer_id = '$response_id'";

										if ($ballot_answer_results = mysqli_query($dbc, $query_ballot_answer_count)){
					           		 		$ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
				   						} 
							
										$data .='<tr>
													<td>'.$response_answer.'</td>
													<td>'.$ballot_answer_votes.'</td>
													<td>'.$ballot_answer_votes.'</td>
												 </tr>';
									}

								} else {
        							$data .='';
								}
							
							$data .='</tbody>';
							  
						} else {
							$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
							$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
				
							if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
           		 				$ballot_votes = mysqli_num_rows($ballot_results);
							} 
						
							$data .='<p>No data available</p>';
						}
					
						$data .='</tbody>
							</table>
						</div>';

		echo $data;

	}

mysqli_close($dbc);
			
?>