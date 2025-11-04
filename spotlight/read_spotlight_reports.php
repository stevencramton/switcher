<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_reports')){
	header("Location:../../index.php?msg1");
	exit();
}

$data = '';

if (isset($_SESSION['id'])) {
	$status_filter = isset($_POST['status_filter']) ? mysqli_real_escape_string($dbc, $_POST['status_filter']) : '';
	$search_filter = isset($_POST['search_filter']) ? mysqli_real_escape_string($dbc, $_POST['search_filter']) : '';
	$where_conditions = array();
	
	if (!empty($status_filter)) {
		$where_conditions[] = "spotlight_inquiry.inquiry_status = '$status_filter'";
	}
	
	$where_clause = '';
	if (!empty($where_conditions)) {
		$where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
	}
	
	$query = "SELECT * FROM spotlight_inquiry $where_clause ORDER BY inquiry_creation_date DESC";
	
	if (!$result = mysqli_query($dbc, $query)) {
		exit("Query failed: " . mysqli_error($dbc));
	}
	
	if (mysqli_num_rows($result) > 0) {
		
		$data .= '<div class="accordion accordion-flush" id="accordionspotlightReports">';
		
		while ($row = mysqli_fetch_array($result)) {
			
			$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_id'] ?? ''));
			$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
			$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
			$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
			$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
			$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
			$inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? 'No description available.'));
			
			if ($inquiry_image == '' || $inquiry_image == null){
				$inquiry_image = 'media/links/default_spotlight_image.png';
			}
			
			$query_spotlight_answers = "SELECT COUNT(*) as answer_count FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
			$spotlight_answers_count = 0;
			if ($spotlight_answers_results = mysqli_query($dbc, $query_spotlight_answers)){
				$answer_row = mysqli_fetch_assoc($spotlight_answers_results);
				$spotlight_answers_count = $answer_row['answer_count'];
			}
			
			$query_enrollment_count = "SELECT COUNT(*) as enrolled_count FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";
			$enrolled_users = 0;
			if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
				$enrollment_row = mysqli_fetch_assoc($enrollment_results);
				$enrolled_users = $enrollment_row['enrolled_count'];
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
			$winner_info = getSpotlightWinnerInfo($inquiry_id, $dbc);
			
			if (!empty($search_filter)) {
				$search_text = strtolower($inquiry_name . ' ' . $inquiry_author . ' ' . $winner_info['winner_text']);
				if (strpos($search_text, strtolower($search_filter)) === false) {
					continue;
				}
			}
			
			$status_class = 'bg-secondary';
			$status_icon = 'fa-circle-question';
			switch($inquiry_status) {
				case 'Active':
					$status_class = 'bg-success';
					$status_icon = 'fa-circle-play';
					break;
				case 'Closed':
					$status_class = 'bg-dark';
					$status_icon = 'fa-circle-check';
					break;
				case 'Paused':
					$status_class = 'bg-warning';
					$status_icon = 'fa-circle-pause';
					break;
			}
			
			$data .='<div class="accordion-item count-spotlight-item">
						<h2 class="accordion-header" id="flush-heading-'.$inquiry_id.'">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
								data-bs-target="#flush-collapse-'.$inquiry_id.'" aria-expanded="false" 
								aria-controls="flush-collapse-'.$inquiry_id.'">
								
								<div class="d-flex w-100 align-items-center">
									<div class="flex-shrink-0 me-3">
										<img src="'.$inquiry_image.'" class="rounded-circle" width="50" height="50" 
										alt="Spotlight" onerror="this.src=\'media/links/default_spotlight_image.png\'">
									</div>
									<div class="flex-grow-1">
										<div class="d-flex justify-content-between align-items-start">
											<div>
												<h6 class="mb-1">'.$inquiry_name.'</h6>
												<small class="text-muted">by '.$inquiry_author.' • '.$inquiry_creation_date.'</small>
											</div>
											<div class="text-end ms-3">
												<span class="badge '.$status_class.' mb-2">
													<i class="fa-solid '.$status_icon.' me-1"></i>'.$inquiry_status.'
												</span>
												<div class="small text-muted">
													<div><i class="fa-solid fa-users me-1"></i>'.$enrolled_users.' enrolled</div>
													<div><i class="fa-solid fa-vote-yea me-1"></i>'.$ballot_votes.' votes</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</button>
						</h2>
						
						<div id="flush-collapse-'.$inquiry_id.'" class="accordion-collapse collapse" 
						     aria-labelledby="flush-heading-'.$inquiry_id.'" data-bs-parent="#accordionspotlightReports">
							<div class="accordion-body">
								
								<div class="row mb-4">
									<div class="col-md-8">
										<h6 class="text-primary mb-3">
											<i class="fa-solid fa-info-circle me-2"></i>Spotlight Overview
										</h6>
										<p class="mb-3">'.$inquiry_overview.'</p>
										
										<div class="row g-3">
											<div class="col-sm-3">
												<div class="text-center p-3 bg-light rounded">
													<div class="h4 fw-bold text-primary mb-1">'.$enrolled_users.'</div>
													<small class="text-muted">Enrolled Users</small>
												</div>
											</div>
											<div class="col-sm-3">
												<div class="text-center p-3 bg-light rounded">
													<div class="h4 fw-bold text-success mb-1">'.$ballot_votes.'</div>
													<small class="text-muted">Votes Cast</small>
												</div>
											</div>
											<div class="col-sm-3">
												<div class="text-center p-3 bg-light rounded">
													<div class="h4 fw-bold text-info mb-1">'.$spotlight_answers_count.'</div>
													<small class="text-muted">Nominees</small>
												</div>
											</div>
											<div class="col-sm-3">
												<div class="text-center p-3 bg-light rounded">
													<div class="h4 fw-bold text-warning mb-1">'.$percentage_rate.'%</div>
													<small class="text-muted">Participation</small>
												</div>
											</div>
										</div>
									</div>
									
									<div class="col-md-4">
										<h6 class="text-primary mb-3">
											<i class="fa-solid fa-trophy me-2"></i>Results
										</h6>';
			
			if ($winner_info['has_votes']) {
				if ($winner_info['is_tie']) {
					$data .= '<div class="alert alert-warning py-3">
								<div class="d-flex align-items-center">
									<i class="fa-solid fa-handshake fa-2x text-warning me-3"></i>
									<div>
										<h6 class="mb-1">TIE RESULT</h6>
										<small>'.count($winner_info['tied_winners']).' nominees tied with '.$winner_info['winning_votes'].' votes each</small>
									</div>
								</div>
							  </div>';
					
					$data .= '<div class="mb-3"><h6 class="text-muted mb-2">Tied Winners:</h6>';
					foreach($winner_info['tied_winners'] as $winner) {
						$data .= '<div class="d-flex align-items-center mb-2">
									<div class="flex-grow-1">
										<div class="fw-bold">'.htmlspecialchars($winner).'</div>
									</div>
									<span class="badge bg-warning text-dark">TIE</span>
								  </div>';
					}
					$data .= '</div>';
					
				} else {
					$data .= '<div class="alert alert-success py-3">
								<div class="d-flex align-items-center">
									<i class="fa-solid fa-crown fa-2x text-warning me-3"></i>
									<div>
										<h6 class="mb-1">WINNER</h6>
										<small>'.htmlspecialchars($winner_info['winner_name']).' with '.$winner_info['winning_votes'].' votes</small>
									</div>
								</div>
							  </div>';
					
					$data .= '<div class="text-center mb-3">
								<div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
									<i class="fa-solid fa-trophy fa-2x"></i>
								</div>
								<h5 class="mt-2 mb-1">'.htmlspecialchars($winner_info['winner_name']).'</h5>
								<div class="badge bg-success">'.$winner_info['winning_votes'].' votes</div>
							  </div>';
				}
			} else if ($ballot_votes > 0) {
				$data .= '<div class="alert alert-info py-3">
							<div class="d-flex align-items-center">
								<i class="fa-solid fa-clock fa-2x text-info me-3"></i>
								<div>
									<h6 class="mb-1">VOTING IN PROGRESS</h6>
									<small>'.$ballot_votes.' votes cast so far</small>
								</div>
							</div>
						  </div>';
			} else {
				$data .= '<div class="alert alert-secondary py-3">
							<div class="d-flex align-items-center">
								<i class="fa-solid fa-hourglass fa-2x text-secondary me-3"></i>
								<div>
									<h6 class="mb-1">NO VOTES YET</h6>
									<small>Voting has not started</small>
								</div>
							</div>
						  </div>';
			}
			
			$data .= '</div>
					</div>
						<div class="mt-4 pt-3 border-top">
									<div class="btn-group" role="group">
										<button type="button" class="btn btn-primary" onclick="showDetailedVotingResults('.$inquiry_id.');">
											<i class="fa-solid fa-chart-bar me-1"></i> View Statistics
										</button>
										<button type="button" class="btn btn-info" onclick="showVoterDetails('.$inquiry_id.');">
											<i class="fa-solid fa-users-viewfinder me-1"></i> Who Voted for Whom
										</button>
									</div>
								</div>
								
							</div>
						</div>
					</div>';
		}
		
		$data .= '</div>';
		
	} else {
		$data .= '<div class="text-center py-5">
					<i class="fa-solid fa-chart-bar fa-3x text-muted mb-3"></i>
					<h5 class="text-muted">No Spotlight Reports Found</h5>
					<p class="text-muted">Try adjusting your filters or create some spotlights to generate reports.</p>
				  </div>';
	}
}

echo $data;

function getSpotlightWinnerInfo($inquiry_id, $dbc) {
	$winner_info = array(
		'has_votes' => false,
		'is_tie' => false,
		'winner_name' => '',
		'winner_text' => '',
		'tied_winners' => array(),
		'winning_votes' => 0
	);
	
	$query = "
		SELECT 
			sn.assignment_user,
			u.first_name,
			u.last_name,
			COUNT(sb.answer_id) as vote_count
		FROM spotlight_nominee sn
		JOIN users u ON sn.assignment_user = u.user
		LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$inquiry_id'
		WHERE sn.question_id = '$inquiry_id'
		GROUP BY sn.assignment_user, u.first_name, u.last_name
		ORDER BY vote_count DESC";
	
	$result = mysqli_query($dbc, $query);
	
	if ($result && mysqli_num_rows($result) > 0) {
		$nominees = array();
		$highest_votes = 0;
		
		while ($row = mysqli_fetch_assoc($result)) {
			$votes = $row['vote_count'];
			$full_name = $row['first_name'] . ' ' . $row['last_name'];
			
			$nominees[] = array(
				'name' => $full_name,
				'votes' => $votes
			);
			
			if ($votes > $highest_votes) {
				$highest_votes = $votes;
			}
		}
		
		if ($highest_votes > 0) {
			$winner_info['has_votes'] = true;
			$winner_info['winning_votes'] = $highest_votes;
			
			$winners = array_filter($nominees, function($nominee) use ($highest_votes) {
				return $nominee['votes'] == $highest_votes;
			});
			
			if (count($winners) == 1) {
				$winner_info['winner_name'] = $winners[0]['name'];
				$winner_info['winner_text'] = $winners[0]['name'];
			} else {
				$winner_info['is_tie'] = true;
				$winner_info['tied_winners'] = array_column($winners, 'name');
				$winner_info['winner_text'] = implode(', ', $winner_info['tied_winners']);
			}
		}
	}
	
	return $winner_info;
}

mysqli_close($dbc);

?>

<style>
	.ui-state-highlights {
		background: #f8f9fa !important;
		height: 50px;	
	}
</style>

<script>
$(document).ready(function(){
	function countspotlightRecords(){
		var count = $('.count-spotlight-item').length;
	    $('.count-spotlight-records').html(count);
	} 
	countspotlightRecords();

	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>