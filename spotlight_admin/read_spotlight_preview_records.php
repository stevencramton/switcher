<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}
?>

<?php

if (isset($_SESSION['id'])) {
	
	$data ='';

	if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";	
	} else {
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";	
	}

	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
	
	if (mysqli_num_rows($result) > 0) {
	  
		while ($row = mysqli_fetch_assoc($result)) {
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? 'Unknown author'));
			$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? 'No date'));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? 'Untitled Spotlight'));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			$inquiry_nominee_image = htmlspecialchars(strip_tags($row['inquiry_nominee_image'] ?? ''));
			$nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? ''));
			$bullet_one = htmlspecialchars(strip_tags($row['bullet_one'] ?? ''));
			$bullet_two = htmlspecialchars(strip_tags($row['bullet_two'] ?? ''));
			$bullet_three = htmlspecialchars(strip_tags($row['bullet_three'] ?? ''));
			$special_preview = htmlspecialchars(strip_tags($row['special_preview'] ?? ''));
			$inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? ''));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? 'Unknown'));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? ''));
			$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info'] ?? ''));
			
			if (empty($inquiry_image)) {
				$inquiry_image = 'media/links/default_spotlight_image.png';
			}
			
			if (empty($inquiry_nominee_image)) {
				$inquiry_nominee_image = 'img/profile_pic/default_img/pizza_panda.jpg';
			}
			
			if (empty($nominee_name)) {
				$nominee_name = 'Panda Power';
			}
			
			if (empty($bullet_one)) {
				$bullet_one = 'Exceptional customer service skills!';
			}
			
			if (empty($bullet_two)) {
				$bullet_two = 'A strong and caring work ethic!';
			}
			
			if (empty($bullet_three)) {
				$bullet_three = 'Overall outstanding job performance!';
			}
			
			if (empty($special_preview)) {
				$special_preview = '*This Spotlight is currently inactive but please stay tuned as new spotlights are showcased often. Spotlights aim to promote recognition and positivity within our working community.';
			}
			
			if (empty($inquiry_overview)) {
				$inquiry_overview = 'Welcome to our Spotlight Showcase - a space that aims to recognize the dedicated work and outstanding performance of our colleagues. This application allows users to nominate their peers for a spotlight kudo by voting on those who have demonstrated:';
			}
			
			$status_class = 'btn-dark';
			$status_icon = 'fa-circle-pause';
			$status_text = 'Inactive';
			$status_color = '#6c757d';
			
			if ($inquiry_status == 'Active') {
				$status_class = 'btn-dark';
				$status_icon = 'fab fa-first-order-alt';
				$status_text = 'Active';
				$status_color = '#44be9a';
			}
			
			$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";
			$enrolled_users = 0;
			if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
				$enrolled_users = mysqli_num_rows($enrollment_results);
			}
			
			$query_nominee_count = "SELECT * FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
			$nominee_count = 0;
			if ($nominee_results = mysqli_query($dbc, $query_nominee_count)){
				$nominee_count = mysqli_num_rows($nominee_results);
			}
			
			$data .='<div class="row g-3">
						<div class="col-md-8">
							<div class="bg-dark rounded-3 border shadow" style="padding: 2rem!important;">
								
								<div class="d-flex display-5 fw-bold text-white mb-3">
									<div class="p-0" style="margin-right:10px;">
										<img class="img-fluid rounded-3 mb-3" src="'.$inquiry_image.'" height="75" width="75" alt="Spotlight Image"> 
									</div>
									<div class="" style="margin-top:11px;">
										<span class="text-white text-break">'.$inquiry_name.'</span>
									</div>
									<div class="ms-auto">
										<a href="#" class="btn '.$status_class.' btn-sm" style="cursor:pointer;color:'.$status_color.';"> 
											<i class="'.$status_icon.'" style="color:'.$status_color.';"></i> '.$status_text.'
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
								</p>
								<div><strong class="text-white-50">Your choice:</strong> <code>You have not yet answered this spotlight</code></div>
								<div class="row">
									<div class="col-md-12">
										<div class="d-grid mt-4">
											<button type="button" class="btn btn-orange btn-lg shadow-sm w-100" style="height:50px;" disabled>
												<i class="fa-regular fa-circle-right"></i> Get Started
											</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="col-md-4">
							<div class="text-center mb-3 p-3 border rounded bg-white shadow-sm">
								<img class="img-fluid rounded-3 shadow-sm" src="'.$inquiry_nominee_image.'" height="375" width="375" alt="Winner Image">
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
					</div>
					
					<div class="row mt-3">
						<div class="col-md-12">
							
						<div class="shadow-sm bg-white rounded border">
							<div class="accordion accordion-flush" id="spotlightStatsAccordion">
								<div class="accordion-item">
									<h2 class="accordion-header">
										<button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#spotlightStats" aria-expanded="false" aria-controls="spotlightStats">
											<span class="btn btn-primary btn-sm me-3" style="width:40px;" disabled>
												<i class="fa-solid fa-chart-simple text-white"></i>
											</span>
											<span class="w-75">
												<strong class="dark-gray">Spotlight Statistics</strong> 
												<span class="text-secondary">View detailed information about this spotlight</span>
											</span>
										</button>
									</h2>
									<div id="spotlightStats" class="accordion-collapse collapse" data-bs-parent="#spotlightStatsAccordion">
										<div class="accordion-body">
											<div class="row g-3">
												<div class="col-md-4">
													<div class="bg-light rounded p-3">
														<h6 class="text-secondary mb-2"><i class="fa-solid fa-info-circle me-2"></i>Basic Info</h6>
														<div class="mb-1"><span class="fw-bold">Name:</span> <span class="text-secondary">'.$inquiry_name.'</span></div>
														<div class="mb-1"><span class="fw-bold">Status:</span> <span class="badge bg-'.($inquiry_status == 'Active' ? 'success' : 'secondary').' ms-2">'.$inquiry_status.'</span></div>
														<div class="mb-0"><span class="fw-bold">Author:</span> <span class="text-secondary">'.$inquiry_author.'</span></div>
													</div>
												</div>
												<div class="col-md-4">
													<div class="bg-light rounded p-3">
														<h6 class="text-secondary mb-2"><i class="fa-solid fa-users me-2"></i>Participation</h6>
														<div class="mb-1"><span class="fw-bold">Enrolled:</span> <span class="text-secondary">'.$enrolled_users.' users</span></div>
														<div class="mb-1"><span class="fw-bold">Nominees:</span> <span class="text-secondary">'.$nominee_count.' people</span></div>
														<div class="mb-0"><span class="fw-bold">Progress:</span> <span class="text-secondary">0% complete</span></div>
													</div>
												</div>
												<div class="col-md-4">
													<div class="bg-light rounded p-3">
														<h6 class="text-secondary mb-2"><i class="fa-solid fa-calendar me-2"></i>Timeline</h6>
														<div class="mb-1"><span class="fw-bold">Created:</span> <span class="text-secondary">'.$inquiry_creation_date.'</span></div>
														<div class="mb-1"><span class="fw-bold">Opens:</span> <span class="text-secondary">Not scheduled</span></div>
														<div class="mb-0"><span class="fw-bold">Closes:</span> <span class="text-secondary">Not scheduled</span></div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
							
					</div>
				</div>';
		}

	} else {
        
		$data .='<div class="row g-3">
					<div class="col-md-12">
						<div class="alert alert-warning" role="alert">
							<h4 class="alert-heading"><i class="fa-solid fa-triangle-exclamation"></i> No Spotlight Found</h4>
							<p>The spotlight you are trying to preview could not be found. Please make sure the spotlight exists and try again.</p>
							<hr>
							<p class="mb-0">Return to the spotlight list and select a valid spotlight to preview.</p>
						</div>
					</div>
				</div>';
	
	}

	echo $data;
}

mysqli_close($dbc);
?>