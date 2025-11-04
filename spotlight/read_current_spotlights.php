<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

echo '<style>
.avatar-group {
    display: inline-flex;
}
.avatar-group .avatar:not(:last-child) {
    -webkit-mask-image: radial-gradient(
        circle at calc(100% - var(--bs-avatar-group-offset) + var(--bs-avatar-group-outline-width)) 50%,
        transparent calc(var(--bs-avatar-size) / 2 + var(--bs-avatar-group-outline-width) / 2),
        #000 calc(var(--bs-avatar-size) / 2 + var(--bs-avatar-group-outline-width) / 2)
    );
    mask-image: radial-gradient(
        circle at calc(100% - var(--bs-avatar-group-offset) + var(--bs-avatar-group-outline-width)) 50%,
        transparent calc(var(--bs-avatar-size) / 2 + var(--bs-avatar-group-outline-width) / 2),
        #000 calc(var(--bs-avatar-size) / 2 + var(--bs-avatar-group-outline-width) / 2)
    );
}
.avatar {
    --bs-avatar-size: calc(1.8rem + var(--bs-border-width) * 2);
    --bs-avatar-border-radius: 50rem;
    --bs-avatar-font-size: 0.875rem;
    --bs-avatar-bg: var(--bs-tertiary-bg);
    --bs-avatar-color: var(--bs-body-color);
    --bs-avatar-group-offset: calc(var(--bs-avatar-size) / -5);
    --bs-avatar-group-outline-width: calc(var(--bs-avatar-size) / 15);
    --bs-avatar-status-border-width: var(--bs-border-width);
    --bs-avatar-status-border-color: var(--bs-border-color);
    align-items: center;
    background-color: var(--bs-avatar-bg);
    border-radius: var(--bs-avatar-border-radius);
    color: var(--bs-avatar-color);
    display: inline-flex;
    font-size: var(--bs-avatar-font-size);
    height: var(--bs-avatar-size);
    justify-content: center;
    min-width: var(--bs-avatar-size);
    position: relative;
    text-transform: uppercase;
    width: var(--bs-avatar-size);
}
.avatar-group .avatar + .avatar {
    margin-left: var(--bs-avatar-group-offset);
}
.avatar-img {
    border-radius: inherit;
    height: 100%;
    object-fit: cover;
    width: 100%;
}
</style>';

function getSpotlightWinner($inquiry_id, $dbc) {
    $winner_info = array(
        'has_winner' => false,
        'is_tie' => false,
        'winner_name' => '',
        'winner_user' => '',
        'winner_votes' => 0,
        'winner_profile_pic' => '',
        'tied_winners' => array(),
        'runners_up' => array(),
        'total_votes' => 0
    );
    
	$vote_query = "SELECT COUNT(*) as total_votes FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
    $vote_result = mysqli_query($dbc, $vote_query);
    if ($vote_result) {
        $vote_row = mysqli_fetch_assoc($vote_result);
        $winner_info['total_votes'] = $vote_row['total_votes'];
    }
    
    if ($winner_info['total_votes'] == 0) {
        return $winner_info;
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
        HAVING vote_count > 0
        ORDER BY vote_count DESC";
    
    $result = mysqli_query($dbc, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $all_nominees = array();
        $highest_votes = 0;
        
        while ($row = mysqli_fetch_assoc($result)) {
            $votes = $row['vote_count'];
            $full_name = $row['first_name'] . ' ' . $row['last_name'];
            $profile_pic = !empty($row['profile_pic']) ? $row['profile_pic'] : 'img/profile_pic/default_img/pizza_panda.jpg';
            
        	$nominee_data = array(
                'name' => $full_name,
                'user' => $row['assignment_user'],
                'assignment_id' => $row['assignment_id'],
                'votes' => $votes,
                'profile_pic' => $profile_pic
            );
            
            $all_nominees[] = $nominee_data;
            
            if ($votes > $highest_votes) {
                $highest_votes = $votes;
            }
        }
        
        if ($highest_votes > 0) {
            $winner_info['has_winner'] = true;
            $winner_info['winner_votes'] = $highest_votes;
            
          	$winners = array();
            $runners_up = array();
            
       	 	foreach ($all_nominees as $nominee) {
                if ($nominee['votes'] == $highest_votes) {
                    $winners[] = $nominee;
                } else {
                    $runners_up[] = $nominee;
                }
            }
            
        	$winner_info['runners_up'] = array_slice($runners_up, 0, 4);
            
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

function isShowcasePeriodActive($showcase_start_date, $showcase_end_date) {
    $now = new DateTime();
    
 	if (empty($showcase_start_date) && empty($showcase_end_date)) {
        return true;
    }
    
  	if (!empty($showcase_start_date)) {
        $start_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_start_date);
        if ($start_datetime && $now < $start_datetime) {
            return false;
        }
    }
    
	if (!empty($showcase_end_date)) {
        $end_datetime = DateTime::createFromFormat('m/d/Y, g:i A', $showcase_end_date);
        if ($end_datetime && $now >= $end_datetime) {
            return false;
        }
    }
    
    return true;
}

if (isset($_SESSION['id'])) {
    
    $data = '';
    
 	$query = "SELECT * FROM spotlight_inquiry 
              WHERE inquiry_status = 'Closed'
              ORDER BY inquiry_display_order ASC, inquiry_creation_date DESC";
    
    if (!$result = mysqli_query($dbc, $query)) {
        $data = '<div class="alert alert-danger">Error loading current winners.</div>';
    } else {
        
        $current_winners = array();
        
     	while ($row = mysqli_fetch_array($result)) {
            $showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
            $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
            
            if (isShowcasePeriodActive($showcase_start_date, $showcase_end_date)) {
                $current_winners[] = $row;
            }
        }
        
        if (count($current_winners) > 0) {
        	$data .= '<div class="container-fluid px-2 mt-3" id="blog_posts_container">';
            
            foreach ($current_winners as $row) {
                $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
                $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
                $inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? ''));
                $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? 'media/links/default_spotlight_image.png'));
                $showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
                $showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
            	$nominee_query = "SELECT COUNT(*) as count FROM spotlight_nominee WHERE question_id = '$inquiry_id'";
                $nominee_result = mysqli_query($dbc, $nominee_query);
                $nominee_count = $nominee_result ? mysqli_fetch_assoc($nominee_result)['count'] : 0;
            	$vote_query = "SELECT COUNT(*) as count FROM spotlight_ballot WHERE question_id = '$inquiry_id'";
                $vote_result = mysqli_query($dbc, $vote_query);
                $vote_count = $vote_result ? mysqli_fetch_assoc($vote_result)['count'] : 0;
             	$winner_info = getSpotlightWinner($inquiry_id, $dbc);
                
            	$showcase_info = '';
                if (!empty($showcase_start_date) && !empty($showcase_end_date)) {
                    $showcase_info = "Showcased: " . $showcase_start_date . " - " . $showcase_end_date;
                } elseif (!empty($showcase_start_date)) {
                    $showcase_info = "Showcased from: " . $showcase_start_date;
                } elseif (!empty($showcase_end_date)) {
                    $showcase_info = "Showcased until: " . $showcase_end_date;
                }
                
            	if ($winner_info['has_winner']) {
                    if ($winner_info['is_tie']) {
                      	foreach ($winner_info['tied_winners'] as $index => $tied_winner) {
                            $left_image = $tied_winner['profile_pic'];
                            $left_image_alt = $tied_winner['name'] . ' (Tie Winner)';
                            $winner_identifier = $tied_winner['user'];
                            
                         	$data .= '<div class="card border-0 shadow-sm mb-3 list-view-card position-relative" style="min-height: 120px;">
                                        <div class="ribbon">
                                            <span><i class="fa-solid fa-handshake"></i> Tie Winner</span>
                                        </div>
                                        <div class="d-flex h-100">
                                            <div class="list-view-image-container">
                                                <img src="' . $left_image . '" class="list-view-image shadow-sm" alt="' . $left_image_alt . '">
                                            </div>
                                            <div class="flex-grow-1 p-3">
                                                <div class="d-flex flex-column h-100">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fa-solid fa-handshake me-2 text-warning"></i>
                                                        <h6 class="mb-0 me-2">' . $inquiry_name . '</h6>
                                                    </div>
                                                    <p class="mb-2 small text-primary fw-bold">Tie Winner: ' . htmlspecialchars($tied_winner['name']) . '</p>
                                                    <p class="mb-2 small text-muted" style="line-height: 1.4;">' . $inquiry_overview . '</p>
                                                    <div class="mb-2">
                                                        <small class="meta-stat">
                                                            <i class="fa-solid fa-user"></i> ' . htmlspecialchars($tied_winner['name']) . ' | 
                                                            <i class="fa-solid fa-users"></i> ' . $nominee_count . ' nominees | 
                                                            <i class="fa-solid fa-vote-yea"></i> ' . $tied_winner['votes'] . ' votes';

                            if (!empty($showcase_info)) {
                                $data .= ' | <i class="fa-solid fa-clock"></i> ' . $showcase_info;
                            }
                            
                            $data .= '</small>
                                    </div>';
                            
                         	if (!empty($winner_info['runners_up']) && $index === 0) {
                                $runners_up_html = '<div class="d-flex align-items-center gap-2">
                                                      <small class="text-muted">Runners-up:</small>
                                                      <div class="avatar-group">'; 
                                
                                foreach ($winner_info['runners_up'] as $runner) {
                                    $runners_up_html .= '<div class="avatar" title="' . htmlspecialchars($runner['name']) . ' (' . $runner['votes'] . ' votes)">
                                                           <img class="avatar-img" src="' . $runner['profile_pic'] . '" alt="' . htmlspecialchars($runner['name']) . '">
                                                         </div>';
                                }
                                
                                $runners_up_html .= '</div>
                                                    <small class="text-muted">';
                                
                            	$runner_names = array();
                                foreach ($winner_info['runners_up'] as $runner) {
                                    $runner_names[] = $runner['name'] . ' (' . $runner['votes'] . ')';
                                }
                                $runners_up_html .= implode(', ', $runner_names);
                                
                                $runners_up_html .= '</small>
                                                   </div>';
                            } else {
                                $runners_up_html = '';
                            }
                            
                            $data .= '<div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <button class="btn btn-primary btn-sm me-1" onclick="window.location.href=\'spotlight_winner.php?id=' . $inquiry_id . '&winner=' . urlencode($winner_identifier) . '\'">
                                                <i class="fa-solid fa-ranking-star"></i> View Spotlight
                                            </button>
                                        </div>
                                        ' . $runners_up_html . '
                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                        }
                    } else {
                     	$left_image = $winner_info['winner_profile_pic'];
                        $left_image_alt = $winner_info['winner_name'] . ' (Winner)';
                        
                        $data .= '<div class="card border-0 shadow-sm mb-3 list-view-card position-relative" style="min-height: 120px;">
                                    <div class="ribbon">
                                        <span><i class="fa-solid fa-trophy"></i> Winner</span>
                                    </div>
                                    <div class="d-flex h-100">
                                        <div class="list-view-image-container">
                                            <img src="' . $left_image . '" class="list-view-image shadow-sm" alt="' . $left_image_alt . '">
                                        </div>
                                        <div class="flex-grow-1 p-3">
                                            <div class="d-flex flex-column h-100">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fa-solid fa-trophy me-2 text-warning"></i>
                                                    <h6 class="mb-0 me-2">' . $inquiry_name . '</h6>
                                                </div>
                                                <p class="mb-2 small text-primary fw-bold">Winner: ' . htmlspecialchars($winner_info['winner_name']) . '</p>
                                                <p class="mb-2 small text-muted" style="line-height: 1.4;">' . $inquiry_overview . '</p>
                                                <div class="mb-2">
                                                    <small class="meta-stat">
                                                        <i class="fa-solid fa-user"></i> ' . htmlspecialchars($winner_info['winner_name']) . ' | 
                                                        <i class="fa-solid fa-users"></i> ' . $nominee_count . ' nominees | 
                                                        <i class="fa-solid fa-vote-yea"></i> ' . $winner_info['winner_votes'] . ' votes';

                        if (!empty($showcase_info)) {
                            $data .= ' | <i class="fa-solid fa-clock"></i> ' . $showcase_info;
                        }
                        
                        $data .= '</small>
                                </div>';
                        
                    	if (!empty($winner_info['runners_up'])) {
                            $runners_up_html = '<div class="d-flex align-items-center gap-2">
                                                  <small class="text-muted">Runners-up:</small>
                                                  <div class="avatar-group">'; 
                            
                            foreach ($winner_info['runners_up'] as $runner) {
                                $runners_up_html .= '<div class="avatar" title="' . htmlspecialchars($runner['name']) . ' (' . $runner['votes'] . ' votes)">
                                                       <img class="avatar-img" src="' . $runner['profile_pic'] . '" alt="' . htmlspecialchars($runner['name']) . '">
                                                     </div>';
                            }
                            
                            $runners_up_html .= '</div>
                                                <small class="text-muted">';
                            
                       	 	$runner_names = array();
                            foreach ($winner_info['runners_up'] as $runner) {
                                $runner_names[] = $runner['name'] . ' (' . $runner['votes'] . ')';
                            }
                            $runners_up_html .= implode(', ', $runner_names);
                            
                            $runners_up_html .= '</small>
                                               </div>';
                        } else {
                            $runners_up_html = '';
                        }
                        
                        $data .= '<div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <button class="btn btn-primary btn-sm me-1" onclick="window.location.href=\'spotlight_winner.php?id=' . $inquiry_id . '\'">
                                            <i class="fa-solid fa-ranking-star"></i> View Spotlight
                                        </button>
                                    </div>
                                    ' . $runners_up_html . '
                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                    }
                } else {
                 	$left_image = $inquiry_image;
                    $left_image_alt = 'Spotlight Image';
                    
                    $data .= '<div class="card border-0 shadow-sm mb-3 list-view-card position-relative" style="min-height: 120px;">
                                <div class="ribbon">
                                    <span><i class="fa-solid fa-clock"></i> In Progress</span>
                                </div>
                                <div class="d-flex h-100">
                                    <div class="list-view-image-container">
                                        <img src="' . $left_image . '" class="list-view-image shadow-sm" alt="' . $left_image_alt . '">
                                    </div>
                                    <div class="flex-grow-1 p-3">
                                        <div class="d-flex flex-column h-100">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa-solid fa-clock me-2 text-info"></i>
                                                <h6 class="mb-0 me-2">' . $inquiry_name . '</h6>
                                            </div>
                                            <p class="mb-2 small text-info fw-bold">Voting in Progress</p>
                                            <p class="mb-2 small text-muted" style="line-height: 1.4;">' . $inquiry_overview . '</p>
                                            <div class="mb-2">
                                                <small class="meta-stat">
                                                    <i class="fa-solid fa-users"></i> ' . $nominee_count . ' nominees | 
                                                    <i class="fa-solid fa-vote-yea"></i> ' . $vote_count . ' votes';

                    if (!empty($showcase_info)) {
                        $data .= ' | <i class="fa-solid fa-clock"></i> ' . $showcase_info;
                    }
                    
                    $data .= '</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <button class="btn btn-secondary btn-sm me-1" disabled>
                                        <i class="fa-solid fa-clock"></i> Voting in Progress
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
                }
            }
            
         	$data .= '</div>';
            
        } else {
            $data = '<div class="text-center py-5">
                        <i class="fa-solid fa-trophy fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Current Winners</h5>
                        <p class="text-muted">There are no spotlight winners currently being showcased.</p>
                    </div>';
        }
    }
    
    echo $data;
} else {
    echo '<div class="alert alert-danger">Session expired. Please log in again.</div>';
}

mysqli_close($dbc);
?>