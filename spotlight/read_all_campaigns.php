<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit();
}

function getCampaignInfo($inquiry_id, $inquiry_status, $dbc) {
    $campaign_info = array(
        'nominee_count' => 0,
        'vote_count' => 0,
        'enrollment_count' => 0,
        'participation_rate' => 0,
        'winner_preview' => '',
        'has_votes' => false
    );
    
	$nominee_query = "SELECT COUNT(*) as nominee_count FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
    $nominee_result = mysqli_query($dbc, $nominee_query);
    if ($nominee_result) {
        $nominee_row = mysqli_fetch_assoc($nominee_result);
        $campaign_info['nominee_count'] = $nominee_row['nominee_count'];
    }
    
	$vote_query = "SELECT COUNT(*) as vote_count FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
    $vote_result = mysqli_query($dbc, $vote_query);
    if ($vote_result) {
        $vote_row = mysqli_fetch_assoc($vote_result);
        $campaign_info['vote_count'] = $vote_row['vote_count'];
        $campaign_info['has_votes'] = $vote_row['vote_count'] > 0;
    }
    
	$enrollment_query = "SELECT COUNT(*) as enrollment_count FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";
    $enrollment_result = mysqli_query($dbc, $enrollment_query);
    if ($enrollment_result) {
        $enrollment_row = mysqli_fetch_assoc($enrollment_result);
        $campaign_info['enrollment_count'] = $enrollment_row['enrollment_count'];
    }
    
 	if ($campaign_info['enrollment_count'] > 0) {
        $campaign_info['participation_rate'] = round(($campaign_info['vote_count'] / $campaign_info['enrollment_count']) * 100, 1);
    }
    
	if ($inquiry_status == 'Closed' && $campaign_info['has_votes']) {
        $leader_query = "
            SELECT 
                u.first_name,
                u.last_name,
                COUNT(sb.answer_id) as vote_count
            FROM spotlight_nominee sn
            JOIN users u ON sn.assignment_user = u.user
            LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$inquiry_id'
            WHERE sn.question_id = '$inquiry_id'
            GROUP BY sn.assignment_user, u.first_name, u.last_name
            ORDER BY vote_count DESC
            LIMIT 1";
        
        $leader_result = mysqli_query($dbc, $leader_query);
        if ($leader_result && mysqli_num_rows($leader_result) > 0) {
            $leader_row = mysqli_fetch_assoc($leader_result);
            if ($leader_row['vote_count'] > 0) {
                $campaign_info['winner_preview'] = $leader_row['first_name'] . ' ' . $leader_row['last_name'] . ' (' . $leader_row['vote_count'] . ' votes)';
            }
        }
    }
    
    return $campaign_info;
}

// NEW FUNCTION: Check if logged-in user is enrolled in a spotlight
function isUserEnrolled($inquiry_id, $username, $dbc) {
    $username_escaped = mysqli_real_escape_string($dbc, $username);
    $inquiry_id_escaped = mysqli_real_escape_string($dbc, $inquiry_id);
    
    $enrollment_check = "SELECT COUNT(*) as is_enrolled 
                         FROM spotlight_assignment 
                         WHERE spotlight_id = '$inquiry_id_escaped' 
                         AND assignment_user = '$username_escaped'";
    
    $result = mysqli_query($dbc, $enrollment_check);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['is_enrolled'] > 0;
    }
    
    return false;
}

// NEW FUNCTION: Check if user has already voted
function hasUserVoted($inquiry_id, $username, $dbc) {
    $username_escaped = mysqli_real_escape_string($dbc, $username);
    $inquiry_id_escaped = mysqli_real_escape_string($dbc, $inquiry_id);
    
    $vote_check = "SELECT COUNT(*) as has_voted 
                   FROM spotlight_ballot 
                   WHERE question_id = '$inquiry_id_escaped' 
                   AND ballot_user = '$username_escaped'";
    
    $result = mysqli_query($dbc, $vote_check);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['has_voted'] > 0;
    }
    
    return false;
}

function hasShowcasePeriodStarted($showcase_start_date) {
    if (empty($showcase_start_date)) {
        return true;
    }
    
    $start_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_start_date);
    if (!$start_datetime) {
        return true;
    }
    
    $now = new DateTime();
    return $now >= $start_datetime;
}

function hasShowcasePeriodEnded($showcase_end_date) {
    if (empty($showcase_end_date)) {
        return false;
    }
    
    $end_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_end_date);
    if (!$end_datetime) {
        return false;
    }
    
    $now = new DateTime();
    return $now >= $end_datetime;
}

if (isset($_SESSION['id'])) {
	$current_user = $_SESSION['user']; // Get the logged-in user's USERNAME (not numeric ID)
	$data = '';
	$query = "SELECT * FROM spotlight_inquiry 
              WHERE inquiry_status IN ('Active', 'Closed') 
              ORDER BY inquiry_status ASC, inquiry_display_order ASC, inquiry_creation_date DESC";
    
    if (!$result = mysqli_query($dbc, $query)) {
        $data = '<div class="alert alert-danger">Error loading campaigns.</div>';
    } else {
        
        $campaigns = array();
		while ($row = mysqli_fetch_array($result)) {
            $inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
            $showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
            $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
            
       	 	if ($inquiry_status === 'Active' || 
                ($inquiry_status === 'Closed' && !hasShowcasePeriodStarted($showcase_start_date)) ||
                ($inquiry_status === 'Closed' && hasShowcasePeriodEnded($showcase_end_date))) {
                $campaigns[] = $row;
            }
        }
        
        if (empty($campaigns)) {
            $data = '<div class="empty-state">
                        <i class="fa-solid fa-list"></i>
                        <h4>No Campaigns</h4>
                        <p class="mb-0">There are currently no active campaigns or upcoming showcases.</p>
                     </div>';
        } else {
            
            $data .= '<div class="row g-4">';
            
            foreach ($campaigns as $row) {
                
                $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
                $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
                $inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
                $inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
                $inquiry_opening = htmlspecialchars(strip_tags($row['inquiry_opening'] ?? ''));
                $inquiry_closing = htmlspecialchars(strip_tags($row['inquiry_closing'] ?? ''));
                $showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
                $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
                $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
                $inquiry_nominee_image = htmlspecialchars(strip_tags($row['inquiry_nominee_image'] ?? ''));
                $inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? 'No description available.'));
                $inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? 'Active'));
                $nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? 'Team Members'));
                $bullet_one = htmlspecialchars(strip_tags($row['bullet_one'] ?? ''));
                $bullet_two = htmlspecialchars($row['bullet_two'] ?? '');
                $bullet_three = htmlspecialchars($row['bullet_three'] ?? '');
                $special_preview = htmlspecialchars(strip_tags($row['special_preview'] ?? ''));
                
            	if (empty($inquiry_image)) {
                    $inquiry_image = 'media/links/default_spotlight_image.png';
                }
                
                // Use nominee image for display, fallback to default if not set
                if (empty($inquiry_nominee_image)) {
                    $display_image = 'img/profile_pic/default_img/pizza_panda.jpg';
                } else {
                    $display_image = $inquiry_nominee_image;
                }
                
                // Check if user is enrolled and has voted
                $user_enrolled = isUserEnrolled($inquiry_id, $current_user, $dbc);
                $user_voted = hasUserVoted($inquiry_id, $current_user, $dbc);
                
             	$campaign_info = getCampaignInfo($inquiry_id, $inquiry_status, $dbc);
            	$campaign_phase = '';
                $timing_info = '';
                $status_class = '';
                
                if ($inquiry_status === 'Active') {
                    $campaign_phase = 'Voting Open';
                    $status_class = 'text-success';
                    
                    if (!empty($inquiry_closing)) {
                        $closing_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $inquiry_closing);
                        if ($closing_datetime) {
                            $now = new DateTime();
                            $diff = $now->diff($closing_datetime);
                            if ($closing_datetime > $now) {
                                if ($diff->days > 0) {
                                    $timing_info = 'Closes in ' . $diff->days . ' days';
                                } elseif ($diff->h > 0) {
                                    $timing_info = 'Closes in ' . $diff->h . ' hours';
                                } else {
                                    $timing_info = 'Closes soon';
                                }
                            }
                        }
                    }
                } elseif ($inquiry_status === 'Closed') {
                    $campaign_phase = 'Awaiting Close';
                    $status_class = 'text-secondary';
                    
                    if (!empty($showcase_start_date) && !hasShowcasePeriodStarted($showcase_start_date)) {
                        $start_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_start_date);
                        if ($start_datetime) {
                            $now = new DateTime();
                            $diff = $now->diff($start_datetime);
                            if ($start_datetime > $now) {
                                if ($diff->days > 0) {
                                    $timing_info = 'Results in ' . $diff->days . ' days';
                                } elseif ($diff->h > 0) {
                                    $timing_info = 'Results in ' . $diff->h . ' hours';
                                } else {
                                    $timing_info = 'Results soon';
                                }
                            }
                        }
                    }
                }
                
                $data .= '<div class="col-md-6 col-lg-4">
                          <div class="card h-100 spotlight-card">
                          <div class="status-badge badge bg-' . ($inquiry_status === 'Active' ? 'success' : 'secondary') . '">
                            <i class="fa-solid fa-' . ($inquiry_status === 'Active' ? 'circle-play' : 'circle-check') . ' me-2"></i>
                            <span>' . $campaign_phase . '</span>
                          </div>';
                
                $data .= '<img src="' . $display_image . '" class="spotlight-image" alt="Spotlight Image" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                          <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <h5 class="card-title mb-2">' . $inquiry_name . '</h5>
                                <p class="card-text text-muted small mb-2">' . $inquiry_overview . '</p>
                            </div>';
                
               	if ($inquiry_status === 'Closed' && !empty($campaign_info['winner_preview'])) {
                    $data .= '<div class="winner-preview mb-3">
                                <div class="alert alert-info py-2">
                                    <small>
                                        <i class="fa-solid fa-trophy me-2"></i>
                                        <strong>Winner:</strong> ' . $campaign_info['winner_preview'] . '
                                    </small>
                                </div>
                              </div>';
                }
                
            	if (!empty($bullet_one) || !empty($bullet_two) || !empty($bullet_three)) {
                    $qualities = array_filter([$bullet_one, $bullet_two, $bullet_three]);
                    if (count($qualities) > 0) {
                        $data .= '<div class="campaign-details mb-3">
                                    <h6 class="text-secondary mb-2">Recognizing:</h6>
                                    <div class="small text-muted">' . implode(' • ', array_slice($qualities, 0, 2)) . '</div>
                                  </div>';
                    }
                }
                
              	$data .= '<div class="stats-grid mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold text-primary">' . $campaign_info['nominee_count'] . '</div>
                                    <small class="text-muted">Nominees</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-info">' . $campaign_info['vote_count'] . '</div>
                                    <small class="text-muted">Votes</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold ' . $status_class . '">' . $campaign_info['participation_rate'] . '%</div>
                                    <small class="text-muted">Participation</small>
                                </div>
                            </div>
                          </div>';
                
           		if (!empty($timing_info)) {
                    $data .= '<div class="timing-info mb-3">
                                <div class="alert alert-light border py-2">
                                    <small class="' . $status_class . '">
                                        <i class="fa-solid fa-clock me-2"></i>' . $timing_info . '
                                    </small>
                                </div>
                              </div>';
                }
                
            	if (!empty($special_preview)) {
                    $data .= '<div class="alert alert-warning small mb-3">' . $special_preview . '</div>';
                }
                
             	$data .= '<div class="mt-auto">';
                
                // FIXED LOGIC: Check enrollment status for Active spotlights
                if ($inquiry_status === 'Active') {
                    if ($user_enrolled) {
                        if ($user_voted) {
                            // User has already voted
                            $data .= '<a href="spotlight_dashboard.php" class="btn btn-outline-success w-100">
                                        <i class="fa-solid fa-check-circle me-2"></i>Vote Submitted
                                      </a>';
                        } else {
                            // User is enrolled but hasn't voted yet
                            $data .= '<a href="spotlight_dashboard.php" class="btn btn-primary w-100">
                                        <i class="fa-solid fa-vote-yea me-2"></i>Vote Now
                                      </a>';
                        }
                    } else {
                        // User is NOT enrolled - show disabled button
                        $data .= '<div class="btn btn-outline-secondary w-100 disabled" title="You are not enrolled in this spotlight">
                                    <i class="fa-solid fa-lock me-2"></i>Not Enrolled
                                  </div>';
                    }
                } elseif ($inquiry_status === 'Closed' && !empty($campaign_info['winner_preview'])) {
                    $data .= '<a href="spotlight_winner.php?id=' . $inquiry_id . '" class="btn btn-outline-info w-100">
                                <i class="fa-solid fa-eye me-2"></i>Preview Results
                              </a>';
                } else {
                    $data .= '<div class="btn btn-outline-secondary w-100 disabled">
                                <i class="fa-solid fa-clock me-2"></i>Awaiting Results
                              </div>';
                }
                
                $data .= '</div>
                          </div>
                          </div>
                          </div>';
            }
            
            $data .= '</div>';
        }
    }
    
    echo $data;
    
} else {
    echo '<div class="alert alert-warning">Please log in to view campaigns.</div>';
}

mysqli_close($dbc);
?>