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

<style>
.ui-state-highlights {
	background: #f8f9fa !important;
	height: 50px;	
}
</style>

<script>
$(document).ready(function(){
	function countPollRecords(){
		var count = $('.count-poll-item').length;
	    $('.count-poll-records').html(count);
	} countPollRecords();
});
</script>

<script>
$(document).ready(function(){
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_poll_count").html(count);
	} else {
		$(".my_poll_count").html(count_zero);
	}					
});	
</script>

<script>
$(document).ready(function(){
	$("#search_completed_polls").on("keyup", function() {
		if ($("#search_completed_polls").val() == ''){
			$(".input-group-text.completed-poll-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
		} else {
			$(".input-group-text.completed-poll-search").find('.fa').removeClass('fa-search').addClass("fa-times-circle");
			$('.fa-times-circle').click(function() {
				var value = $(this).val().toLowerCase();
				$(".accordion .accordion-search").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});
				$('input[type="text"]').val('').trigger('propertychange').focus();
				$(".input-group-text.completed-poll-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
			});
		}
		var value = $(this).val().toLowerCase();
		$(".accordion .accordion-search").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
	});
});
</script>

<?php
if (isset($_SESSION['id'])) {

$data ='<div class="accordion accordion-flush" id="accordionPollReports">';
				
$query = "SELECT * FROM poll_inquiry WHERE inquiry_status = 'Closed' ORDER BY inquiry_display_order ASC";

if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
            $inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author']));
            $inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date']));
            $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
            $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
            $inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
            $inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info']));
            $inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status']));

            if ($inquiry_image == '') {
                $inquiry_image = 'media/links/default_poll_image.png';
            } else {
                $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
            }

            $query_poll_answers = "SELECT COUNT(*) FROM poll_response WHERE question_id = ?";

            if ($stmt_poll = mysqli_prepare($dbc, $query_poll_answers)) {
                mysqli_stmt_bind_param($stmt_poll, "s", $inquiry_id);
                mysqli_stmt_execute($stmt_poll);
                mysqli_stmt_bind_result($stmt_poll, $poll_answers_count);
                mysqli_stmt_fetch($stmt_poll);
                mysqli_stmt_close($stmt_poll);
            } else {
                exit();
            }

            $query_enrollment_count = "SELECT COUNT(*) FROM poll_assignment WHERE poll_id = ?";

            if ($stmt_enrollment = mysqli_prepare($dbc, $query_enrollment_count)) {
                mysqli_stmt_bind_param($stmt_enrollment, "s", $inquiry_id);
                mysqli_stmt_execute($stmt_enrollment);
                mysqli_stmt_bind_result($stmt_enrollment, $enrolled_users);
                mysqli_stmt_fetch($stmt_enrollment);
                mysqli_stmt_close($stmt_enrollment);
            } else {
                exit();
            }

            $query_ballot_count = "SELECT COUNT(*) FROM poll_ballot WHERE question_id = ?";

            if ($stmt_ballot = mysqli_prepare($dbc, $query_ballot_count)) {
                mysqli_stmt_bind_param($stmt_ballot, "s", $inquiry_id);
                mysqli_stmt_execute($stmt_ballot);
                mysqli_stmt_bind_result($stmt_ballot, $ballot_votes);
                mysqli_stmt_fetch($stmt_ballot);
                mysqli_stmt_close($stmt_ballot);
            } else {
                exit();
            }

            if ($enrolled_users == 0) {
                $percentage_rate = '0.00';
            } else {
                $participation_rate = $ballot_votes / $enrolled_users;
                $percentage_rate = number_format($participation_rate * 100, 2, '.', '');
            }

            $data .= '<div class="accordion-item accordion-search border mb-2">
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
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Answers" disabled>
								<i class="fa-solid fa-clipboard-question text-secondary"></i> '.$poll_answers_count.'
							</span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Enrolled Users" disabled>
								<i class="fa-solid fa-users text-secondary"></i> '.$enrolled_users.'
							</span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Ballot Votes" disabled>
								<i class="fa-solid fa-check-to-slot text-secondary"></i> '.$ballot_votes.'
							</span>
							<span class="flex-grow-1 ms-2" style="width:125px;">
								<div class="progress" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
							  		<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
								</div>
							</span>';
								
							if($inquiry_status == "Active"){
							
	                    		$data .='<span class="btn btn-light btn-sm bg-mint ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" style="width:50px;" disabled><i class="fa-solid fa-circle-check"></i></span>';
					
	                     	} else if($inquiry_status == "Paused"){
							
	                    		$data .='<span class="btn btn-light btn-sm bg-hot ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" style="width:50px;" disabled><i class="fa-solid fa-circle-pause"></i></span>';
							
							} else if($inquiry_status == "Closed"){
							
	                    		$data .='<span class="btn btn-light btn-sm bg-concrete ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" style="width:50px;" disabled><i class="fa-solid fa-check-to-slot"></i></span>';
							
							} else {}
								
	 						$data .='</button>';
									
	 		    			$data .='</h2>
	 		    					 <div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionPollReports">
	 		      						<div class="accordion-body">';
							
										$data .='<div class="fw-bold fs-5 dark-gray mt-2">'.$inquiry_question.'</div>';
						
										$data .='<hr>';
							
										$query_two = "SELECT * FROM poll_response WHERE question_id = ? ORDER BY response_display_order ASC";

										if ($stmt_two = mysqli_prepare($dbc, $query_two)) {
										    mysqli_stmt_bind_param($stmt_two, "s", $inquiry_id);
										    mysqli_stmt_execute($stmt_two);
										    $result_two = mysqli_stmt_get_result($stmt_two);

										    if ($result_two && mysqli_num_rows($result_two) > 0) {
								
											$highest_value = 0;
											$winning_items = array();
								
											while ($row = mysqli_fetch_assoc($result_two)) {
												$response_id = mysqli_real_escape_string($dbc, strip_tags($row['response_id']));
												$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
												$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";

												if ($stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count)) {
												 	mysqli_stmt_bind_param($stmt_enrollment_count, "s", $inquiry_id);
    												mysqli_stmt_execute($stmt_enrollment_count);
    												$enrollment_results = mysqli_stmt_get_result($stmt_enrollment_count);
    												if ($enrollment_results) {
												        $enrolled_users = mysqli_num_rows($enrollment_results);
												    } else {
												        $enrolled_users = 0;
												    }
    												mysqli_stmt_close($stmt_enrollment_count);
												}
					
												$query_ballot_answer_count = "SELECT * FROM poll_ballot WHERE answer_id = ?";

												if ($stmt_ballot_answer_count = mysqli_prepare($dbc, $query_ballot_answer_count)) {
												    mysqli_stmt_bind_param($stmt_ballot_answer_count, "s", $response_id);
    												mysqli_stmt_execute($stmt_ballot_answer_count);
    												$ballot_answer_results = mysqli_stmt_get_result($stmt_ballot_answer_count);
    												if ($ballot_answer_results) {
												        $ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
												    } else {
												        $ballot_answer_votes = 0;
												    }
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
												
												}
								
												if (count($winning_items) == 1) {
								   				 	
													$data .='<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
								   								<div class="d-flex align-items-center text-break">
								   									<div class="flex-shrink-0">
								   										<!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
								   										<img src="img/poll_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
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
															</div>';
												} else {
									
													$data .='<div class="h-100 p-4 text-bg-dark rounded-3 mb-3">
 								   								<div class="d-flex align-items-center text-break">
 								   									<div class="flex-shrink-0">
 								   										<!-- https://img.freepik.com/premium-vector/ice-cream-icon-vector-illustration_430232-296.jpg?w=2000 -->
 								   										<img src="img/poll_images/crown.png" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="">
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
				}
				
				else {
				        $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 60px auto 0 !important;">
				  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
				  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				</svg>
				<p class="one success">Empty!</p>
				<p class="complete" style="margin-bottom:55px !important;">No Poll reports at this time.</p>';
				    }
								
				$data .='</div> <!-- End accordion Flush -->';
				
				mysqli_stmt_close($stmt);
				}

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