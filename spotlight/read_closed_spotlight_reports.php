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

if (isset($_SESSION['id'])) {

	$data ='<div class="accordion accordion-flush" id="accordionspotlightReports">';
	
	$report_requestor = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
	
	$query = "SELECT DISTINCT si.* FROM spotlight_inquiry si 
	          JOIN spotlight_ballot sb ON si.inquiry_id = sb.question_id 
	          WHERE sb.ballot_user = '$report_requestor' 
	          AND si.inquiry_status = 'Closed' 
	          ORDER BY si.inquiry_creation_date DESC";	
			
	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
	
 	if (mysqli_num_rows($result) > 0) {
		
        while ($row = mysqli_fetch_array($result)) {
						
			$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
			$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? ''));
			$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info'] ?? ''));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
			
			if ($inquiry_image == ''){
				$inquiry_image = 'media/links/default_spotlight_image.png';
			}
			
			// FIXED: Check both spotlight_response AND spotlight_nominee tables
			$query_spotlight_answers = "SELECT * FROM spotlight_response WHERE question_id = '$inquiry_id'";
			$query_spotlight_nominees = "SELECT * FROM spotlight_nominee WHERE question_id = '$inquiry_id'";

			$spotlight_answers_count = 0;
			$spotlight_nominees_count = 0;
			$spotlight_type = 'unknown';
			
			if ($spotlight_answers_results = mysqli_query($dbc, $query_spotlight_answers)){
		   	 	$spotlight_answers_count = mysqli_num_rows($spotlight_answers_results);
	 	   	}
	 	   	
	 	   	if ($spotlight_nominees_results = mysqli_query($dbc, $query_spotlight_nominees)){
		   	 	$spotlight_nominees_count = mysqli_num_rows($spotlight_nominees_results);
	 	   	}
	 	   	
	 	   	// Determine spotlight type
	 	   	if ($spotlight_answers_count > 0) {
	 	   		$spotlight_type = 'answers';
	 	   	} elseif ($spotlight_nominees_count > 0) {
	 	   		$spotlight_type = 'nominees';
	 	   	}
						
			$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";
			if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
				$enrolled_users = mysqli_num_rows($enrollment_results);
	  	  	} 
						
			$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
			if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
		 	   $ballot_votes = mysqli_num_rows($ballot_results);
		   	} 
			
			if (empty($enrolled_users)) {
				$participation_rate = 0;
				$percentage_rate = number_format($participation_rate, 2, '.', '');
			} else {
				$participation_rate = $ballot_votes / $enrolled_users;
				$participation_rate = $participation_rate * 100;
				$percentage_rate = number_format($participation_rate, 2, '.', '');
			}
			
			$data .='<div class="accordion-item accordion-search border mb-2">
	 		   			<h2 class="accordion-header">
	 		      		<button type="button" class="accordion-button d-flex justify-content-between align-items-center collapsed" style="padding: 0.5rem;" data-bs-toggle="collapse" data-bs-target="#accord_'.$inquiry_id.'" aria-expanded="false" aria-controls="flush-collapseOne">
	 		        		<img src="'.$inquiry_image.'" class="profile-photo ms-2"> 
							<span class="w-25"><strong class="dark-gray ms-3">'.$inquiry_name.'</strong></span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_creation_date.'" disabled>
								<i class="fa-solid fa-clock text-secondary"></i> Date
							</span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_author.'" disabled>
								<i class="fa-solid fa-user text-secondary"></i> '.$inquiry_author.'
							</span>
							<span class="flex-grow-1 ms-2" style="width:125px;">
								<div class="progress" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Spotlight Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
							  		<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
								</div>
							</span>';
								
							if($inquiry_status == "Active"){
	                    		$data .='<span class="btn btn-light btn-sm bg-mint ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" style="width:50px;" disabled><i class="fa-solid fa-circle-check"></i></span>';
	                     	} else if($inquiry_status == "Paused"){
	                    		$data .='<span class="btn btn-light btn-sm bg-hot ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" style="width:50px;" disabled><i class="fa-solid fa-circle-pause"></i></span>';
							} else if($inquiry_status == "Closed"){
	                    		$data .='<span class="btn btn-light btn-sm bg-concrete ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" style="width:50px;" disabled><i class="fa-solid fa-check-to-slot"></i></span>';
							}
								
	 						$data .='</button>';
									
	 		    			$data .='</h2>
	 		    					 <div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionspotlightReports">
	 		      						<div class="accordion-body">';
							
										$data .='<div class="fw-bold fs-5 dark-gray mt-2">'.$inquiry_question.'</div>';
						
										
							
										// FIXED: Handle both answer-based and nominee-based spotlights
										if ($spotlight_type == 'answers') {
											// Original answer-based logic
											$query_two = "SELECT * FROM spotlight_response WHERE question_id = '$inquiry_id' ORDER BY response_display_order ASC";	
											
											if (!$result_two = mysqli_query($dbc, $query_two)) {
												exit();
											}
								
											if (mysqli_num_rows($result_two) > 0) {
									
												$highest_value = 0;
												$winning_items = array();
									
												while ($row = mysqli_fetch_assoc($result_two)) {
							
													$response_id = mysqli_real_escape_string($dbc, strip_tags($row['response_id'] ?? ''));
													$response_answer = htmlspecialchars(strip_tags($row['response_answer'] ?? ''));
										
													$query_ballot_answer_count = "SELECT * FROM spotlight_ballot WHERE answer_id = '$response_id'";

													if ($ballot_answer_results = mysqli_query($dbc, $query_ballot_answer_count)){
								           		 		$ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
							   						} 
													
													if ($ballot_answer_votes > $highest_value) {
														$highest_value = $ballot_answer_votes;
														$winning_items = array(); 
														array_push($winning_items, $response_answer);
													} elseif ($ballot_answer_votes == $highest_value && $highest_value > 0) {
														array_push($winning_items, $response_answer);
													}
												}
												
												// Display results for answer-based spotlight
												$data .='<div class="card border-0 bg-light mb-4">
															<div class="card-body p-4">
																<div class="row align-items-center">
																	<div class="col-md-10">
																		<div class="d-flex align-items-center">
																			<div class="flex-shrink-0 me-3">
																				<div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
																					<i class="fa-solid fa-trophy text-white fa-lg"></i>
																				</div>
																			</div>
																			<div class="flex-grow-1">
																				<h5 class="mb-1 text-success">
																					<strong>Top Choice</strong>
																				</h5>';
																			
																		$num_winners = count($winning_items);
																		$counter = 1;
																					
																		foreach ($winning_items as $item) {
																			if ($counter == $num_winners) {
																				$data .='<span class="text-dark">'.$item.'</span> ';
																			} else {
																				$data .='<span class="text-dark">'.$item.'</span>, ';
																			}
																			$counter++;
																		}
																		
																		$data .='<small class="text-muted ms-2">('.$highest_value.' votes)</small>
																			</div>
																		</div>
																	</div>
																	<div class="col-md-2 text-end">';
																
																if ($ballot_votes < $enrolled_users && $ballot_votes != 0) {
																	$data .='<span class="badge bg-warning">
																				<i class="fa-solid fa-hourglass"></i> '.$ballot_votes.' votes
																			 </span>';
																} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																	$data .='<span class="badge bg-success">
																				<i class="fa-solid fa-circle-check"></i> Complete
																			 </span>';
																} else if ($ballot_votes == 0) {
																	$data .='<span class="badge bg-secondary">
																				<i class="fa-solid fa-circle-info"></i> No votes
																			 </span>';
																}
																
																$data .='</div>
																	</div>
																</div>
															</div>';
												
											} else {
												$data .='<div class="alert alert-warning" role="alert">
															<div class="d-flex align-items-center">
																<i class="fa-solid fa-circle-question fa-2x me-3"></i>
																<div>
																	<h6 class="alert-heading mb-1">No Answers Available</h6>
																	<small class="mb-0">This spotlight does not contain any answers.</small>
																</div>
															</div>
														 </div>';
											}
											
										} elseif ($spotlight_type == 'nominees') {
											// NEW: Handle nominee-based spotlights
											$query_nominees = "SELECT sn.*, u.first_name, u.last_name, u.profile_pic 
											                   FROM spotlight_nominee sn 
											                   JOIN users u ON sn.assignment_user = u.user 
											                   WHERE sn.question_id = '$inquiry_id' 
											                   ORDER BY sn.assignment_id ASC";
											
											if (!$result_nominees = mysqli_query($dbc, $query_nominees)) {
												exit();
											}
								
											if (mysqli_num_rows($result_nominees) > 0) {
									
												$highest_value = 0;
												$winning_nominees = array();
												$all_nominees_data = array();
									
												// First pass: collect all nominee data and find highest votes
												while ($row = mysqli_fetch_assoc($result_nominees)) {
							
													$assignment_id = mysqli_real_escape_string($dbc, strip_tags($row['assignment_id'] ?? ''));
													$nominee_first_name = htmlspecialchars(strip_tags($row['first_name'] ?? ''));
													$nominee_last_name = htmlspecialchars(strip_tags($row['last_name'] ?? ''));
													$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;
													$nominee_profile_pic = htmlspecialchars(strip_tags($row['profile_pic'] ?? 'img/profile_pic/avatar.png'));
										
													$query_ballot_nominee_count = "SELECT * FROM spotlight_ballot WHERE answer_id = '$assignment_id' AND question_id = '$inquiry_id'";

													if ($ballot_nominee_results = mysqli_query($dbc, $query_ballot_nominee_count)){
								           		 		$ballot_nominee_votes = mysqli_num_rows($ballot_nominee_results);
							   						} else {
							   							$ballot_nominee_votes = 0;
							   						}
													
													// Store nominee data
													$all_nominees_data[] = array(
														'name' => $nominee_full_name,
														'profile_pic' => $nominee_profile_pic,
														'votes' => $ballot_nominee_votes
													);
													
													if ($ballot_nominee_votes > $highest_value) {
														$highest_value = $ballot_nominee_votes;
														$winning_nominees = array(); 
														$winning_nominees[] = array(
															'name' => $nominee_full_name,
															'profile_pic' => $nominee_profile_pic,
															'votes' => $ballot_nominee_votes
														);
													} elseif ($ballot_nominee_votes == $highest_value && $highest_value > 0) {
														$winning_nominees[] = array(
															'name' => $nominee_full_name,
															'profile_pic' => $nominee_profile_pic,
															'votes' => $ballot_nominee_votes
														);
													}
												}
												
												if ($highest_value > 0) {
													$data .='<div class="row mb-4">
																<div class="col-12">
																	<div class="card border-success bg-light">
																		<div class="card-body p-4">
																			<div class="d-flex align-items-center">
																				<div class="flex-shrink-0 me-3">
																					<div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
																						<i class="fa-solid fa-trophy text-white fa-lg"></i>
																					</div>
																				</div>
																				<div class="flex-grow-1">
																					<h5 class="mb-2 text-success">
																						<strong>'.($highest_value == 1 && count($winning_nominees) == 1 ? 'Winner' : 'Winners').'</strong>
																					</h5>
																					<div class="d-flex flex-wrap align-items-center gap-3">';
																				
																			foreach ($winning_nominees as $winner) {
																				$data .='<div class="d-flex align-items-center">
																							<img src="'.$winner['profile_pic'].'" class="rounded-circle me-2" 
																								 width="40" height="40" alt="Winner" 
																								 onerror="this.src=\'img/profile_pic/avatar.png\'">
																							<div>
																								<div class="fw-bold text-dark">'.$winner['name'].'</div>
																								<small class="text-muted">'.$winner['votes'].' votes</small>
																							</div>
																						</div>';
																			}
																			
																			$data .='</div>
																				</div>
																				<div class="flex-shrink-0">';
																			
																			if ($ballot_votes < $enrolled_users && $ballot_votes != 0) {
																				$data .='<span class="badge bg-warning fs-6 px-3 py-2">
																							<i class="fa-solid fa-hourglass me-1"></i>'.$ballot_votes.' of '.$enrolled_users.' voted
																						 </span>';
																			} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																				$data .='<span class="badge bg-success fs-6 px-3 py-2">
																							<i class="fa-solid fa-circle-check me-1"></i>Complete
																						 </span>';
																			} else if ($ballot_votes == 0) {
																				$data .='<span class="badge bg-secondary fs-6 px-3 py-2">
																							<i class="fa-solid fa-circle-info me-1"></i>No votes
																						 </span>';
																			}
																			
																			$data .='</div>
																			</div>
																		</div>
																	</div>
																</div>
															</div>';
															
													// Show all nominees breakdown
													$data .='<div class="row mb-3">
																<div class="col-12">
																	<h6 class="text-muted mb-3">
																		<i class="fa-solid fa-users me-2"></i>All Nominees
																	</h6>
																	<div class="row g-3">';
																	
																foreach ($all_nominees_data as $nominee) {
																	$is_winner = $nominee['votes'] == $highest_value && $highest_value > 0;
																	$card_class = $is_winner ? 'border-success bg-success bg-opacity-10' : 'border-light';
																	
																	$data .='<div class="col-md-6 col-lg-4">
																				<div class="card '.$card_class.' h-100">
																					<div class="card-body p-3">
																						<div class="d-flex align-items-center">
																							<img src="'.$nominee['profile_pic'].'" class="rounded-circle me-3" 
																								 width="45" height="45" alt="Nominee" 
																								 onerror="this.src=\'img/profile_pic/avatar.png\'">
																							<div class="flex-grow-1">
																								<div class="fw-bold '.($is_winner ? 'text-success' : '').'">'.$nominee['name'].'</div>
																								<small class="text-muted">'.$nominee['votes'].' votes</small>
																							</div>
																							'.($is_winner ? '<i class="fa-solid fa-crown text-warning"></i>' : '').'
																						</div>
																					</div>
																				</div>
																			</div>';
																}
																
																$data .='</div>
																</div>
															</div>';
															
												} else {
													$data .='<div class="alert alert-info" role="alert">
																<div class="d-flex align-items-center">
																	<i class="fa-solid fa-users fa-2x me-3"></i>
																	<div>
																		<h6 class="alert-heading mb-1">Nominees Available</h6>
																		<small class="mb-0">This spotlight has nominees but no votes have been cast yet.</small>
																	</div>
																</div>
															 </div>';
															 
													// Show nominees even without votes
													$data .='<div class="row mb-3">
																<div class="col-12">
																	<h6 class="text-muted mb-3">
																		<i class="fa-solid fa-users me-2"></i>Nominees
																	</h6>
																	<div class="row g-3">';
																	
																foreach ($all_nominees_data as $nominee) {
																	$data .='<div class="col-md-6 col-lg-4">
																				<div class="card border-light h-100">
																					<div class="card-body p-3">
																						<div class="d-flex align-items-center">
																							<img src="'.$nominee['profile_pic'].'" class="rounded-circle me-3" 
																								 width="45" height="45" alt="Nominee" 
																								 onerror="this.src=\'img/profile_pic/avatar.png\'">
																							<div class="flex-grow-1">
																								<div class="fw-bold">'.$nominee['name'].'</div>
																								<small class="text-muted">No votes yet</small>
																							</div>
																						</div>
																					</div>
																				</div>
																			</div>';
																}
																
																$data .='</div>
																</div>
															</div>';
												}
												
											} else {
												$data .='<div class="alert alert-warning" role="alert">
															<div class="d-flex align-items-center">
																<i class="fa-solid fa-circle-question fa-2x me-3"></i>
																<div>
																	<h6 class="alert-heading mb-1">No Nominees</h6>
																	<small class="mb-0">This spotlight does not contain any nominees.</small>
																</div>
															</div>
														 </div>';
											}
											
										} else {
											// Neither answers nor nominees found
											$data .='<div class="alert alert-danger" role="alert">
														<div class="d-flex align-items-center">
															<i class="fa-solid fa-circle-exclamation fa-2x me-3"></i>
															<div>
																<h6 class="alert-heading mb-1">No Content</h6>
																<small class="mb-0">This spotlight does not contain any answers or nominees.</small>
															</div>
														</div>
													 </div>';
										}
							
										$data .='</div> <!-- End accordion body -->
	 		    								 </div> <!-- End accordionspotlightReports -->
					 					    </div> <!-- End accordion-item -->';
			
			 		}
				}
				
				else {
				    $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 60px auto 0 !important;">
				  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
				  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				</svg>
				<p class="one success">Empty!</p>
				<p class="complete" style="margin-bottom:55px !important;">No Spotlight reports at this time.</p>';
				    }
								
				$data .='</div> <!-- End accordion Flush -->';

echo $data;

}

mysqli_close($dbc);

?>

<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>