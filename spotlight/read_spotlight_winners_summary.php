<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_reports')){
    header("Location:../../index.php?msg1");
    exit();
}

$data = '';

$query = "
    SELECT 
        si.inquiry_id,
        si.inquiry_name,
        si.inquiry_status,
        si.inquiry_creation_date,
        si.inquiry_author,
        si.inquiry_image,
        si.inquiry_closing,
        COUNT(DISTINCT sb.ballot_user) as total_votes
    FROM spotlight_inquiry si
    LEFT JOIN spotlight_ballot sb ON si.inquiry_id = sb.question_id
    WHERE si.inquiry_status IN ('Closed', 'Active')
    GROUP BY si.inquiry_id
    HAVING total_votes > 0
    ORDER BY si.inquiry_creation_date DESC";

$result = mysqli_query($dbc, $query);

if ($result && mysqli_num_rows($result) > 0) {
    
    $data .= '<div class="row mb-4">
                <div class="col-md-12">
                    <div class="bg-white rounded shadow-sm p-4 border">
                        <h6 class="text-primary mb-3">
                            <i class="fa-solid fa-trophy me-2"></i>Spotlight Winners Overview
                        </h6>
                        <p class="text-muted mb-0">Complete summary of all spotlight winners, including vote counts and completion dates.</p>
                    </div>
                </div>
              </div>';

    $data .= '<div class="row g-4">';
    
    while ($row = mysqli_fetch_assoc($result)) {
        
        $inquiry_id = $row['inquiry_id'];
        $inquiry_name = htmlspecialchars($row['inquiry_name']);
        $inquiry_status = $row['inquiry_status'];
        $inquiry_author = htmlspecialchars($row['inquiry_author']);
        $inquiry_image = $row['inquiry_image'] ?: 'media/links/default_spotlight_image.png';
        $total_votes = $row['total_votes'];
        $creation_date = $row['inquiry_creation_date'];
        $closing_date = $row['inquiry_closing'];
   	 	$winner_details = getDetailedWinnerInfo($inquiry_id, $dbc);
   	 	$status_badge = $inquiry_status == 'Closed' ? 'bg-success' : 'bg-primary';
        $status_text = $inquiry_status == 'Closed' ? 'Completed' : 'Active';
        
        $data .= '
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border">
                <div class="card-header bg-light">
                    <div class="d-flex align-items-center">
                        <img src="' . htmlspecialchars($inquiry_image) . '" 
                             class="rounded-circle me-3" width="40" height="40" 
                             alt="Spotlight" onerror="this.src=\'media/links/default_spotlight_image.png\'">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">' . $inquiry_name . '</h6>
                            <small class="text-muted">Created by ' . $inquiry_author . '</small>
                        </div>
                        <span class="badge ' . $status_badge . '">' . $status_text . '</span>
                    </div>
                </div>
                
                <div class="card-body">';
        
   		if ($winner_details['has_winner']) {
            if ($winner_details['is_tie']) {
                $data .= '
                    <div class="alert alert-warning mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-handshake fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="mb-1">TIE RESULT</h6>
                                <small>' . count($winner_details['winners']) . ' nominees tied with ' . $winner_details['winning_votes'] . ' votes each</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Tied Winners:</h6>';
                
                foreach ($winner_details['winners'] as $winner) {
                    $data .= '
                        <div class="d-flex align-items-center mb-2">
                            <img src="' . htmlspecialchars($winner['profile_pic']) . '" 
                                 class="rounded-circle me-2" width="32" height="32" 
                                 alt="Winner" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                            <div class="flex-grow-1">
                                <div class="fw-bold">' . htmlspecialchars($winner['name']) . '</div>
                                <small class="text-muted">' . $winner['votes'] . ' votes</small>
                            </div>
                            <span class="winner-badge">TIE</span>
                        </div>';
                }
                
                $data .= '</div>';
                
            } else {
                $winner = $winner_details['winners'][0];
                $data .= '
                    <div class="alert alert-success mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-crown fa-2x text-warning me-3"></i>
                            <div>
                                <h6 class="mb-1">WINNER</h6>
                                <small>' . htmlspecialchars($winner['name']) . ' with ' . $winner['votes'] . ' votes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-3">
                        <img src="' . htmlspecialchars($winner['profile_pic']) . '" 
                             class="rounded-circle shadow" width="80" height="80" 
                             alt="Winner" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                        <h5 class="mt-2 mb-1">' . htmlspecialchars($winner['name']) . '</h5>
                        <div class="winner-badge">' . $winner['votes'] . ' votes</div>
                    </div>';
            }
            
     	   if (count($winner_details['all_nominees']) > 1) {
                $data .= '
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">All Results:</h6>';
                
                foreach ($winner_details['all_nominees'] as $nominee) {
                    $percentage = $total_votes > 0 ? round(($nominee['votes'] / $total_votes) * 100, 1) : 0;
                    $bar_color = $nominee['votes'] == $winner_details['winning_votes'] ? 'bg-success' : 'bg-secondary';
                    
                    $data .= '
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="fw-bold">' . htmlspecialchars($nominee['name']) . '</small>
                                <small class="text-muted">' . $nominee['votes'] . ' votes (' . $percentage . '%)</small>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar ' . $bar_color . '" style="width: ' . $percentage . '%"></div>
                            </div>
                        </div>';
                }
                
                $data .= '</div>';
            }
            
        } else {
            $data .= '
                <div class="alert alert-info mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-clock fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="mb-1">IN PROGRESS</h6>
                            <small>' . $total_votes . ' votes cast so far</small>
                        </div>
                    </div>
                </div>';
        }
        
        $data .= '
                </div>
                
                <div class="card-footer bg-white border-top text-center">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Votes</small>
                            <strong class="text-primary">' . $total_votes . '</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Created</small>
                            <strong class="text-muted">' . date('M d, Y', strtotime(str_replace('/', '-', $creation_date))) . '</strong>
                        </div>
                    </div>';
        
        if ($closing_date && $inquiry_status == 'Closed') {
            $data .= '
                    <hr class="my-2">
                    <small class="text-success">
                        <i class="fa-solid fa-flag-checkered me-1"></i>
                        Completed: ' . date('M d, Y', strtotime(str_replace('/', '-', $closing_date))) . '
                    </small>';
        }
        
        $data .= '
                </div>
            </div>
        </div>';
    }
    
    $data .= '</div>';
    
} else {
    $data .= '
    <div class="text-center py-5">
        <i class="fa-solid fa-trophy fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No Winners Yet</h5>
        <p class="text-muted">Complete some spotlights with votes to see winners here.</p>
    </div>';
}

echo $data;

function getDetailedWinnerInfo($inquiry_id, $dbc) {
    $winner_info = array(
        'has_winner' => false,
        'is_tie' => false,
        'winners' => array(),
        'all_nominees' => array(),
        'winning_votes' => 0
    );
    
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
                'votes' => $votes,
                'profile_pic' => $profile_pic
            );
            
            $nominees[] = $nominee_data;
            
            if ($votes > $highest_votes) {
                $highest_votes = $votes;
            }
        }
        
        $winner_info['all_nominees'] = $nominees;
        
        if ($highest_votes > 0) {
            $winners = array_filter($nominees, function($nominee) use ($highest_votes) {
                return $nominee['votes'] == $highest_votes;
            });
            
            $winner_info['has_winner'] = true;
            $winner_info['winning_votes'] = $highest_votes;
            $winner_info['winners'] = array_values($winners);
            $winner_info['is_tie'] = count($winners) > 1;
        }
    }
    
    return $winner_info;
}

mysqli_close($dbc);
?>