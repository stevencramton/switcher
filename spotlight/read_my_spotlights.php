<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
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
	function countspotlightRecords(){
		var count = $('.count-spotlight-item').length;
	    $('.count-spotlight-records').html(count);
	} countspotlightRecords();
	
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';

	if (count != 0){
		$(".my_spotlight_count").html(count);
	} else {
		$(".my_spotlight_count").html(count_zero);
	}				
});
</script>

<script>
	$(document).ready(function(){
		let originalRows = [];
		let activeFilters = ['Active', 'Paused', 'Closed'];
	
		setTimeout(function() {
			storeOriginalRows();
		}, 1000);
	
		function storeOriginalRows() {
			originalRows = [];
			$("tbody#sortable_my_spotlight_row tr").each(function() {
				originalRows.push({
					element: $(this).clone(true),
					text: $(this).text().toLowerCase(),
					status: getRowStatus($(this))
				});
			});
		}
	
		function getRowStatus(row) {
			if (row.find('.bg-mint').length > 0) return 'Active';
			if (row.find('.bg-hot').length > 0) return 'Paused';
			if (row.find('.bg-concrete').length > 0) return 'Closed';
			return 'Unknown';
		}
	
		$("#search_my_assigned_spotlights").on("keyup", function() {
			var searchValue = $(this).val();
			var searchBtn = $("#searchBtn");
		
			if (searchValue === '') {
				searchBtn.removeClass('clear-mode')
					   .attr('title', 'Search')
					   .html('<i class="fa-solid fa-magnifying-glass"></i>');
			} else {
				searchBtn.addClass('clear-mode')
					   .attr('title', 'Clear search')
					   .html('<i class="fa-solid fa-circle-xmark"></i>');
			}
		
			applyAllFilters();
		});
	
		$(document).on("click", "#searchBtn", function() {
		    var searchInput = $("#search_my_assigned_spotlights");
		    var searchBtn = $(this);

		    if (searchBtn.hasClass('clear-mode')) {
		        searchInput.val('').trigger('keyup').focus();
		    } else {
		        searchInput.focus();
		    }
		});

		

		$(document).on("click", function(e) {
		    if (!$(e.target).closest('.search-input-container').length) {
		        $("#filterDropdown").removeClass('show');
		        $("#filterBtn").removeClass('active');
		    }
		});

		$(document).on("click", "#filterDropdown", function(e) {
		    e.stopPropagation();
		});

		$(document).on("keypress", "#search_my_assigned_spotlights", function(e) {
		    if (e.which === 13) {
		        var searchBtn = $("#searchBtn");
		        if (searchBtn.hasClass('clear-mode')) {
		            $(this).val('').trigger('keyup');
		        }
		    }
		});
		
		
		$(document).on("click", "#filterBtn", function(e) {
		    e.stopPropagation();
		    var dropdown = $("#filterDropdown");
		    var filterBtn = $(this);
    
		    // Check if dropdown is actually visible, not just if it has 'show' class
		    var isVisible = dropdown.is(':visible') && dropdown.hasClass('show');
    
		    if (isVisible) {
		        dropdown.removeClass('show');
		        filterBtn.removeClass('active');
		    } else {
		        // Force remove any stale classes first, then add
		        dropdown.removeClass('show');
		        filterBtn.removeClass('active');
        
		        // Small delay to ensure DOM is ready, then show
		        setTimeout(function() {
		            dropdown.addClass('show');
		            filterBtn.addClass('active');
		        }, 10);
		    }
		});
		
		
	
		function applyAllFilters() {
			var searchValue = $("#search_my_assigned_spotlights").val().toLowerCase();
			var tbody = $("tbody#sortable_my_spotlight_row");
			tbody.empty();
			originalRows.forEach(function(rowData) {
				var matchesSearch = searchValue === '' || rowData.text.indexOf(searchValue) > -1;
				var matchesStatus = activeFilters.includes(rowData.status);
			
				if (matchesSearch && matchesStatus) {
					tbody.append(rowData.element.clone(true));
				}
			});
		
			$('[data-bs-toggle="tooltip"]').tooltip();
		}
	
		window.applyFilters = function() {
			activeFilters = [];
			$("#filterDropdown input[type='checkbox']:checked").each(function() {
				activeFilters.push($(this).val());
			});
		
			var filterBtn = $("#filterBtn");
			if (activeFilters.length < 3) {
				filterBtn.addClass('active');
			} else {
				filterBtn.removeClass('active');
			}
		
			applyAllFilters();
		
			$("#filterDropdown").removeClass('show');
			$("#filterBtn").removeClass('active');
		
			var statusText = activeFilters.length === 3 ? 'All statuses' : activeFilters.join(', ');
		};
	
		window.clearAllFilters = function() {
			$("#filterDropdown input[type='checkbox']").prop('checked', true);
			activeFilters = ['Active', 'Paused', 'Closed'];
			$("#search_my_assigned_spotlights").val('').trigger('keyup');
			applyAllFilters();
			$("#filterBtn").removeClass('active');
			$("#filterDropdown").removeClass('show');
		};
	
		window.refreshSpotlightFilters = function() {
			setTimeout(function() {
				storeOriginalRows();
				applyAllFilters();
			}, 500);
		};
	
	});
</script>

<?php

if (isset($_SESSION['id'])) {
	
	$data ='<script>
		$(document).ready(function(){
		 	$(".spotlight_drag_icon").mousedown(function(){
		 		$("#sortable_my_spotlight_row").sortable({
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

		 	$("tbody#sortable_my_spotlight_row tr").each(function() {
		 		selectedItem.push($(this).data("id"));
		 	});
	
		 	var dataString = "sort_order="+selectedItem;
	
		 	$.ajax({
		  	  	type: "GET",
		   	 	url: "ajax/spotlight/update_assignment_display_order.php",
		   	  	data: dataString, 
		  	   	cache: false,
		   	  	success: function(data){
		     		readspotlightReports();
					
					var toastTrigger = document.getElementById("sortable_my_spotlight_row")
					var toastLiveExample = document.getElementById("toast-spotlight-manager-order")
	
					if (toastTrigger) {
						var toast = new bootstrap.Toast(toastLiveExample);
						toast.show()
				   	} 
				}
			});
		 }
		 </script>';

$data .='<table class="table table-borderless" style="padding:0px;">
	 		<tbody id="sortable_my_spotlight_row">';

	$spotlight_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
				
	$query = "SELECT * FROM spotlight_inquiry JOIN spotlight_assignment ON spotlight_inquiry.inquiry_id = spotlight_assignment.spotlight_id WHERE spotlight_assignment.assignment_user = '$spotlight_user' ORDER BY assignment_display_order ASC";	 
	
    if (!$result = mysqli_query($dbc, $query)) {
        exit();
    }
	
 	if (mysqli_num_rows($result) > 0) {
		
        while ($row = mysqli_fetch_array($result)) {
						
			$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? 'Unknown author'));
			$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? 'No date'));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? 'Untitled Spotlight'));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			$inquiry_nominee_image = htmlspecialchars(strip_tags($row['inquiry_nominee_image'] ?? ''));
			$nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? 'Nominee'));
			$bullet_one = htmlspecialchars(strip_tags($row['bullet_one'] ?? ''));
			$bullet_two = htmlspecialchars(strip_tags($row['bullet_two'] ?? ''));
			$bullet_three = htmlspecialchars(strip_tags($row['bullet_three'] ?? ''));
			$special_preview = htmlspecialchars(strip_tags($row['special_preview'] ?? ''));
			$inquiry_opening = htmlspecialchars(strip_tags($row['inquiry_opening'] ?? ''));
			$inquiry_closing = htmlspecialchars(strip_tags($row['inquiry_closing'] ?? ''));
			$inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? 'No description available'));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? 'Unknown'));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? 'No question available'));
			$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info'] ?? ''));
			$assignment_read = htmlspecialchars(strip_tags($row['assignment_read'] ?? '0'));
			
			if ($inquiry_image == ''){
							
				$inquiry_image = 'media/links/default_spotlight_image.png';
							
			} else {
							
				$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			}
						
			$query_spotlight_answers = "SELECT * FROM spotlight_response WHERE question_id = '$inquiry_id'";

			if ($spotlight_answers_results = mysqli_query($dbc, $query_spotlight_answers)){
		   	 	$spotlight_answers_count = mysqli_num_rows($spotlight_answers_results);
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
				$participation_rate = $participation_rate * 100;
				$percentage_rate = number_format($participation_rate, 2, '.', '');
			} else {
				$participation_rate = $ballot_votes / $enrolled_users;
				$participation_rate = $participation_rate * 100;
				$percentage_rate = number_format($participation_rate, 2, '.', '');
			}
			
			$data .='<tr class="mb-0" data-id="'.$row['inquiry_id'].'">
			      		<td class="align-middle spotlight_drag_icon text-secondary bg-light" style="cursor:move; width:3%;"><i class="fa-solid fa-grip-vertical"></i></td>
				  		<td class="bg-light">
							<div class="accordion accordion-flush mb-2" id="accordionspotlightReports">
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
	 		    					 <div id="accord_'.$inquiry_id.'" class="accordion-collapse collapse" data-bs-parent="#accordionspotlightReports">
	 		      						<div class="accordion-body">';
										
										if($inquiry_status == "Active"){
											
											$query_ballot_sub = "SELECT * FROM spotlight_ballot WHERE ballot_user = '$spotlight_user' AND question_id = '$inquiry_id'";

											if ($ballot_sub_results = mysqli_query($dbc, $query_ballot_sub)){
										 	   $ballot_sub_votes = mysqli_num_rows($ballot_sub_results);
										   	} 
											
											if ($ballot_sub_votes > 0) {
												
												$data .='<div class="p-4 bg-body-tertiary border rounded-3">
															<h5 class="dark-gray">
																<span class="badge bg-cool-ice" style="font-size:16px; line-height:16px; width:140px;">
																	<i class="far fa-check-circle"></i> Completed 
																</span>
															</h5>
															<hr style="border-top: 1px dashed red;">
															<p><strong class="dark-gray">spotlight summary:</strong> '.$inquiry_overview.'';
														
															$query_user_choice = "SELECT sb.*, sn.*, u.first_name, u.last_name 
																				 FROM spotlight_ballot sb 
																				 JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id AND sb.question_id = sn.question_id
																				 JOIN users u ON sn.assignment_user = u.user 
																				 WHERE sb.question_id = '$inquiry_id' AND sb.ballot_user = '$spotlight_user'";
															
															if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
																exit();
															}

															if (mysqli_num_rows($result_user_choice) > 0) {
																while ($row = mysqli_fetch_assoc($result_user_choice)) {
																	$nominee_first_name = htmlspecialchars(strip_tags($row['first_name']));
																	$nominee_last_name = htmlspecialchars(strip_tags($row['last_name']));
																	$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;

																	$data .='<br><strong class="dark-gray">Your choice:</strong> <code>'.$nominee_full_name.'</code></p>';
																}
															} else {
																$data .='<br><strong class="dark-gray">Your choice:</strong> <code>You have already completed this spotlight</code></p>';
															}
														
															 $data .='<p>This spotlight has been completed. <br>
								   						 	   Use the button below to access your spotlight results.
							    							</p> 
															<button type="button" class="btn btn-primary" onclick="viewMySpotlightResults('.$inquiry_id.');">
																<i class="fa-solid fa-chart-bar"></i> View My Results
															</button>
														 </div>';
														 
												} else {
							
												$data .='<div class="row g-3">
															<div class="col-md-8">
																<div class="bg-dark rounded-3 border shadow" style="padding: 2rem!important;">
																	
																	<div class="d-flex display-5 fw-bold text-white mb-3">
																		<div class="p-0" style="margin-right:10px;">
																			<img class="img-fluid rounded-3 mb-3" src="'.$inquiry_image.'" height="75" width="75" alt="..."> 
																		</div>
																		<div class="" style="margin-top:11px;">
																			<span class="text-white">'.$inquiry_name.'</span>
																		</div>
																		<div class="ms-auto">
																			<a href="#" class="btn btn-dark btn-sm" id="" style="cursor:pointer;color:#20c997;"> 
																				<i class="fab fa-first-order-alt" style="color:#44be9a;"></i> '.$inquiry_status.'
																			</a>
																		</div>
																	</div>
																	
																	<p class="lead fw-normal text-white opacity-75">
																		'.$inquiry_overview.'
																	</p>
																	<div class="lead fw-normal fs-5 text-hot mb-4" style="font-family:Chalkduster, fantasy;">
																		<ul>
																			<li>'.$bullet_one.'</li>
																			<li>'.$bullet_two.'</li>
																			<li>'.$bullet_three.'</li>
																		</ul> 
																	</div>
																	<p class="text-white-50 mb-2">
																		'.$special_preview.'
																	</p>';
														
														$query_user_choice = "SELECT sb.*, sn.*, u.first_name, u.last_name 
																			 FROM spotlight_ballot sb 
																			 JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id AND sb.question_id = sn.question_id
																			 JOIN users u ON sn.assignment_user = u.user 
																			 WHERE sb.question_id = '$inquiry_id' AND sb.ballot_user = '$spotlight_user'";
														
														if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
															exit();
														}

														if (mysqli_num_rows($result_user_choice) > 0) {
															while ($row = mysqli_fetch_assoc($result_user_choice)) {
																$nominee_first_name = htmlspecialchars(strip_tags($row['first_name']));
																$nominee_last_name = htmlspecialchars(strip_tags($row['last_name']));
																$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;

																$data .='<div><strong class="text-white-50">Your choice:</strong> <code>'.$nominee_full_name.'</code></div>';
															}
														} else {
															$data .='<div><strong class="text-white-50">Your choice:</strong> <code>You have not yet answered this spotlight</code></div>';
														}
														
															 $data .='<div class="row">
																		<div class="col-md-12">
																			<div class="d-grid mt-4">
																				<button type="button" class="btn btn-orange btn-lg shadow-sm w-100" id="show-spotlight-nominee-form" onclick="startAssignedspotlight('.$inquiry_id.');" style="height:50px;">
																					<i class="fa-regular fa-circle-right"></i> Get Started
																				</button>
																			</div>
																		</div>
																	</div>
																</div>
															</div>
															
															<div class="col-md-4">
																<div class="text-center mb-3 p-3 border rounded bg-white shadow-sm">
																	<!-- <i class="fab fa-jedi-order text-white-50" style="font-size:356px;"></i> -->
																	<img class="img-fluid rounded-3 shadow-sm" src="'.$inquiry_nominee_image.'" height="375" width="375" alt="...">
																</div>
																<p class="lead fw-normal fs-2 text-hot mb-2 text-center" style="font-family:Chalkduster, fantasy;">
																	'.$nominee_name.'
																</p>
																<div class="ratings text-center mb-3">
																	<i class="fa fa-star rating-color"></i>
																	<i class="fa fa-star rating-color"></i>
																	<i class="fa fa-star rating-color"></i>
																	<i class="fa fa-star rating-color"></i>
																	<i class="fa fa-star rating-color"></i>
																</div>
															</div>
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
														<p><strong class="dark-gray">spotlight summary:</strong> '.$inquiry_overview.'';
														
														$query_user_choice = "SELECT sb.*, sn.*, u.first_name, u.last_name 
																			 FROM spotlight_ballot sb 
																			 JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id AND sb.question_id = sn.question_id
																			 JOIN users u ON sn.assignment_user = u.user 
																			 WHERE sb.question_id = '$inquiry_id' AND sb.ballot_user = '$spotlight_user'";
														
														if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
															exit();
														}

														if (mysqli_num_rows($result_user_choice) > 0) {
															while ($row = mysqli_fetch_assoc($result_user_choice)) {
																$nominee_first_name = htmlspecialchars(strip_tags($row['first_name']));
																$nominee_last_name = htmlspecialchars(strip_tags($row['last_name']));
																$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;

																$data .='<br><strong class="dark-gray">Your choice:</strong> <code>'.$nominee_full_name.'</code></p>';
															}
														} else {
															$data .='<br><strong class="dark-gray">Your choice:</strong> <code>You have not yet answered this spotlight</code></p>';
														}
														
														$data .='<p>This spotlight has been marked as paused. <br>
							   						 	   Results for this spotlight are not currently accessible.
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
														
														<p><strong class="dark-gray">spotlight summary:</strong> '.$inquiry_overview.'';
														
														$query_user_choice = "SELECT sb.*, sn.*, u.first_name, u.last_name 
																			 FROM spotlight_ballot sb 
																			 JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id AND sb.question_id = sn.question_id
																			 JOIN users u ON sn.assignment_user = u.user 
																			 WHERE sb.question_id = '$inquiry_id' AND sb.ballot_user = '$spotlight_user'";
														
														if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
															exit();
														} 

														if (mysqli_num_rows($result_user_choice) > 0) {
															while ($row = mysqli_fetch_assoc($result_user_choice)) {
																$nominee_first_name = htmlspecialchars(strip_tags($row['first_name']));
																$nominee_last_name = htmlspecialchars(strip_tags($row['last_name']));
																$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;

																$data .='<br><strong class="dark-gray">Your choice:</strong> <code>'.$nominee_full_name.'</code></p>';
															}
														} else {
															$data .='<br><strong class="dark-gray">Your choice:</strong> <code>You have not yet answered this spotlight</code></p>';
														}
															 
														$data .='<p>This spotlight has been marked as closed. <br>
							   						 	   Results for this spotlight should be accessible via the View spotlight Results button below.
						    							</p> 
														<button type="button" class="btn btn-forest" id="" onclick="showClosedspotlightResults();">
															<i class="fa-solid fa-chart-simple"></i> View spotlight Results
														</button>
													 </div>';
							
										} else {}
										
											$data .='</div>
	 		    								 </div>
					 					    </div>
										</div></td></tr>';
			
		}
		
		$data .='</tbody>
		</table>';
		
					
	} else {
     	$data .='<div><svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 60px auto 0 !important;">
  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
</svg>
<p class="one success">Empty!</p>
<p class="complete">No spotlights at this time.</p>';
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