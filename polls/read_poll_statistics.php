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

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
	$data ='';
	$inquiry_id = $_POST['inquiry_id'];
	$query = "SELECT * FROM poll_inquiry WHERE inquiry_id = ? ORDER BY inquiry_display_order ASC";
	$stmt = mysqli_prepare($dbc, $query);

	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    	mysqli_stmt_execute($stmt);
    	$result = mysqli_stmt_get_result($stmt);
    	confirmQuery($result);
    	while ($row = mysqli_fetch_array($result)) {
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
			$query_poll_answers = "SELECT * FROM poll_response WHERE question_id = ?";
			$stmt_poll_answers = mysqli_prepare($dbc, $query_poll_answers);
			if ($stmt_poll_answers) {
				mysqli_stmt_bind_param($stmt_poll_answers, 'i', $inquiry_id);
    			mysqli_stmt_execute($stmt_poll_answers);
    			$poll_answers_results = mysqli_stmt_get_result($stmt_poll_answers);
    			$poll_answers_count = mysqli_num_rows($poll_answers_results);
    			mysqli_stmt_close($stmt_poll_answers);
			}
						
			$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
			$stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count);
			
			if ($stmt_enrollment_count) {
				mysqli_stmt_bind_param($stmt_enrollment_count, 'i', $inquiry_id);
    			mysqli_stmt_execute($stmt_enrollment_count);
    			$enrollment_results = mysqli_stmt_get_result($stmt_enrollment_count);
    			$enrolled_users = mysqli_num_rows($enrollment_results);
    			mysqli_stmt_close($stmt_enrollment_count);
			}
				
			$query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";
			$stmt_ballot_count = mysqli_prepare($dbc, $query_ballot_count);

				if ($stmt_ballot_count) {
				 	mysqli_stmt_bind_param($stmt_ballot_count, 'i', $inquiry_id);
    				mysqli_stmt_execute($stmt_ballot_count);
    				$ballot_results = mysqli_stmt_get_result($stmt_ballot_count);
    				$ballot_votes = mysqli_num_rows($ballot_results);
    				mysqli_stmt_close($stmt_ballot_count);
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
			
				$data .='<div class="row">
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
									<div class="d-flex text-body-secondary">';
							 
									if($inquiry_status == "Active"){
					
										$data .='<span class="btn btn-light btn-sm bg-mint me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" disabled>
													<i class="fa-solid fa-circle-check"></i>
												 </span>
												 <p class="mb-0 small lh-sm">
												 	<strong class="d-block text-gray-dark">Status - Active</strong>
													This poll status is active
												 </p>';
					
									} else if($inquiry_status == "Paused"){
					
										$data .='<span class="btn btn-light btn-sm bg-hot me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" disabled>
													<i class="fa-solid fa-circle-pause"></i>
												 </span>
												 <p class="pb-3 mb-0 small lh-sm border-bottom">
												 	<strong class="d-block text-gray-dark">Status - Paused</strong>
													This poll status is paused
												 </p>';
							
									} else if($inquiry_status == "Closed"){
					
										$data .='<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" disabled>
													<i class="fa-solid fa-folder-closed"></i>
												 </span>
												 <p class="mb-0 small lh-sm">
												 	<strong class="d-block text-gray-dark">Status - Closed</strong>
													This poll status is closed
												 </p>';
							
									} else {}
							
										$data .='</div> 
											</div>
										</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
							
									<div class="d-flex text-body-secondary">
										<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Author" disabled>
											<i class="fa-solid fa-circle-user"></i>
										</span>
										<p class="mb-0 small lh-sm">
											<strong class="d-block text-gray-dark">Author - '.$inquiry_author.'</strong>
											Created by '.$inquiry_author.'
							    		</p>
									</div>
									
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
							
									<div class="d-flex text-body-secondary">
										<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Creation Date" disabled>
											<i class="fa-solid fa-clock"></i>
										</span>
										<p class="mb-0 small lh-sm">
						       		 		<strong class="d-block text-gray-dark">Creation Date </strong>
						        			'.$inquiry_creation_date.'
							    		</p>
									</div>
								</div>
							</div>
						</div>
					
						<div class="row">
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
							
									<div class="d-flex text-body-secondary">
  		  				  		  		<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrollments" disabled>
  		  				  					<i class="fa-solid fa-users"></i>
  		  				 				</span>
					 					<p class="mb-0 small lh-sm">
  		  				       				<strong class="d-block text-gray-dark">Enrollments - '.$enrolled_users.'</strong>
  		  				        			Assigned users
  		  				      		  	</p>
  		  				    		</div>
									
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
									<div class="d-flex text-body-secondary">
										<a class="" data-bs-toggle="collapse" href="#collapseVotes" role="button" aria-expanded="false" aria-controls="collapseVotes">
											<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
						  						<i class="fa-solid fa-check-to-slot"></i>
						 					</span>
										</a>
					 					<p class="mb-0 small lh-sm">
											<strong class="d-block text-gray-dark">Ballot Votes - '.$ballot_votes.'</strong>
											Poll ballot votes			      
										</p>
						    		</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-3">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-light btn-sm bg-concrete me-2" style="height:32px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Progress" disabled="">
											<i class="fa-solid fa-bars-progress"></i>
										</span>
										<span class="flex-grow-1"><strong class="mb-0 small lh-sm">Completion - '.$percentage_rate.'%</strong>
											<div class="progress lh-1" style="height: 11px" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
												<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
											</div>
										</span>
									</div>
								</div>
							</div>
						</div>';
				
						$data .='<div class="collapse" id="collapseVotes">
						  			<div class="p-3 bg-body rounded border shadow-sm mb-3">';
						    
						$query_user_choice = "
						    SELECT * 
						    FROM poll_ballot 
						    JOIN poll_response ON poll_ballot.question_id = poll_response.question_id 
						    JOIN users ON poll_ballot.ballot_user = users.user 
						    WHERE poll_ballot.question_id = ? 
						    AND answer_id = response_id 
						    ORDER BY response_answer ASC
						";

						$stmt_user_choice = mysqli_prepare($dbc, $query_user_choice);

						if ($stmt_user_choice) {
						  	mysqli_stmt_bind_param($stmt_user_choice, 'i', $inquiry_id);
    						mysqli_stmt_execute($stmt_user_choice);
    						$result_user_choice = mysqli_stmt_get_result($stmt_user_choice);
    						if (mysqli_num_rows($result_user_choice) > 0) {
							
				  			$data .='<div class="table-responsive">
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
	
			  			} else {
							
				  			$data .='<div class="table-responsive">
				  					 	<table class="table mb-3" id="" width="100%">
				  						 	<thead class="table-light">
				  								<tr>
				  									<th colspan="4">No results</th>
				  								</tr>
				  							</thead>
				  							<tbody id="">';
											
											$data .='<tr>
			  											<td class="align-middle" colspan="4">No votes have been submitted</td>
			  										 </tr>';
			  			}
						
						mysqli_stmt_close($stmt_user_choice);
							
					}
			
						$data .='</tbody></table></div>';
							
						$data .='</div>
						</div>
						
						<div class="p-3 bg-body rounded border shadow mb-3">
							<div class="d-flex align-items-center text-break">
								<div class="flex-shrink-0">
									<img src="'.$inquiry_image.'" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:70px;" id="read_audit_profile_pic">
								</div>
								<div class="flex-grow-1 ms-3 mb-3">
									<div class="fs-5">
										<div class="dark-gray fw-bold mt-3">'.$inquiry_question.'</div>
											<p><small class="text-secondary"><i>Heart Poll</i></small></p>
											<input type="hidden" name="" id="" value="">
										</div>
									</div>
								</div>';
						
								$query_two = "SELECT * FROM poll_response WHERE question_id = ? ORDER BY response_display_order ASC";
								$stmt_two = mysqli_prepare($dbc, $query_two);

								if ($stmt_two) {
									mysqli_stmt_bind_param($stmt_two, 'i', $inquiry_id);
    								mysqli_stmt_execute($stmt_two);
    								$result_two = mysqli_stmt_get_result($stmt_two);
    
									if (mysqli_num_rows($result_two) > 0) {
										$highest_value = 0;
										$winning_items = array();
								
									while ($row = mysqli_fetch_assoc($result_two)) {
						
										$response_id = mysqli_real_escape_string($dbc, strip_tags($row['response_id']));
										$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
										$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
										$stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count);

										if ($stmt_enrollment_count) {
										    mysqli_stmt_bind_param($stmt_enrollment_count, 'i', $inquiry_id);
											mysqli_stmt_execute($stmt_enrollment_count);
											$enrollment_results = mysqli_stmt_get_result($stmt_enrollment_count);
											$enrolled_users = mysqli_num_rows($enrollment_results);
											mysqli_stmt_close($stmt_enrollment_count);
										}
										
										$query_ballot_answer_count = "SELECT * FROM poll_ballot WHERE answer_id = ?";
										$stmt_ballot_answer_count = mysqli_prepare($dbc, $query_ballot_answer_count);

										if ($stmt_ballot_answer_count) {
										  	mysqli_stmt_bind_param($stmt_ballot_answer_count, 'i', $response_id);
    										mysqli_stmt_execute($stmt_ballot_answer_count);
    										$ballot_answer_results = mysqli_stmt_get_result($stmt_ballot_answer_count);
    										$ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
    										mysqli_stmt_close($stmt_ballot_answer_count);
										}
										
										if ($ballot_answer_votes == 0) {
											$percentage_rate = 0;
										} else {
											$percentage_rate = ($ballot_answer_votes / $ballot_votes) * 100;
										}
												
										if ($ballot_answer_votes > $highest_value) {
											$highest_value = $ballot_answer_votes;
											$winning_items = array($response_answer);
										} else if ($ballot_answer_votes == $highest_value) {
											array_push($winning_items, $response_answer);
										}
												
										$percentage_rate_new = round($percentage_rate, 2);
									
										$data .='<span class="badge bg-light-gray mb-1 me-1">'.$ballot_answer_votes.'</span>
												 <span class="fw-bold text-secondary">'.$response_answer.'</span>
										         <span class="fw-bold text-secondary float-end">
												 	<small>'.$percentage_rate_new.'%</small>
												 </span>';
												
												 if ($percentage_rate >= 0 && $percentage_rate <= 25) {
													 	$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-danger" style="width: '.$percentage_rate.'%"></div>
																 </div>';
														} else if ($percentage_rate >= 26 && $percentage_rate <= 50) {
													
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-warning" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
														} else if ($percentage_rate >= 51 && $percentage_rate <= 75) {
													
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																	<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
																 </div>';
													
														} else if ($percentage_rate >= 76 && $percentage_rate <= 100) {
													
														$data .='<div class="progress mb-3" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
																		<div class="progress-bar progress-bar-striped bg-success" style="width: '.$percentage_rate.'%"></div>
																	 </div>';
														} else {}
												
															$data .='';
													}
								
													if (count($winning_items) == 1) {
								   				 	
														$data .='<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
									   								<div class="d-flex align-items-center text-break">
									   									<div class="flex-shrink-0">
									   										<!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
									   										<img src="'.$inquiry_image.'" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
									   									</div>
									   									<div class="flex-grow-1 ms-3">
									   										<div class="fs-5">
									   											<div class="fw-bold mt-3">';
																			
																				if ($enrolled_users != $ballot_votes) { 
																			
																					$data .='<span class="">The leading choice is...</span>';
																							 
																				} else if ($enrolled_users == $ballot_votes && $ballot_votes != 0) {
																			
																					$data .='<span class="">And the Winner is!</span>';
																							 
																				} else if ($ballot_votes == 0) {
																					
																					$data .='<span class="">This poll has no votes.</span>';
																				
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
																					
																							$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completed" disabled>
																										<i class="fa-solid fa-circle-check"></i> '.$ballot_votes.'
																									 </span>';
																									 
																						} else if ($ballot_votes == 0) {
																							
																							$data .='<span type="" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Not Started" disabled>
																										<i class="fa-solid fa-circle-info"></i> '.$ballot_votes.'
																									 </span>';
																						} else {}
																					
																		  $data .='</div>
																				</div>
									   									  		<p><small class=""><i>'.$winning_items[0].'</i></small></p>
									   										</div>
									   									</div>
									   								</div>
																</div>
															</div>';
															
													} else {
									
														$data .='<div class="h-100 p-3 text-bg-dark rounded-3">
	 								   								<div class="d-flex align-items-center text-break">
	 								   									<div class="flex-grow-1 ms-3">
	 								   										<div class="fs-4">
	 								   											<div class="fw-bold mt-3">There is a tie between the following choices...</div>
																		    </div>';
																					
																			$data .='<p>';
																							
																				$num_winners = count($winning_items);
																				$counter = 1;
																										
																				foreach ($winning_items as $item) {
																				
																					if ($counter == $num_winners) {
																						$data .='<small class="fs-5 text-info"><i>'.$item.' </i></small> ';
																					} else {
																						$data .='<small class="fs-5 text-info"><i>'.$item.'</i> <span class="text-white">&#8226;</span></small> ';
																					}
																				
																					$counter++;
																				}
																			
																				$data .='';
																			
																	   $data .='</p>
																	   
																		  </div>
	 								   								   </div>
	 								   								</div>
																</div>';
													}
												
												} else {
        
													$data .='<div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
																<div class="position-relative">
																	<h6 class="bg-hot shadow-sm rounded border p-3 mb-0"> 
																		<span class="" style="line-height: 30px;"> 
																			<i class="fa-solid fa-circle-question"></i> This poll does not contain any answers.
																		</span>
																	</h6>
																</div>
															 </div>';
												}
		
												$data .='</div> 
		 		    								 </div>
						 					    </div>';
			              }
						  
						 mysqli_stmt_close($stmt);
						  
				 		}
					}
								
					$data .='</div> <!-- End accordion Flush -->';

				}
	
	echo $data;

mysqli_close($dbc);
			
?>	

<script>
$(document).ready(function(){      
	var postURL = "ajax/polls/addmore.php";
	var i = 1;  

	$('#add-poll-answer').click(function(){  
		$("#sortable_answer_row").sortable({
			axis: "y",
			helper: function(e, tr)
			  {
			    var $originals = tr.children();
			    var $helper = tr.clone();
			    
				$helper.children().each(function(index){
					$(this).width($originals.eq(index).width());
				  	$(this).css("background-color", "white");
			    });
				
			    return $helper;
			  },
			
			placeholder: "ui-state-highlights",
			update: function(event, ui) {
 				updateDocAnswerDisplayOrder();
 			}
 		});
						
		i++; 
						
		$('#add_new_answer_row').append('<tr id="row'+i+'" class="dynamic-added"><td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td><td><input type="text" name="response_answer[]" placeholder="" class="form-control" required></td><td class="align-middle text-center">0</td><td class="" style="width:150px;"><input type="number" class="form-control" value="0" min="0" max="" step="1"></td><td class="align-middle text-center" style="width:5%"><button type="button" name="remove" id="'+i+'" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button></td></tr></tr>');

});

$(document).on('click', '.btn_remove', function(){  
	var button_id = $(this).attr("id");
	$('#row'+button_id+'').addClass('bg-hot-o');
	$('#row'+button_id+'').fadeOut(1000);
});  

$('#submit').click(function(){            
	$.ajax({  
		url:postURL,  
		method:"POST",  
		data:$('#add_name').serialize(),
			type:'json',
			success:function(data){
				i = 1;
				$('.dynamic-added').remove();
				$('#add_name')[0].reset();
			}  
		});  
	});		
});
</script>

<script>
$(document).ready(function() {
	$("#update-poll-answers").click(function() {
		$("#update-poll-answers").addClass('bg-mint');
	 	$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
	});
});
</script>
	
<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>