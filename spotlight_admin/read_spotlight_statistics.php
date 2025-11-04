<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){

	$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
	
	$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'"; 
	
	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
	
	if (mysqli_num_rows($result) > 0) {
		
		while ($row = mysqli_fetch_assoc($result)) {
			
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
			
			if ($inquiry_image == ''){
				$inquiry_image = 'media/links/default_spotlight_image.png';
			}
			
			$query_enrollment_count = "SELECT COUNT(*) as enrolled_count FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";
			$enrolled_users = 0;
			if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
				$enrollment_row = mysqli_fetch_assoc($enrollment_results);
				$enrolled_users = $enrollment_row['enrolled_count'];
			}
			
			$query_nominee_count = "SELECT COUNT(*) as nominee_count FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
			$nominee_count = 0;
			if ($nominee_results = mysqli_query($dbc, $query_nominee_count)){
				$nominee_row = mysqli_fetch_assoc($nominee_results);
				$nominee_count = $nominee_row['nominee_count'];
			}
			
			$query_ballot_count = "SELECT COUNT(*) as vote_count FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
			$ballot_votes = 0;
			if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
				$ballot_row = mysqli_fetch_assoc($ballot_results);
				$ballot_votes = $ballot_row['vote_count'];
			}
			
			$participation_rate = 0;
			if ($enrolled_users > 0) {
				$participation_rate = ($ballot_votes / $enrolled_users) * 100;
			}
			$percentage_rate = number_format($participation_rate, 1);
			
			$data = '';
			
			$data .='<div class="row gx-3">
						<div class="col-md-4">
							<div class="p-3 bg-body rounded border shadow mb-2">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-primary btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-users text-white"></i>
										</span>
										<div>
											<p class="mb-0 small lh-sm">
												<strong class="d-block text-gray-dark">Enrolled - '.$enrolled_users.'</strong>
												People who can vote
											</p>
										</div>
									</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-2">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-success btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-crown text-white"></i>
										</span>
										<div>
											<p class="mb-0 small lh-sm">
												<strong class="d-block text-gray-dark">Nominees - '.$nominee_count.'</strong>
												People who can be voted for
											</p>
										</div>
									</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-2">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-info btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-check-to-slot text-white"></i>
										</span>
										<div>
											<p class="mb-0 small lh-sm">
												<strong class="d-block text-gray-dark">Votes - '.$ballot_votes.'</strong>
												Total votes cast
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="row gx-3">
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-2" style="height:70px;">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-warning btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-bars-progress text-white"></i>
										</span>
										<div class="flex-grow-1">
											<strong class="mb-0 small lh-sm d-block">Completion - '.$percentage_rate.'%</strong>
											<div class="progress lh-1 mt-1" style="height: 8px" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Spotlight Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="'.$percentage_rate.'" aria-valuemin="0" aria-valuemax="100">
												<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-2">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-secondary btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-info-circle text-white"></i>
										</span>
										<div>
											<p class="mb-0 small lh-sm">
												<strong class="d-block text-gray-dark">Status</strong>
												<span class="badge bg-'.($inquiry_status == 'Active' ? 'success' : ($inquiry_status == 'Closed' ? 'dark' : 'warning')).'">'.$inquiry_status.'</span>
											</p>
										</div>
									</div>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="p-3 bg-body rounded border shadow mb-2">
									<div class="d-flex text-body-secondary">
										<span class="btn btn-dark btn-sm me-3" style="width:40px;" disabled>
											<i class="fa-solid fa-user-tie text-white"></i>
										</span>
										<div>
											<p class="mb-0 small lh-sm">
												<strong class="d-block text-gray-dark">Author</strong>
												'.$inquiry_author.'
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="alert alert-info d-flex align-items-center justify-content-between mt-2" role="alert">
							<div class="d-flex align-items-center">
								<i class="fa-solid fa-users-viewfinder fa-2x me-3"></i>
								<div>
									<h6 class="mb-1">Individual Voting Records</h6>
									<small>View detailed breakdown of who voted for whom ('.($ballot_votes > 0 ? $ballot_votes.' votes cast' : 'No votes yet').')</small>
								</div>
							</div>
							<button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVotes" aria-expanded="false" aria-controls="collapseVotes">
								<i class="fa-solid fa-eye me-1"></i> View Voting Details
							</button>
						</div>
						
						<div class="collapse" id="collapseVotes">
							<div class="p-4 bg-body rounded border shadow-sm mb-3">';
			    
			$query_user_choice = "SELECT sb.*, sn.*, u.first_name, u.last_name, u.profile_pic, u.user 
								  FROM spotlight_ballot sb
								  JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id
								  JOIN users u ON sb.ballot_user = u.user 
								  WHERE sb.question_id = '$inquiry_id' 
								  ORDER BY u.first_name ASC";

			if (!$result_user_choice = mysqli_query($dbc, $query_user_choice)) {
				exit();
			}

			if (mysqli_num_rows($result_user_choice) > 0) {
				
				$data .='<h6 class="text-primary mb-3">
							<i class="fa-solid fa-table me-2"></i>Individual Voting Records
						</h6>
						<div class="table-responsive">
							<table class="table table-hover mb-3" width="100%">
								<thead class="table-dark">
									<tr>
										<th width="8%">Photo</th>
										<th width="25%">Voter</th>
										<th width="20%">Username</th>
										<th width="25%">Voted For</th>
										<th width="22%">Agency</th>
									</tr>
								</thead>
								<tbody>';

				while ($row_vote = mysqli_fetch_assoc($result_user_choice)) {
					
					$user_profile_pic = htmlspecialchars(strip_tags($row_vote['profile_pic'] ?? 'img/profile_pic/default_img/pizza_panda.jpg'));
					$ballot_user_first_name = htmlspecialchars(strip_tags($row_vote['first_name'] ?? ''));
					$ballot_user_last_name = htmlspecialchars(strip_tags($row_vote['last_name'] ?? ''));
					$ballot_user_full_name = $ballot_user_first_name . ' ' . $ballot_user_last_name;
					$ballot_username = htmlspecialchars(strip_tags($row_vote['user'] ?? ''));
					$assignment_agency = htmlspecialchars(strip_tags($row_vote['assignment_agency'] ?? 'Unassigned'));
					
					$nominee_user = htmlspecialchars(strip_tags($row_vote['assignment_user'] ?? ''));
					$nominee_query = "SELECT first_name, last_name FROM users WHERE user = '$nominee_user'";
					$nominee_result = mysqli_query($dbc, $nominee_query);
					if ($nominee_result && mysqli_num_rows($nominee_result) > 0) {
						$nominee_row = mysqli_fetch_assoc($nominee_result);
						$nominee_name = htmlspecialchars($nominee_row['first_name']) . ' ' . htmlspecialchars($nominee_row['last_name']);
					} else {
						$nominee_name = 'Unknown Nominee';
					}

					$data .='<tr>
								<td class="align-middle">
									<img src="' . $user_profile_pic . '" class="rounded-circle" width="40" height="40" 
									     alt="Voter" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
								</td>
								<td class="align-middle">
									<div class="fw-bold">'.$ballot_user_full_name.'</div>
									<small class="text-muted">'.$ballot_username.'</small>
								</td>
								<td class="align-middle">'.$ballot_username.'</td>
								<td class="align-middle">
									<div class="fw-bold text-primary">'.$nominee_name.'</div>
									<small class="text-muted">'.$nominee_user.'</small>
								</td>
								<td class="align-middle">
									<span class="badge bg-light text-dark">'.$assignment_agency.'</span>
								</td>
							</tr>';

				}
				
				$data .='</tbody></table></div>';

			} else {
				
				$data .='<div class="text-center py-4">
							<i class="fa-solid fa-users-viewfinder fa-3x text-muted mb-3"></i>
							<h6 class="text-muted">No votes have been submitted</h6>
							<p class="text-muted mb-0">This spotlight hasn\'t received any votes yet.</p>
						</div>';
			}
				
			$data .='</div>
					</div>';
					
			if ($nominee_count > 0) {
				$data .='<div class="p-3 bg-body rounded border shadow mb-3">
							<h6 class="text-secondary mb-3"><i class="fa-solid fa-trophy me-2"></i>Nominee Results</h6>';
				
				$query_nominees = "SELECT sn.*, u.first_name, u.last_name, u.profile_pic 
								   FROM spotlight_nominee sn
								   JOIN users u ON sn.assignment_user = u.user
								   WHERE sn.question_id = '$inquiry_id' 
								   ORDER BY u.first_name ASC";
							
				if (!$result_nominees = mysqli_query($dbc, $query_nominees)) {
					exit();
				}

				if (mysqli_num_rows($result_nominees) > 0) {
					$highest_value = 0;
					$winning_items = array();
					$nominees_data = array();
				
					while ($nominee_row = mysqli_fetch_assoc($result_nominees)) {
						$assignment_id = mysqli_real_escape_string($dbc, strip_tags($nominee_row['assignment_id']));
						$nominee_first_name = htmlspecialchars(strip_tags($nominee_row['first_name']));
						$nominee_last_name = htmlspecialchars(strip_tags($nominee_row['last_name']));
						$nominee_full_name = $nominee_first_name . ' ' . $nominee_last_name;
						$nominee_profile_pic = htmlspecialchars(strip_tags($nominee_row['profile_pic'] ?? 'img/profile_pic/default_img/pizza_panda.jpg'));
						
						$vote_query = "SELECT COUNT(*) as vote_count FROM spotlight_ballot WHERE question_id = '$inquiry_id' AND answer_id = '$assignment_id'";
						$vote_result = mysqli_query($dbc, $vote_query);
						$vote_count = 0;
						if ($vote_result) {
							$vote_row = mysqli_fetch_assoc($vote_result);
							$vote_count = $vote_row['vote_count'];
						}
						
						$percentage = $ballot_votes > 0 ? round(($vote_count / $ballot_votes) * 100, 1) : 0;
						
						$nominees_data[] = array(
							'name' => $nominee_full_name,
							'profile_pic' => $nominee_profile_pic,
							'votes' => $vote_count,
							'percentage' => $percentage
						);
						
						if ($vote_count > $highest_value) {
							$highest_value = $vote_count;
						}
					}
					
					usort($nominees_data, function($a, $b) {
						return $b['votes'] - $a['votes'];
					});
					
					$data .='<div class="row g-3">';
					
					foreach ($nominees_data as $index => $nominee) {
						$progress_color = $index == 0 ? 'bg-success' : ($index == 1 ? 'bg-info' : 'bg-secondary');
						$is_winner = $nominee['votes'] == $highest_value && $highest_value > 0;
						
						$data .='<div class="col-md-6 col-lg-4">
									<div class="card h-100 '.($is_winner ? 'border-success' : '').'">
										<div class="card-body text-center">
											<img src="'.$nominee['profile_pic'].'" class="rounded-circle mb-2" width="60" height="60" 
											     alt="Nominee" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
											<h6 class="mb-2">'.htmlspecialchars($nominee['name']).'</h6>
											<div class="d-flex justify-content-between align-items-center mb-2">
												<span class="badge bg-primary">'.$nominee['votes'].' votes</span>
												<span class="text-muted small">'.$nominee['percentage'].'%</span>
											</div>
											<div class="progress" style="height: 8px;">
												<div class="progress-bar progress-bar-striped '.$progress_color.'" 
												     style="width: '.$nominee['percentage'].'%" 
												     role="progressbar" 
												     aria-valuenow="'.$nominee['percentage'].'" 
												     aria-valuemin="0" 
												     aria-valuemax="100">
												</div>
											</div>
											'.($is_winner && $highest_value > 0 ? '<div class="mt-2"><span class="badge bg-success"><i class="fa-solid fa-trophy me-1"></i>Winner</span></div>' : '').'
										</div>
									</div>
								</div>';
					}
					
					$data .='</div>';
		
				}
		
				$data .='</div>';
			}
		}
		
	} else {
		$data = '<div class="alert alert-warning">
					<i class="fa-solid fa-exclamation-triangle me-2"></i>
					No spotlight data found.
				 </div>';
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