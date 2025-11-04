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
	width:90px !important;
}
.table>:not(caption)>*>* {
	padding: 0rem !important;
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
	$("#search_my_assigned_polls").on("keyup", function() {
		if ($("#search_my_assigned_polls").val() == ''){
			$(".input-group-text.my-assigned-poll-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
		} else {
			$(".input-group-text.my-assigned-poll-search").find('.fa').removeClass('fa-search').addClass("fa-times-circle");
			$('.fa-times-circle').click(function() {
				var value = $(this).val().toLowerCase();
				$("tr").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
				$('input[type="text"]').val('').trigger('propertychange').focus();
				$(".input-group-text.my-assigned-poll-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
			});
		}
		var value = $(this).val().toLowerCase();
		$("tr").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
});
</script>

<?php
if (isset($_SESSION['id'])) {
	
	$data ='<script>
		$(document).ready(function(){
		 	$(".poll_drag_icon").mousedown(function(){
		 		$("#sortable_my_poll_row").sortable({
					axis: "y",
					helper: function(e, tr)
					  {
					    var $originals = tr.children();
					    var $helper = tr.clone();
					    $helper.children().each(function(index)
					    {
					      $(this).width($originals.eq(index).width());
						  $(this).css("background-color", "#f8f9fa");
					    });
					    return $helper;
					  },
					
					placeholder: "ui-state-highlights",
					update: function(event, ui) {
		 				updateMyDisplayRowOrder();
		 			}
		 		}); 
		 	});
		 });
		 </script>

		 <script>
		 function updateMyDisplayRowOrder() {
			var selectedItem = new Array();
			$("tbody#sortable_my_poll_row tr").each(function() {
				selectedItem.push($(this).data("id"));
			});
	
		 	var dataString = "sort_order="+selectedItem;
	
		 	$.ajax({
		  	  	type: "GET",
		   	 	url: "ajax/polls/update_assignment_display_order.php",
		   	  	data: dataString,
		  	   	cache: false,
		   	  	success: function(data){
		     		readPollReports();
					var toastTrigger = document.getElementById("sortable_my_poll_row")
					var toastLiveExample = document.getElementById("toast-poll-manager-order")
					if (toastTrigger) {
						var toast = new bootstrap.Toast(toastLiveExample);
						toast.show()
				   	} 
				}
			});
		}
		</script>';

$data .='<table class="table table-borderless" style="padding:0px;">
	 		<tbody id="sortable_my_poll_row">';

	$poll_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
				
	$query = "SELECT * FROM poll_inquiry 
	          JOIN poll_assignment ON poll_inquiry.inquiry_id = poll_assignment.poll_id 
	          WHERE poll_assignment.assignment_user = ? 
	          ORDER BY assignment_display_order ASC";

	if ($stmt = mysqli_prepare($dbc, $query)) {
	 	mysqli_stmt_bind_param($stmt, "s", $poll_user);
    	mysqli_stmt_execute($stmt);
    	$result = mysqli_stmt_get_result($stmt);
    
		if ($result && mysqli_num_rows($result) > 0) {
	        while ($row = mysqli_fetch_array($result)) {
						
			$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author']));
			$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date']));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
			$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info']));
			$assignment_read = htmlspecialchars(strip_tags($row['assignment_read']));
			$inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview']));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status']));
			
			if ($inquiry_image == ''){
				$inquiry_image = 'media/links/default_poll_image.png';
			} else {
				$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
			}
						
			$query_poll_answers = "SELECT * FROM poll_response WHERE question_id = ?";
			if ($stmt_poll_answers = mysqli_prepare($dbc, $query_poll_answers)) {
			    mysqli_stmt_bind_param($stmt_poll_answers, "i", $inquiry_id);
			    mysqli_stmt_execute($stmt_poll_answers);
			    $poll_answers_results = mysqli_stmt_get_result($stmt_poll_answers);
			    $poll_answers_count = mysqli_num_rows($poll_answers_results);
			    mysqli_stmt_close($stmt_poll_answers);
			}

			$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
			if ($stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count)) {
			    mysqli_stmt_bind_param($stmt_enrollment_count, "i", $inquiry_id);
			    mysqli_stmt_execute($stmt_enrollment_count);
			    $enrollment_results = mysqli_stmt_get_result($stmt_enrollment_count);
			    $enrolled_users = mysqli_num_rows($enrollment_results);
			    mysqli_stmt_close($stmt_enrollment_count);
			}

			$query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";
			if ($stmt_ballot_count = mysqli_prepare($dbc, $query_ballot_count)) {
			    mysqli_stmt_bind_param($stmt_ballot_count, "i", $inquiry_id);
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
			
			$data .='<tr class="mb-0" data-id="'.$row['inquiry_id'].'">
			      		<td class="align-middle poll_drag_icon text-secondary bg-light" style="cursor:move; width:3%;"><i class="fa-solid fa-grip-vertical"></i></td>
				  		<td class="bg-light">
							<div class="accordion accordion-flush mb-2" id="accordionPollReports">
								<div class="accordion-item border">
	 		   						<h2 class="accordion-header">
										<button type="button" class="';
						
						if ($assignment_read == 1) {
                  		  	$data .='bg-dark-ice text-white accordion-button d-flex justify-content-between align-items-center collapsed';
                   	 	} else { 
						
						$data .='text-white accordion-button d-flex justify-content-between align-items-center collapsed'; 
						
						}
						
						$data .='" style="padding: 0.5rem;" data-bs-toggle="collapse" data-bs-target="#accord_'.$inquiry_id.'" aria-expanded="false" aria-controls="flush-collapseOne">
	 		        		
						    <img src="'.$inquiry_image.'" class="profile-photo ms-2"> 
							<span class="w-25"><strong class=" ';
							
							if ($assignment_read == 1) {
	                  		  	
								$data .='text-white';
								
	                   	 	} else { 
								
								$data .='dark-gray'; 
							}
							
							$data .=' ms-3">'.$inquiry_name.'</strong></span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_creation_date.'" disabled>
								<i class="fa-solid fa-clock text-secondary"></i>
							</span>
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_author.'" disabled>
								<i class="fa-solid fa-circle-user text-secondary"></i>
							</span>
							
							<span type="" class="btn btn-light btn-sm text-secondary ms-2 flex-grow-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'.$inquiry_author.'" disabled>
								<i class="fa-solid fa-magnifying-glass-chart text-secondary"></i>
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
							
	                    		$data .='<span class="btn btn-light btn-sm bg-mint ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active" style="width:40px;" disabled><i class="fa-solid fa-circle-check"></i></span>';
					
	                     	} else if($inquiry_status == "Paused"){
							
	                    		$data .='<span class="btn btn-light btn-sm bg-hot ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Paused" style="width:50px;" disabled><i class="fa-solid fa-circle-pause"></i></span>';
							
							} else if($inquiry_status == "Closed"){
							
	                    		$data .='<span class="btn btn-light btn-sm bg-concrete ms-2 me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Closed" style="width:50px;" disabled><i class="fa-solid fa-check-to-slot"></i></span>';
							
							} else {}
								
								
							if ($assignment_read == 1) {
								
		                 	   $data .='<span class="position-absolute top-0 start-0 translate-middle p-1 bg-warning border border-light rounded-circle">
								    		<span class="visually-hidden">New alerts</span>
								 	 	</span>';
		             	  	
							} else { }	
								
							$data .='</button>';
									
	 		    			$data .='</h2>
	 		    					 <div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionPollReports">
	 		      						<div class="accordion-body">';
										
										if($inquiry_status == "Active"){
											
											$query_ballot_sub = "SELECT * FROM poll_ballot WHERE ballot_user = ? AND question_id = ?";
											if ($stmt_ballot_sub = mysqli_prepare($dbc, $query_ballot_sub)) {
											    mysqli_stmt_bind_param($stmt_ballot_sub, "si", $poll_user, $inquiry_id);
											    mysqli_stmt_execute($stmt_ballot_sub);
											    $ballot_sub_results = mysqli_stmt_get_result($stmt_ballot_sub);
											    $ballot_sub_votes = mysqli_num_rows($ballot_sub_results);
											    mysqli_stmt_close($stmt_ballot_sub);
											}
											
											if ($ballot_sub_votes > 0) {
												
												$data .='<div class="p-4 bg-body-tertiary border rounded-3">
															<h5 class="dark-gray">
																<span class="badge bg-cool-ice" style="font-size:16px; line-height:16px; width:140px;">
																	<i class="far fa-check-circle"></i> Completed 
																</span>
															</h5>
															<hr style="border-top: 1px dashed red;">
															<p><strong class="dark-gray">Poll summary:</strong> '.$inquiry_overview.'';
														
															$query_user_choice = "SELECT * FROM poll_ballot JOIN poll_response ON poll_ballot.question_id = poll_response.question_id WHERE poll_ballot.question_id = ? AND ballot_user = ? AND answer_id = response_id";

															if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
															 	mysqli_stmt_bind_param($stmt_user_choice, "is", $inquiry_id, $poll_user);
																mysqli_stmt_execute($stmt_user_choice);
																$result_user_choice = mysqli_stmt_get_result($stmt_user_choice);
																
																if (!$result_user_choice) {
															        exit();
															    }

															  	if (mysqli_num_rows($result_user_choice) > 0) {
															        while ($row = mysqli_fetch_assoc($result_user_choice)) {

																	$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
																	$response_type = htmlspecialchars(strip_tags($row['response_type']));
																	$response_key = htmlspecialchars(strip_tags($row['response_key']));
																	
																	$data .='<br>
																				<strong class="dark-gray">Poll type:</strong> <code>'.$response_type.'</code><br>';
																				
																	$query_response_key = "SELECT * FROM poll_response WHERE response_key = ?";

																	if ($stmt_response_key = mysqli_prepare($dbc, $query_response_key)) {
																	    $response_key_value = '1';
																	    mysqli_stmt_bind_param($stmt_response_key, "s", $response_key_value);
																		mysqli_stmt_execute($stmt_response_key);
																		$result_response_key = mysqli_stmt_get_result($stmt_response_key);

																	   	if (!$result_response_key) {
																	        exit();
																	    }

																	    $row_response_key = mysqli_fetch_assoc($result_response_key);
																	    $value_response_key = $row_response_key['response_key'];
																	    $response_answer_key = $row_response_key['response_answer'];

																	    mysqli_stmt_close($stmt_response_key);
																					
																		if($response_key == $value_response_key){
																			$data .='<strong class="dark-gray">Your choice:
																						<i class="fa-solid fa-circle-check text-success"></i>
																						<span class="text-success">'.$response_answer.'</span>
																					 </strong>
																					 <br>
																					 <strong class="dark-gray">Answer:
																						<span class="text-success">'.$response_answer_key.'</span>
																					 </strong>';
																		} else {
																			$data .='<strong class="dark-gray">Your choice:
																						<i class="fa-solid fa-circle-xmark text-hot"></i> 
																						<span class="text-hot">'.$response_answer.'</span>
																					 </strong>
																					 <br>
																					 <strong class="dark-gray">Answer:
																						<span class="text-success">'.$response_answer_key.'</span>
																					 </strong>';
																		} 
																				
																	$data .='</p>';
																}
															}
												
														} else {
															$data .='<br><strong class="dark-gray">Your choice:</strong> <code>You have already completed this poll</code></p>';
														}
														
														$data .='<p>This poll has been completed. <br>
								   						 	   Use the button below to access your poll results.
							    							</p> 
															<button type="button" class="btn btn-primary" onclick="startAssignedPoll('.$inquiry_id.');">
																<i class="fa-solid fa-chart-bar"></i> View My Results
															</button>
														 </div>';
														 
													 }
													 
											} else {
							
												$data .='<div class="p-4 bg-body-tertiary border rounded-3">
															<h5 class="dark-gray">
																<span class="badge bg-mint" style="font-size:16px; line-height:16px; width:140px;">
																	<i class="far fa-check-circle"></i> Active 
																</span>
															</h5>
															<hr style="border-top: 1px dashed red;">
															<p><strong class="dark-gray">Poll summary:</strong> '.$inquiry_overview.'';
														
															$query_user_choice = "SELECT * FROM poll_ballot 
															                      JOIN poll_response 
															                      ON poll_ballot.question_id = poll_response.question_id 
															                      WHERE poll_ballot.question_id = ? 
															                      AND ballot_user = ? 
															                      AND answer_id = response_id";

															if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
															 	mysqli_stmt_bind_param($stmt_user_choice, "is", $inquiry_id, $poll_user);
																mysqli_stmt_execute($stmt_user_choice);
																$result_user_choice = mysqli_stmt_get_result($stmt_user_choice);
																
																if (mysqli_num_rows($result_user_choice) > 0) {
															        while ($row = mysqli_fetch_assoc($result_user_choice)) {
															            $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
															            $data .= '<br><strong class="dark-gray">Your choice:</strong> <code>'.$response_answer.'</code></p>';
															        }
															    } else {
															        $data .= '<br><strong class="dark-gray">Your choice:</strong> <code>You have not yet answered this poll</code></p>';
															    }

															    mysqli_stmt_close($stmt_user_choice);
															} else {
															    exit();
															}

															$data .= '<p>This poll is currently active. <br>
								   						 	   Use the button below to access the poll.
							    							</p> 
															<button type="button" class="btn btn-forest" onclick="startAssignedPoll('.$inquiry_id.');">
																<i class="fa-solid fa-chart-bar"></i> Start the Poll
															</button>
														 </div>';
											
													 }
											
				                     	} else if($inquiry_status == "Paused"){
							
											$data .='<div class="p-4 bg-body-tertiary border rounded-3">
														<h5 class="dark-gray">
															<span class="badge bg-hot" style="font-size:16px; line-height:16px; width:140px;">
																<i class="fa-regular fa-circle-pause"></i> Paused 
															</span>
														</h5>
														<hr style="border-top: 1px dashed red;">
														<p><strong class="dark-gray">Poll summary:</strong> '.$inquiry_overview.'';
														
														$query_user_choice = "SELECT * FROM poll_ballot 
														                      JOIN poll_response 
														                      ON poll_ballot.question_id = poll_response.question_id 
														                      WHERE poll_ballot.question_id = ? 
														                      AND ballot_user = ? 
														                      AND answer_id = response_id";

														if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
														  	mysqli_stmt_bind_param($stmt_user_choice, "is", $inquiry_id, $poll_user);
															mysqli_stmt_execute($stmt_user_choice);
															$result_user_choice = mysqli_stmt_get_result($stmt_user_choice);

														  	if (mysqli_num_rows($result_user_choice) > 0) {
														        while ($row = mysqli_fetch_assoc($result_user_choice)) {
														            $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
														            $data .= '<br><strong class="dark-gray">Your choice:</strong> <code>'.$response_answer.'</code></p>';
														        }
														    } else {
														        $data .= '<br><strong class="dark-gray">Your choice:</strong> <code>You have not yet answered this poll</code></p>';
														    }

														    mysqli_stmt_close($stmt_user_choice);
														} else {
														    exit();
														}

														$data .= '<p>This poll has been marked as paused. <br>
							   						 	   Results for this poll are not currently accessible.
						    							</p> 
													</div>';
							
										} else if($inquiry_status == "Closed"){
							
											$data .='<div class="p-4 bg-body-tertiary border rounded-3">
														<h5 class="dark-gray">
															<span class="badge bg-concrete" style="font-size:16px; line-height:16px; width:140px;">
																<i class="fa-solid fa-check-to-slot"></i> Closed 
															</span>
														</h5>
														<hr style="border-top: 1px dashed red;">
														
														<p><strong class="dark-gray">Poll summary:</strong> '.$inquiry_overview.'';
														
														$query_user_choice = "SELECT * FROM poll_ballot 
														                      JOIN poll_response 
														                      ON poll_ballot.question_id = poll_response.question_id 
														                      WHERE poll_ballot.question_id = ? 
														                      AND ballot_user = ? 
														                      AND answer_id = response_id";

														if ($stmt_user_choice = mysqli_prepare($dbc, $query_user_choice)) {
															mysqli_stmt_bind_param($stmt_user_choice, "is", $inquiry_id, $poll_user);
															mysqli_stmt_execute($stmt_user_choice);
															$result_user_choice = mysqli_stmt_get_result($stmt_user_choice);

														   	if (mysqli_num_rows($result_user_choice) > 0) {
														        while ($row = mysqli_fetch_assoc($result_user_choice)) {
														            $response_answer = htmlspecialchars(strip_tags($row['response_answer']));
            														$data .= '<br><strong class="dark-gray">Your choice:</strong> <code>'.$response_answer.'</code></p>';
														        }
														    } else {
														        $data .= '<br><strong class="dark-gray">Your choice:</strong> <code>You have not yet answered this poll</code></p>';
														    }

														    mysqli_stmt_close($stmt_user_choice);

														    $data .= '<p>This poll has been marked as closed. <br>
							   						 	   Results for this poll should be accessible via the View Poll Results button below.
						    							</p> 
														<button type="button" class="btn btn-forest" id="" onclick="showClosedPollResults();">
															<i class="fa-solid fa-chart-simple"></i> View Poll Results
														</button>
													 </div>';
							
											}
										
										} else {}
										
											$data .='</div>
	 		    								 </div>
					 						</div>
										</div></td></tr>';
			
			 }
			
			mysqli_stmt_close($stmt);
			
		}
		
		$data .='</tbody>
		</table>';
		
					
	} else {
      	$data .='<div><svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 60px auto 0 !important;">
  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
</svg>
<p class="one success">Empty!</p>
<p class="complete">No Polls at this time.</p>';
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