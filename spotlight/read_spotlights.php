<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
	header("Location:../../index.php?msg1");
	exit();
}

?>

<?php
	
	$user = $_SESSION["user"];
	
	$query = "SELECT DISTINCT question_id from spotlight_ballot WHERE ballot_user = '$user'"; 
	$result = getIds($query);

	function getIds($query) {
		
		global $dbc;
		
	    $result = mysqli_query($dbc,$query);
		
	    while($row = mysqli_fetch_array($result)) {
	        $resultset[] = $row[0];
	    }
		
	    if(!empty($resultset))
	        return $resultset;
	}
	
	$condition = "";
	
	if(!empty($result)) {
		$condition = " AND inquiry_id NOT IN (" . implode(",", $result) . ")";
	}
	
	$data = '';
	
	if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
 	   $inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
	
	   $query = "SELECT spotlight_inquiry.inquiry_id, spotlight_inquiry.inquiry_author, spotlight_inquiry.inquiry_creation_date, spotlight_inquiry.inquiry_name, spotlight_inquiry.inquiry_image, spotlight_inquiry.inquiry_question, spotlight_inquiry.inquiry_info, spotlight_inquiry.inquiry_status, spotlight_assignment.assignment_user, spotlight_assignment.spotlight_id FROM spotlight_inquiry INNER JOIN spotlight_assignment ON spotlight_inquiry.inquiry_id = spotlight_assignment.spotlight_id AND spotlight_assignment.assignment_user = '$user' WHERE spotlight_inquiry.inquiry_id = '$inquiry_id' AND inquiry_status = 'Active' ". $condition ." ORDER BY inquiry_display_order ASC limit 1";
	
}
	
    $questions = runQuery($query);
	
	function runQuery($query) {
		
		global $dbc;
		
		$result = mysqli_query($dbc, $query);
		
		while($row = mysqli_fetch_array($result)) {
			$resultset[] = $row;
		}
		
		if(!empty($resultset))
			return $resultset;
	}
	
	if(!empty($questions)) {
		
		$questions_name = $questions[0]["inquiry_name"] ?? '';
		
		$data .='<h5 class="dark-gray"><i class="fa-solid fa-ranking-star"></i> '.$questions_name.' 
					<button type="button" class="btn btn-light-gray btn-sm float-end" onclick="cancelspotlightReportDetails();">
						<i class="fa-solid fa-rotate-left"></i>
					</button>
				 </h5>
				 
		<hr class="hr-line">
		
		<div class="row g-3">
			<div class="col-md-8">
				<div class="bg-dark rounded-3 border shadow" style="padding: 2rem!important;">
					<h2 class="display-5 fw-bold text-white mb-3">
						
						<img src="'.$questions[0]["inquiry_image"].'" alt="" class="img-fluid rounded-3 mb-2"height="75" width="75" alt="..." id="read_audit_profile_pic">
						<span class="text-white">Nomination Form</span>
						<a href="#" class="btn btn-dark btn-sm float-end" id="" style="cursor:pointer;color:#20c997;" onclick="cancelspotlightReportDetails();">
							<i class="fa-solid fa-rotate-left"></i>
						</a>
					</h2>
					
					<form class="" id="spotlight-voting-form">
					            <input type="hidden" name="question" id="question" value="'.$questions[0]["inquiry_id"].'">
					            <small class="lead fw-normal text-white opacity-75"> Select one person from the list to nominate.</small>';
            
					 	$query = "SELECT spotlight_nominee.*, users.first_name, users.last_name 
					              FROM spotlight_nominee 
					              JOIN users ON spotlight_nominee.assignment_user = users.user 
					              WHERE question_id = " . $questions[0]["inquiry_id"] . " 
					              ORDER BY assignment_display_order ASC";

					    $answers = runQuery($query);

					    if(!empty($answers)) {
					        $data .= '<div class="form-floating mb-3 mt-2">
					                    <select class="form-select" name="answer" id="answer" onchange="enableSubmitButton();">
					                        <option value="">Select a nominee</option>';

					        foreach ($answers as $k => $v) {
					            $full_name = htmlspecialchars($answers[$k]["first_name"] . ' ' . $answers[$k]["last_name"]);
					            $data .= '<option value="' . $answers[$k]["assignment_id"] . '">'. $full_name .'</option>';
					        }

					        $data .= '</select>
					                    <label for="answer" style="color: #7f878e;">Nominate a person...</label>
					                  </div>';
        
					        $data .='<small class="lead fw-normal text-white opacity-75"> Share private comments regarding your nominee.</small>
					                <div class="mb-3 mt-2">
					                    <textarea class="form-control private-note" id="nominee-private-notes" name="nominee-private-notes" rows="5" placeholder="Add a private note..."></textarea>
					                </div>
                
					                <div class="row">
					                    <div class="col-md-12">
					                        <hr class="mb-3" style="border:1px dashed lightgray;">
                            
					                            <div class="mb-2" style="padding-top:6px;">
					                                <small class="lead fw-normal text-hot" style="font-family:Chalkduster, fantasy;">
					                                    Ensure that the correct nominee is selected!
					                                </small>
					                            </div>
                    
					                            <div class="row g-2 mt-2">
					                                <div class="col-md-10">
					                                    <button type="button" class="btn btn-info btn-lg text-white w-100" id="submit-vote" onclick="addspotlight();" style="height:50px;" disabled>
					                                        <i class="fa-regular fa-circle-check"></i> Submit
					                                    </button>
					                                </div>
					                                <div class="col-md-2">
					                                    <button type="reset" class="btn btn-dark btn-lg reset-nominee-form ripple w-100" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Reset Form" style="height:50px;" onclick="disableSubmitButton();">
					                                        <i class="fas fa-eraser"></i> 
					                                    </button>
					                                </div>
					                            </div>
                    
					                        </div>
					                    </div>
					                </form>									
					            </div>
					            </div>
        
					            <div class="col-md-4">
    
					                <div class="text-center mb-3 mt-3">
					                    <img src="img/avatar.png" id="contact_card_image" class="img-fluid img-thumbnail rounded-circle shadow" alt="" style="width:203px;">
					                </div>
					                <li class="list-group-item text-center mb-2">
					                    <span class="contact_card_info" id="">
					                        <span class="dark-gray fw-bold fs-3 p-3" id="display_my_name"><span class="dark-gray fw-bold fs-3">Nominee</span></span>
					                    </span>
					                </li>
					                <li class="list-group-item text-center mb-3">
					                    <span class="contact_card_info" id="">
					                        <div class="" style="color:#ff9800;"><i class="fas fa-star golden-star"></i><i class="fas fa-star golden-star"></i><i class="fas fa-star golden-star"></i><i class="fas fa-star golden-star"></i><i class="fas fa-star golden-star"></i></div>
					                    </span>
					                </li>
                
					                <div class="shadow-sm border rounded bg-white p-1 mb-3" id="nominee-card">
					                    <ul class="list-group list-group-flush">
					                        <li class="list-group-item">
					                            <span class="dark-gray fw-bold">Title</span>
					                            <span class="float-end" id="display_my_title"><i class="fa-solid fa-circle-question text-concrete"></i></span>
					                        </li>
					                        <li class="list-group-item">
					                            <span class="dark-gray fw-bold">Agency</span>
					                            <span class="float-end" id="display_my_agency"><i class="fa-solid fa-circle-question text-concrete"></i></span>
					                        </li>
					                        <li class="list-group-item">
					                            <span class="dark-gray fw-bold">Location</span>
					                            <span class="float-end" id="display_my_location"><i class="fa-solid fa-circle-question text-concrete"></i></span>
					                        </li>
					                        <li class="list-group-item">
					                            <span id="pronoun_names" class="contact_card_info">
					                                <span class="dark-gray fw-bold">Pronouns</span>
					                                <span class="float-end" id="pronouns"><i class="fa-solid fa-circle-question text-concrete"></i></span>
					                            </span>
					                        </li>
					                    </ul>
					                </div>
            						<div class="d-grid shadow-sm bg-white rounded p-3">
					                    <button type="button" class="btn btn-light-gray shadow-sm w-100" style="height:53px;" onclick="cancelspotlightReportDetails();">
					                        <i class="fa-solid fa-rotate-left"></i> Close Form
					                    </button>
					                </div>
         						</div> 
    						</div>';

					$data .='<div class="d-flex align-items-center text-break mb-3">
					    <div class="flex-grow-1 ms-3">
					        <div class="fs-5">
					            <div class="dark-gray fw-bold mt-3">'.$questions[0]["inquiry_question"].'</div>
					            <p><small class="text-secondary"><i>'.$questions[0]["inquiry_info"].'</i></small></p>
					        </div>
					    </div>
					</div>

					<script>
					function enableSubmitButton() {
					    var selectedValue = $("#answer").val();
					    if (selectedValue !== "") {
					        $("#submit-vote").prop("disabled", false);
					    } else {
					        $("#submit-vote").prop("disabled", true);
					    }
					}

					function disableSubmitButton() {
					    $("#submit-vote").prop("disabled", true);
					}
					</script>';

					$data .='';
								
		
		} // End if !empty
		
		$data .='';
		
	} else {
		
		// spotlight closure date
		$date = date_default_timezone_set("America/New_York"); 
	 	$date = date('m-d-Y g:i A');
		
		$data .='<div class="tab-content">
					<div class="tab-pane fade in active show mb-3" id="spotlight_response_tab">	
						
						<!-- Read closed if spotlight is inactive - currently this reads even if spotlight is active but completed.
						<div class="shadow-sm p-3 mb-5 bg-white rounded">
							<div class="jumbotron">
								<h2 class="display-5 dark-gray mb-3"><i class="fa-solid fa-circle-info"></i> spotlights are currently closed!</h2>
								<p class="dark-gray mb-3">We are sorry, there are no spotlights to be found... be sure to check back later!</p>
								<p><img src="ajax/spotlight/spotlight_image/sorry_we_are_closed.jpg" height="300px;" width="500px;" class="img-thumbnail"></p>
								<small class="text-muted">spotlights are currently closed:</small> <small><code>'.$date.'</code></small>
							</div>
						</div>
						-->
						
						<!-- Displays be default -->
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
									<button type="button" class="btn btn-outline-primary btn-lg px-4 gap-3" id="show-spotlight-results">
										<i class="fa-solid fa-ranking-star"></i> View spotlight Results
									</button>
									<button type="button" class="btn btn-primary btn-lg px-4 gap-3" onclick="cancelspotlightReportDetails();">
										<i class="fa-solid fa-arrow-rotate-left"></i>
									</button>
								</div>
							</div>
						</div> <!-- End col-lg-6 mx-auto -->
					</div> <!-- End px-4 py-4 my-4 text-center -->
 				 </div> <!-- End spotlight_response_tab -->
				
				 <div class="tab-pane fade in" id="spotlight_results_tab">
					<h5 class="dark-gray">
						<i class="fa-solid fa-ranking-star"></i> Spotlight Results 
						<button type="button" class="btn btn-light-gray btn-sm float-end" onclick="closespotlightReportDetails();">
							<i class="fas fa-undo-alt"></i>
						</button>
					</h5>
					<hr class="hr-line">';
					
					$data .='<div class="accordion accordion-flush" id="accordionspotlightReports">';
					
					$report_requestor = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
							
					$query_reports = "SELECT * FROM spotlight_inquiry JOIN spotlight_assignment ON spotlight_inquiry.inquiry_id = spotlight_assignment.spotlight_id JOIN spotlight_ballot ON spotlight_assignment.spotlight_id = spotlight_ballot.question_id WHERE assignment_user = ballot_user AND ballot_user = '$report_requestor' ORDER BY spotlight_inquiry.inquiry_display_order ASC";
							
					if ($result_reports = mysqli_query($dbc, $query_reports)) {
						confirmQuery($result_reports);
                            
						while ($row = mysqli_fetch_array($result_reports)) {
						
							$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_id'] ?? ''));
							$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
							$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
							$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
							$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
							$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? ''));
							$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info'] ?? ''));
							$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
							
							if ($inquiry_image == ''){
							
								$inquiry_image = 'media/links/default_spotlight_image.png';
							
							} else {
							
								$inquiry_image = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_image']  ?? ''));
									
							}
						
							// Count spotlight answers where question_id is equal to the inquiry_id from the spotlight_inquiry table
							$query_spotlight_answers = "SELECT * FROM spotlight_nominee WHERE question_id = '$inquiry_id'"; // Select all from the users table

							if ($spotlight_answers_results = mysqli_query($dbc, $query_spotlight_answers)){
								$spotlight_answers_count = mysqli_num_rows($spotlight_answers_results); // Count the number of rows in the results
							} 
						
							// Count enrolled users from the spotlight_assignments table
							$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'"; // Select all from the users table

							if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
								$enrolled_users = mysqli_num_rows($enrollment_results); // Count the number of rows in the results
							} 
						
							// Count ballot votes where question_id is equal to the inquiry_id from the spotlight_inquiry table
							$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'"; // Select all from the users table

							if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
								$ballot_votes = mysqli_num_rows($ballot_results); // Count the number of rows in the results
							} 
							
							if (empty($enrolled_users)) {
				
								$participation_rate = 0;
								$participation_rate = $participation_rate * 100;
								$percentage_rate = number_format($participation_rate, 2, '.', '');
				
							} else {
				
								$participation_rate = $ballot_votes / $enrolled_users;
								$participation_rate = $participation_rate * 100;
								$percentage_rate = number_format($participation_rate, 2, '.', '');
				
							}
			
							$data .='<div class="accordion-item border mb-2">
							 			<h2 class="accordion-header">
							 				<button type="button" class="accordion-button d-flex justify-content-between align-items-center collapsed" style="padding: 0.5rem;" data-bs-toggle="collapse" data-bs-target="#accord_'.$inquiry_id.'" aria-expanded="false" aria-controls="flush-collapseOne">
							 		        	<img src="'.$inquiry_image.'" class="profile-photo ms-2"> 
												<span class="w-25"><strong class="dark-gray ms-3">'.$inquiry_name.'</strong></span>
												<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_creation_date.'" disabled>
													<i class="fa-solid fa-clock text-secondary"></i> Date
												</span>
												<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_author.'" disabled>
													<i class="fa-solid fa-circle-user text-secondary"></i> Author
												</span>
												<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="spotlight Answers" disabled>
													<i class="fa-solid fa-clipboard-question text-secondary"></i> '.$spotlight_answers_count.'
												</span>
												<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
													<i class="fa-solid fa-users text-secondary"></i> '.$enrolled_users.'
												</span>
												<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
													<i class="fa-solid fa-check-to-slot text-secondary"></i> '.$ballot_votes.'
												</span>
												<span class="flex-grow-1 ms-2" style="width:125px;">
													<div class="progress" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="spotlight Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
														<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
													</div>
												</span>';
								
												if($inquiry_status == "Active"){
							
							                    	$data .='<span class="btn btn-light btn-sm bg-mint ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" style="width:50px;" disabled><i class="fa-solid fa-circle-check"></i></span>';
					
							            		} else if($inquiry_status == "Paused"){
							
							               		 	$data .='<span class="btn btn-light btn-sm bg-hot ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" style="width:50px;" disabled><i class="fa-solid fa-circle-pause"></i></span>';
							
												} else if($inquiry_status == "Closed"){
							
							               		 	$data .='<span class="btn btn-light btn-sm bg-concrete ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" style="width:50px;" disabled><i class="fa-solid fa-folder-closed"></i></span>';
							
												} else {}
								
							 		$data .='</button>
										</h2>';
									
							   $data .='<div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionspotlightReports">
							 	  			<div class="accordion-body">';
							
										$data .='<div class="fw-bold fs-5 dark-gray mt-2">'.$inquiry_question.'</div>';
						
										$data .='<hr>';
							
										$query_two = "SELECT spotlight_nominee.*, users.first_name, users.last_name 
										              FROM spotlight_nominee 
										              JOIN users ON spotlight_nominee.assignment_user = users.user
										              WHERE spotlight_nominee.question_id = '$inquiry_id'
										              ORDER BY spotlight_nominee.assignment_display_order ASC";
										
											if (!$result_two = mysqli_query($dbc, $query_two)) {
												exit();
											}
			
											if (mysqli_num_rows($result_two) > 0) {
								
												// Set to calculate top choice
												$highest_value = 0;
												$winning_items = array();
								
												while ($row = mysqli_fetch_assoc($result_two)) {
						
													$assignment_id = mysqli_real_escape_string($dbc, strip_tags($row['assignment_id']  ?? ''));
													$assignment_user = htmlspecialchars(strip_tags($row['assignment_user']  ?? ''));
													$first_name = htmlspecialchars(strip_tags($row['first_name']  ?? ''));
													$last_name = htmlspecialchars(strip_tags($row['last_name']  ?? ''));
													
													$full_name = $first_name . ' ' . $last_name;
									
													// Count enrolled users from the spotlight_assignments table
													$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'"; // Select all from the users table

													if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
														$enrolled_users = mysqli_num_rows($enrollment_results); // Count the number of rows in the results
													} 
					
													// Count ballot votes where question_id is equal to the inquiry_id from the spotlight_inquiry table
													$query_ballot_answer_count = "SELECT * FROM spotlight_ballot WHERE answer_id = '$assignment_id'"; // Select all from the users table

													if ($ballot_answer_results = mysqli_query($dbc, $query_ballot_answer_count)){
											         	$ballot_answer_votes = mysqli_num_rows($ballot_answer_results); // Count the number of rows in the results
										   			} 
									
													// Calculate the percentage of each option that was voted on
													$percentage_rate = ($ballot_answer_votes / $ballot_votes) * 100;
									
													// Check if value is higher than current highest value
													if ($ballot_answer_votes > $highest_value) {
														
														$highest_value = $ballot_answer_votes;
														$winning_items = array($full_name );
														
													} else if ($ballot_answer_votes == $highest_value) {
														array_push($winning_items, $full_name);
													}
																		
													$percentage_rate_new = round($percentage_rate, 2);
									
													$data .='<span class="badge bg-light-gray mb-1 me-1">'.$ballot_answer_votes.'</span>
															 <span class="fw-bold text-secondary">'.$first_name.' '.$last_name.'</span>
															 <span class="fw-bold text-secondary float-end">
															 <small>'.$percentage_rate_new.'%</small>
															 </span>';
												
													if ($percentage_rate >= 0 && $percentage_rate <= 25) {
													
														// Danger
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-danger" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
													} else if ($percentage_rate >= 26 && $percentage_rate <= 50) {
													
														// Warning
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-warning" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
													} else if ($percentage_rate >= 51 && $percentage_rate <= 75) {
													
														// Info
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
													} else if ($percentage_rate >= 76 && $percentage_rate <= 100) {
													
														// Success
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-success" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
													} else {}
												
													$data .='';
													
													}
								
													// Display winning item or items and their value
													if (count($winning_items) == 1) {
								   				 	
														$data .='<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
														   			<div class="d-flex align-items-center text-break">
														   				<div class="flex-shrink-0">
														   					<!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
														   					<img src="img/spotlight_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
														   				</div>
														   				<div class="flex-grow-1 ms-3">
														   					<div class="fs-5">
														   						<div class="fw-bold mt-3">';
																									
																				if ($enrolled_users != $ballot_votes) { 
																			
																					$data .='<span class="">The leading choice is...</span>';
																							 
																				} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																			
																					$data .='<span class="">And the Winning choice is!</span>';
																							 
																				} else if ($ballot_votes == 0) {
																					
																					$data .='<span class="">This spotlight has no votes.</span>';
																				
																				} else {}
																										
																					$data .='<div class="btn-group btn-group-sm float-end" role="group" aria-label="Small button group">
																								<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
																									<i class="fa-solid fa-users"></i> '.$enrolled_users.'
																								</span>
																								<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
																									<i class="fa-solid fa-check-to-slot"></i> '.$ballot_votes.'
																								</span>';
																					
																								if ($enrolled_users != $ballot_votes) {
																					
																									$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Completed" disabled>
																												<i class="fa-solid fa-bars-progress"></i> '.$ballot_votes.'
																											 </span>';
																									 
																								} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																					
																									$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="spotlight Completed" disabled>
																												<i class="fa-solid fa-circle-check"></i> '.$ballot_votes.'
																										     </span>';
																									 
																								} else if ($ballot_votes == 0) {
																							
																									$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Started" disabled>
																												<i class="fa-solid fa-circle-info"></i> '.$ballot_votes.'
																											 </span>';
																								} else {}
																					
																									$data .='</div>
																										</div>
														   											<p class="mb-0"><small class=""><i>'.$winning_items[0].'</i></small></p>';
																									
									   					// $query_user_choice = "SELECT * FROM spotlight_ballot JOIN spotlight_nominee ON spotlight_ballot.question_id = spotlight_nominee.question_id WHERE spotlight_ballot.question_id = '$inquiry_id' AND ballot_user = '$report_requestor' AND answer_id = assignment_id ";	
														
														$query_user_choice = "SELECT spotlight_ballot.*, spotlight_nominee.*, users.first_name, users.last_name
														                      FROM spotlight_ballot 
														                      JOIN spotlight_nominee ON spotlight_ballot.question_id = spotlight_nominee.question_id 
														                      JOIN users ON spotlight_nominee.assignment_user = users.user
														                      WHERE spotlight_ballot.question_id = '$inquiry_id' 
														                      AND spotlight_ballot.ballot_user = '$report_requestor' 
														                      AND spotlight_ballot.answer_id = spotlight_nominee.assignment_id";
																			  
														if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
									   						 exit();
									   					 }
			
									   					 if (mysqli_num_rows($result_user_choice) > 0) {
																										
															 while ($row = mysqli_fetch_assoc($result_user_choice)) {
						
						   										 // $assignment_user = htmlspecialchars(strip_tags($row['assignment_user']));
																 
																 $assignment_user = htmlspecialchars(strip_tags($row['assignment_user']));
																 $first_name = htmlspecialchars(strip_tags($row['first_name']));
																 $last_name = htmlspecialchars(strip_tags($row['last_name']));
																								
																 $data .='<small style="color:orange;"><i>Your choice was: '.$first_name.' '.$last_name.' - '.$assignment_user.'</i></small>';
																											
															 }
														 }
																							   
														 $data .='</div>
															 </div>
														 </div>
													 </div>';
																					
												 } else {
									
													 $data .='<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
						 										<div class="d-flex align-items-center text-break">
						 								   			<div class="flex-shrink-0">
						 								   				<!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
						 								   				<img src="img/spotlight_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
						 								  			</div>
						 								  		  	<div class="flex-grow-1 ms-3">
						 								   				<div class="fs-5">
						 								   					<div class="fw-bold mt-3">There is a tie between the following choices: 
																				<div class="btn-group btn-group-sm float-end" role="group" aria-label="Small button group">
																					<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
																						<i class="fa-solid fa-users"></i> '.$enrolled_users.'
																					</span>
																					<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
																						<i class="fa-solid fa-check-to-slot"></i> '.$ballot_votes.'
																					</span>';
																					
																					if ($enrolled_users != $ballot_votes) {
																					
																						$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Completed" disabled>
																									<i class="fa-solid fa-bars-progress"></i> '.$ballot_votes.'
																								 </span>';
																									 
																					} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																					
																						$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="spotlight Completed" disabled>
																									<i class="fa-solid fa-circle-check"></i> '.$ballot_votes.'
																								 </span>';
																									 
																					} else if ($ballot_votes == 0) {
																							
																						$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Started" disabled>
																									<i class="fa-solid fa-circle-info"></i> '.$ballot_votes.'
																								 </span>';
																					
																					} else {}
																					
																					$data .='</div>
																						</div>
																					<p>';
																							
																					$num_winners = count($winning_items);
																					$counter = 1;
																										
																					foreach ($winning_items as $item) {
																				
																						if ($counter == $num_winners) {
																							$data .='<small class=""><i>'.$item.'</i></small> ';
																						} else {
																							$data .='<small class=""><i>'.$item.'</i></small>, ';
																						}
																				
																						$counter++;
																					}
																			
																					$data .='</p>';
																						   
																					$query_user_choice = "SELECT spotlight_ballot.*, spotlight_nominee.*, users.first_name, users.last_name
																					                      FROM spotlight_ballot 
																					                      JOIN spotlight_nominee ON spotlight_ballot.question_id = spotlight_nominee.question_id 
																					                      JOIN users ON spotlight_nominee.assignment_user = users.user
																					                      WHERE spotlight_ballot.question_id = '$inquiry_id' 
																					                      AND spotlight_ballot.ballot_user = '$report_requestor' 
																					                      AND spotlight_ballot.answer_id = spotlight_nominee.assignment_id";

																					if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
																					    exit();
																					}

																					if (mysqli_num_rows($result_user_choice) > 0) {
																					    while ($row = mysqli_fetch_assoc($result_user_choice)) {
																					        $assignment_user = htmlspecialchars(strip_tags($row['assignment_user']));
																					        $first_name = htmlspecialchars(strip_tags($row['first_name']));
																					        $last_name = htmlspecialchars(strip_tags($row['last_name']));
        
																					        $data .='<small style="color:orange;"><i>Your choice was: '.$first_name.' '.$last_name.' - '.$assignment_user.'</i></small>';
																					    }
																					}
																							   
														 $data .='</div>
						 									 </div>
						 								 </div>
													 </div>';
												 }
												
											 } 
		
											 $data .='</div> <!-- End accordion body -->
							 					 </div> <!-- End accordionspotlightReports -->
											 </div> <!-- End accordion-item -->';
			
										 } // End while
									 } // End if
								
									 $data .='</div> <!-- End accordion Flush -->';

									 $data .='<div class="h-100 p-3 bg-white border rounded-3 shadow-sm mb-3 w-50">
										 		<a href="#" class="btn btn-secondary w-100" id="" onclick="cancelspotlightReportDetails();">
													<i class="fas fa-undo-alt"></i> Close spotlight Results
												</a>
												
														
											 </div> <!-- End h-100 mb-3 -->
										</div> <!-- End spotlight_results_tab -->
								   </div> <!-- End tab-content --> ';

	}
	
echo $data;
	
?>
 
<script>
$(document).ready(function() {
    $("select#answer").change(function() { 
        var selectedValue = $(this).val(); // Get the selected value from the select element
        
        $.post("ajax/spotlight/read_spotlight_users_details.php", {
            selected_values: selectedValue // Pass the selected value as the parameter 'selected_values'
        }, function(data, status) {
            // Check if the request was successful
            if (status === "success") {
                // Parse the JSON response
                var response = JSON.parse(data);
                
                // Check if data was found
                if (response.hasOwnProperty('status') && response.status === "Data not found!") {
                    // Handle the case when data was not found
                    console.log("Error: Data not found!");
                } else {
                    // Access the individual fields in the response object and update your HTML elements accordingly
                    // Example: Update HTML elements with response data
 					// display profile pic
 					var profilePic = response.profile_pic;
														
 					if (!(profilePic == null)){
						
 						$("#contact_card_image").attr("src", profilePic);
 					} else {
 						$("#contact_card_image").attr("src", "img/avatar.png");
 					} 
														
 					// display name
 					var display_name = response.display_name; 
														
 					if (!(display_name == null)){
 						$("#display_my_name").html(display_name);
 					} else {
 						$("#display_my_name").html("<span class='dark-gray fw-bold fs-3'>Nominee</span>");
 					}
														
 					// display title
 					var display_title = response.display_title; 
														
 					if (!(display_title == null)){
 						$("#display_my_title").html(display_title);
 					} else {
 						$("#display_my_title").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 					}
														
 					// display agency
 					var display_agency = response.display_agency; 
														
 					if (!(display_agency == null)){
 						$("#display_my_agency").html(display_agency);
 					} else {
 						$("#display_my_agency").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 					}
														
 					// user location
 					var user_location = response.user_location; 
														
 					if (!(user_location == null)){
 						$("#display_my_location").html(user_location);
 					} else {
 						$("#display_my_location").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 					}
														
 					// display pronouns
 					var pronouns = response.pronouns;
									                   
 					if (!(pronouns == null)){
 						$("#pronouns").html(pronouns);
 					} else {
 						$("#pronouns").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 					}
						
                }
            } else {
                // Handle the case when the request was not successful
                console.log("Error: Request failed");
            }
        });
    });
});
</script>

 <script>
 $(document).ready(function(){
											
 	$('#submit-nomination').prop("disabled", true);  
											
 	$('#select-nominee').change(function() {
												
 		if ( $('#select-nominee').val() == "") {
 			$('#submit-nomination').prop("disabled", true);  
 		} else {
											    	
 			$('#submit-nomination').prop("disabled", false);
 		}
 	});
											
 	setTimeout(function() {
 	}, 2000);
		
 });
 </script>
 
 <script>
 	const buttons = document.querySelectorAll('.ripple');

 	buttons.forEach(button => {
 		button.addEventListener('click', function (e) {
 			const rect = button.getBoundingClientRect();
 			const x = e.clientX - rect.left;
 	    	const y = e.clientY - rect.top;

 	 	   	const circle = document.createElement('span');
 	    	circle.classList.add('circle');
 	 	   	circle.style.top = y + 'px';
 	    	circle.style.left = x + 'px';

 	   	 	this.appendChild(circle);

 	     	setTimeout(() => circle.remove(), 500);
 		});
 	});
 </script>
	
 <script>
 $(document).ready(function(){

 	$(".reset-nominee-form").click(function(){
 		$('#submit-vote').prop("disabled", ! $(this).is(':checked'));
 		$("#contact_card_image").attr("src", "img/avatar.png");
 		$("#display_my_name").html("<span class='dark-gray fw-bold fs-3'>Nominee</span>");
 		$("#display_my_agency").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 		$("#display_my_title").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 		$("#display_my_location").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 		$("#pronouns").html("<i class='fa-solid fa-circle-question text-concrete'></i>");
 	});

 });
 </script>
<script>
$(document).ready(function() {
	
	$("#answer").change(function() {
	    if ($(this).val() !== '') {
	        $("#submit-vote").prop("disabled", false);
	    } else {
	        $("#submit-vote").prop("disabled", true);
	    }
	});
	
	$("#submit-vote").click(function() {
		// disable button
		$(this).prop("disabled", true);
		// add spinner to button
		$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submit');
	});

});
</script>

<script>
$(document).ready(function(){
	
	$("#show-spotlight-results").click(function(){
		$("#spotlight_response_tab").fadeOut("fast", function(){
			$("#spotlight_response_tab").removeClass("active show");
			$("#spotlight_results_tab").fadeIn("fast", function(){
				$("#spotlight_results_tab").addClass("active show");
			});
		});
	});
	
	$("#hide-spotlight-results-tab").click(function(){
		$("#spotlight_results_tab").fadeOut("fast", function(){
			$("#spotlight_results_tab").removeClass("active show");
			$("#spotlight_response_tab").fadeIn("fast", function(){
				$("#spotlight_response_tab").addClass("active show");
			});
		});
	});
	
});
</script>

<script>
	
	function closespotlightReportDetails() {
	
		$("#spotlight_results_tab").fadeOut("fast", function(){
			$("#spotlight_results_tab").removeClass("active show");
			$("#spotlight_response_tab").fadeIn("fast", function(){
				$("#spotlight_response_tab").addClass("active show");
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