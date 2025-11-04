<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_reports')){
    header("Location:../../index.php?msg1");
    exit();
}

$status_filter = isset($_POST['status_filter']) ? mysqli_real_escape_string($dbc, $_POST['status_filter']) : '';
$date_filter = isset($_POST['date_filter']) ? mysqli_real_escape_string($dbc, $_POST['date_filter']) : '';
$search_filter = isset($_POST['search_filter']) ? mysqli_real_escape_string($dbc, $_POST['search_filter']) : '';
$where_conditions = array();

if (!empty($status_filter)) {
    $where_conditions[] = "si.inquiry_status = '$status_filter'";
}

if (!empty($date_filter)) {
    $date_condition = "";
    switch($date_filter) {
        case 'last_30':
            $date_condition = "STR_TO_DATE(si.inquiry_creation_date, '%m-%d-%Y %h:%i %p') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'last_90':
            $date_condition = "STR_TO_DATE(si.inquiry_creation_date, '%m-%d-%Y %h:%i %p') >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
            break;
        case 'this_year':
            $date_condition = "YEAR(STR_TO_DATE(si.inquiry_creation_date, '%m-%d-%Y %h:%i %p')) = YEAR(CURDATE())";
            break;
    }
    if ($date_condition) {
        $where_conditions[] = $date_condition;
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
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
        COUNT(DISTINCT sa.assignment_user) as enrolled_count,
        COUNT(DISTINCT sb.ballot_user) as voted_count,
        COUNT(DISTINCT sn.assignment_user) as nominee_count
    FROM spotlight_inquiry si
    LEFT JOIN spotlight_assignment sa ON si.inquiry_id = sa.spotlight_id
    LEFT JOIN spotlight_ballot sb ON si.inquiry_id = sb.question_id  
    LEFT JOIN spotlight_nominee sn ON si.inquiry_id = sn.question_id
    $where_clause
    GROUP BY si.inquiry_id
    ORDER BY si.inquiry_creation_date DESC";

$result = mysqli_query($dbc, $query);

if ($result && mysqli_num_rows($result) > 0) {
    
    $data .= '<div class="reports-grid">';
    
    while ($row = mysqli_fetch_assoc($result)) {
 	   	$inquiry_id = $row['inquiry_id'];
        $inquiry_name = htmlspecialchars($row['inquiry_name']);
        $inquiry_status = $row['inquiry_status'];
        $inquiry_author = htmlspecialchars($row['inquiry_author']);
        $inquiry_image = $row['inquiry_image'] ?: 'media/links/default_spotlight_image.png';
        $enrolled_count = $row['enrolled_count'];
        $voted_count = $row['voted_count'];
        $nominee_count = $row['nominee_count'];
     	$participation_rate = $enrolled_count > 0 ? round(($voted_count / $enrolled_count) * 100, 1) : 0;
    	$winner_info = getSpotlightWinner($inquiry_id, $dbc);
        
      	if (!empty($search_filter)) {
            $search_text = strtolower($inquiry_name . ' ' . $inquiry_author . ' ' . $winner_info['winner_name']);
            if (strpos($search_text, strtolower($search_filter)) === false) {
                continue;
            }
        }
        
     	$status_class = '';
        $status_icon = '';
		
        switch($inquiry_status) {
            case 'Active':
                $status_class = 'spotlight-status-active';
                $status_icon = 'fa-circle-play';
                break;
            case 'Closed':
                $status_class = 'spotlight-status-closed';
                $status_icon = 'fa-circle-check';
                break;
            case 'Paused':
                $status_class = 'spotlight-status-paused';
                $status_icon = 'fa-circle-pause';
                break;
            default:
                $status_class = 'spotlight-status-closed';
                $status_icon = 'fa-circle-question';
        }
        
        $data .= '
        <div class="report-item">
            <div class="card report-item h-100" onclick="showDetailedVotingResults(' . $inquiry_id . ')">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <div class="d-flex align-items-center">
                        <img src="' . htmlspecialchars($inquiry_image) . '" 
                             class="rounded-circle me-3" width="40" height="40" 
                             alt="Spotlight Image" onerror="this.src=\'media/links/default_spotlight_image.png\'">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">' . $inquiry_name . '</h6>
                            <small class="text-muted">by ' . $inquiry_author . '</small>
                        </div>
                        <span class="badge ' . $status_class . ' px-2 py-1">
                            <i class="fa-solid ' . $status_icon . ' me-1"></i>' . $inquiry_status . '
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="small text-muted">Enrolled</div>
                            <div class="fw-bold text-primary">' . $enrolled_count . '</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Voted</div>
                            <div class="fw-bold text-success">' . $voted_count . '</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Nominees</div>
                            <div class="fw-bold text-info">' . $nominee_count . '</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Participation</small>
                            <small class="text-muted">' . $participation_rate . '%</small>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-info" style="width: ' . $participation_rate . '%"></div>
                        </div>
                    </div>';
        
		if ($winner_info['has_winner']) {
            if ($winner_info['is_tie']) {
                $data .= '
                    <div class="alert alert-warning py-2 mb-2">
                        <i class="fa-solid fa-handshake me-1"></i>
                        <small><strong>Tie:</strong> ' . count($winner_info['winners']) . ' nominees tied with ' . $winner_info['winning_votes'] . ' votes each</small>
                    </div>';
            } else {
                $data .= '
                    <div class="alert alert-success py-2 mb-2">
                        <i class="fa-solid fa-trophy me-1"></i>
                        <small><strong>Winner:</strong> ' . htmlspecialchars($winner_info['winner_name']) . ' (' . $winner_info['winning_votes'] . ' votes)</small>
                    </div>';
            }
        } else if ($voted_count > 0) {
            $data .= '
                <div class="alert alert-info py-2 mb-2">
                    <i class="fa-solid fa-clock me-1"></i>
                    <small>Voting in progress - ' . $voted_count . ' votes cast</small>
                </div>';
        } else {
            $data .= '
                <div class="alert alert-secondary py-2 mb-2">
                    <i class="fa-solid fa-hourglass me-1"></i>
                    <small>No votes cast yet</small>
                </div>';
        }
        
        $data .= '
                </div>
                
                <div class="card-footer bg-white border-top-0 pt-0">
                    <small class="text-muted voting-stats">
                        <i class="fa-solid fa-calendar-days me-1"></i>' . $row['inquiry_creation_date'] . '
                    </small>
                    <button class="btn btn-outline-primary btn-sm float-end" onclick="event.stopPropagation(); showDetailedVotingResults(' . $inquiry_id . ')">
                        <i class="fa-solid fa-chart-bar"></i> View Details
                    </button>
                </div>
            </div>
        </div>';
    }
    
    $data .= '</div>';
    
} else {
    $data .= '
    <div class="text-center py-5">
        <i class="fa-solid fa-chart-bar fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No Spotlight Reports Found</h5>
        <p class="text-muted">Try adjusting your filters or create some spotlights to generate reports.</p>
    </div>';
}

echo $data;

function getSpotlightWinner($inquiry_id, $dbc) {
    $winner_info = array(
        'has_winner' => false,
        'is_tie' => false,
        'winner_name' => '',
        'winners' => array(),
        'winning_votes' => 0
    );
    
    $query = "
        SELECT 
            sn.assignment_id,
            sn.assignment_user,
            u.first_name,
            u.last_name,
            COUNT(sb.answer_id) as vote_count
        FROM spotlight_nominee sn
        JOIN users u ON sn.assignment_user = u.user
        LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$inquiry_id'
        WHERE sn.question_id = '$inquiry_id'
        GROUP BY sn.assignment_id, sn.assignment_user, u.first_name, u.last_name
        HAVING vote_count > 0
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
            $winners = array_filter($nominees, function($nominee) use ($highest_votes) {
                return $nominee['votes'] == $highest_votes;
            });
            
            $winner_info['has_winner'] = true;
            $winner_info['winning_votes'] = $highest_votes;
            $winner_info['winners'] = array_values($winners);
            
            if (count($winners) == 1) {
                $winner_info['winner_name'] = $winners[0]['name'];
            } else {
                $winner_info['is_tie'] = true;
                $winner_names = array_column($winners, 'name');
                $winner_info['winner_name'] = implode(', ', $winner_names);
            }
        }
    }
    
    return $winner_info;
}

mysqli_close($dbc);
?>