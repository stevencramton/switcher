<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit();
}

function getCompletedSpotlightWinner($inquiry_id, $dbc) {
    $winner_info = array(
        'has_winner' => false,
        'is_tie' => false,
        'winner_name' => '',
        'winner_user' => '',
        'winner_votes' => 0,
        'winner_profile_pic' => '',
        'tied_winners' => array(),
        'total_votes' => 0,
        'all_nominees' => array()
    );
    
	$vote_query = "SELECT COUNT(*) as total_votes FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
    $vote_result = mysqli_query($dbc, $vote_query);
    if ($vote_result) {
        $vote_row = mysqli_fetch_assoc($vote_result);
        $winner_info['total_votes'] = $vote_row['total_votes'];
    }
    
 	$query = "
        SELECT 
            sn.assignment_id,
            sn.assignment_user,
            u.first_name,
            u.last_name,
            u.profile_pic,
            COUNT(sb.answer_id) as vote_count
        FROM spotlight_nominee sn
        JOIN users u ON sn.assignment_user = u.user
        LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$inquiry_id'
        WHERE sn.question_id = '$inquiry_id'
        GROUP BY sn.assignment_id, sn.assignment_user, u.first_name, u.last_name, u.profile_pic
        ORDER BY vote_count DESC, u.first_name ASC";
    
    $result = mysqli_query($dbc, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $nominees = array();
        $highest_votes = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $votes = $row['vote_count'];
            $full_name = $row['first_name'] . ' ' . $row['last_name'];
            $profile_pic = $row['profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg';
            
            $nominee_data = array(
                'name' => $full_name,
                'user' => $row['assignment_user'],
                'votes' => $votes,
                'profile_pic' => $profile_pic
            );
            
            $nominees[] = $nominee_data;
            $winner_info['all_nominees'][] = $nominee_data;
            
            if ($votes > $highest_votes) {
                $highest_votes = $votes;
            }
        }
        
        if ($highest_votes > 0) {
            $winner_info['has_winner'] = true;
            $winner_info['winner_votes'] = $highest_votes;
            
        	$winners = array();
            foreach ($nominees as $nominee) {
                if ($nominee['votes'] == $highest_votes) {
                    $winners[] = $nominee;
                }
            }
            
            if (count($winners) > 1) {
                $winner_info['is_tie'] = true;
                $winner_info['tied_winners'] = $winners;
            } else {
                $winner_info['winner_name'] = $winners[0]['name'];
                $winner_info['winner_user'] = $winners[0]['user'];
                $winner_info['winner_profile_pic'] = $winners[0]['profile_pic'];
            }
        }
    }
    
    return $winner_info;
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
    
    $data = '';
    
	$query = "SELECT * FROM spotlight_inquiry 
              WHERE inquiry_status = 'Closed'
              ORDER BY showcase_end_date DESC, inquiry_closing DESC, inquiry_creation_date DESC";
    
    if (!$result = mysqli_query($dbc, $query)) {
        $data .= '<div class="alert alert-danger">Error loading past winners: ' . mysqli_error($dbc) . '</div>';
    } else {
        
        $past_winners = array();
        
  	  	while ($row = mysqli_fetch_array($result)) {
            $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
            
            if (hasShowcasePeriodEnded($showcase_end_date)) {
                $past_winners[] = $row;
            }
        }
        
        if (empty($past_winners)) {
            $data .= '<div class="empty-state">
                        <i class="fa-solid fa-history"></i>
                        <h4>No Past Winners Yet</h4>
                        <p class="mb-0">Completed spotlight winner showcases will appear here.</p>
                     </div>';
        } else {
            
            $data .= '<div class="row g-4">';
            
            foreach ($past_winners as $row) {
                
                $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
                $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
                $inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
                $inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
                $inquiry_closing = htmlspecialchars(strip_tags($row['inquiry_closing'] ?? ''));
                $showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
                $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
                $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
                $inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? 'No description available.'));
                $nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? 'Team Members'));
                $bullet_one = htmlspecialchars(strip_tags($row['bullet_one'] ?? ''));
                $bullet_two = htmlspecialchars(strip_tags($row['bullet_two'] ?? ''));
                $bullet_three = htmlspecialchars(strip_tags($row['bullet_three'] ?? ''));
                
             	if (empty($inquiry_image)) {
                    $inquiry_image = 'media/links/default_spotlight_image.png';
                }
                
             	$winner_info = getCompletedSpotlightWinner($inquiry_id, $dbc);
                
            	$nominee_query = "SELECT COUNT(*) as count FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
                $nominee_result = mysqli_query($dbc, $nominee_query);
                $nominee_count = $nominee_result ? mysqli_fetch_assoc($nominee_result)['count'] : 0;
                
                $vote_count = $winner_info['total_votes'];
                
              	$showcase_end_info = '';
                if (!empty($showcase_end_date)) {
                    $end_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_end_date);
                    if ($end_datetime) {
                        $showcase_end_info = 'Showcase ended: ' . $end_datetime->format('M j, Y');
                    }
                } elseif (!empty($inquiry_closing)) {
                    $closing_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $inquiry_closing);
                    if ($closing_datetime) {
                        $showcase_end_info = 'Completed: ' . $closing_datetime->format('M j, Y');
                    }
                }
                
              	$data .= '<div class="col-lg-6 col-xl-4">
                            <div class="card h-100 past-winner-card">
                              <div class="winner-badge completed">
                                <i class="fa-solid fa-flag-checkered me-2"></i>
                                <span>Past Winner</span>
                              </div>
                              <img src="' . $inquiry_image . '" class="spotlight-image" alt="Spotlight Image" onerror="this.src=\'media/links/default_spotlight_image.png\'">
                              <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <h5 class="card-title mb-2">' . $inquiry_name . '</h5>
                                    <p class="card-text text-muted small mb-2">' . $inquiry_overview . '</p>
                                </div>';
                
             	if ($winner_info['has_winner']) {
                    $data .= '<div class="winner-showcase mb-3">';
                    
                    if ($winner_info['is_tie']) {
                        $data .= '<h6 class="text-warning mb-3"><i class="fa-solid fa-handshake me-2"></i>Final Tie</h6>
                                  <div class="row g-2">';
                        
                        foreach ($winner_info['tied_winners'] as $winner) {
                            $data .= '<div class="col-6">
                                        <div class="winner-profile text-center">
                                            <img src="' . $winner['profile_pic'] . '" 
                                                 class="winner-avatar rounded-circle mb-2" 
                                                 width="50" height="50"
                                                 onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                                            <div class="winner-name small">' . $winner['name'] . '</div>
                                            <div class="winner-votes small">' . $winner['votes'] . ' votes</div>
                                        </div>
                                      </div>';
                        }
                        
                        $data .= '</div>';
                    } else {
                        $data .= '<h6 class="text-success mb-3"><i class="fa-solid fa-trophy me-2"></i>Champion</h6>
                                  <div class="winner-profile text-center">
                                    <img src="' . $winner_info['winner_profile_pic'] . '" 
                                         class="winner-avatar rounded-circle mb-3" 
                                         width="70" height="70"
                                         onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                                    <div class="winner-name mb-1">' . $winner_info['winner_name'] . '</div>
                                    <div class="winner-votes">' . $winner_info['winner_votes'] . ' votes</div>
                                  </div>';
                    }
                    
                    $data .= '</div>';
                } else {
                    $data .= '<div class="no-winner-info mb-3 text-center text-muted">
                                <i class="fa-solid fa-question-circle fa-2x mb-2"></i>
                                <div>No votes were cast</div>
                              </div>';
                }
                
            	if (!empty($bullet_one) || !empty($bullet_two) || !empty($bullet_three)) {
                    $qualities = array_filter([$bullet_one, $bullet_two, $bullet_three]);
                    $data .= '<div class="qualities-section mb-3">
                                <h6 class="text-secondary mb-2">Recognized for:</h6>
                                <div class="small text-muted">' . implode(' • ', $qualities) . '</div>
                              </div>';
                }
                
              	$data .= '<div class="stats-grid mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold text-secondary">' . $nominee_count . '</div>
                                    <small class="text-muted">Nominees</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-secondary">' . $vote_count . '</div>
                                    <small class="text-muted">Votes</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-secondary">Complete</div>
                                    <small class="text-muted">Status</small>
                                </div>
                            </div>
                          </div>';
                
            	if (!empty($showcase_end_info)) {
                    $data .= '<div class="completion-info mb-3 text-center">
                                <small class="text-muted">
                                    <i class="fa-solid fa-calendar-check me-1"></i>
                                    ' . $showcase_end_info . '
                                </small>
                              </div>';
                }
                
             	$data .= '<div class="mt-auto">
                            <a href="spotlight_winner.php?id=' . $inquiry_id . '" class="btn btn-outline-secondary w-100">
                              <i class="fa-solid fa-history me-2"></i>View Details
                            </a>
                          </div>
                          </div>
                          </div>
                          </div>';
            }
            
            $data .= '</div>';
        }
    }
    
    echo $data;
    
} else {
    echo '<div class="alert alert-warning">Please log in to view past winners.</div>';
}

mysqli_close($dbc);
?>