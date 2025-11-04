<?php
session_start();

include_once '../../mysqli_connect.php';
include_once '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit();
}

if (!isset($_POST['spotlight_id']) || empty($_POST['spotlight_id'])) {
    echo '<div class="alert alert-danger">Invalid request - no spotlight ID provided.</div>';
    exit();
}

$spotlight_id = mysqli_real_escape_string($dbc, strip_tags($_POST['spotlight_id']));

$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$spotlight_id' AND inquiry_status = 'Closed'";
$result = mysqli_query($dbc, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Spotlight winner not found or spotlight not completed.</div>';
    exit();
}

$spotlight = mysqli_fetch_assoc($result);

$inquiry_name = htmlspecialchars(strip_tags($spotlight['inquiry_name'] ?? ''));
$inquiry_author = htmlspecialchars(strip_tags($spotlight['inquiry_author'] ?? ''));
$inquiry_creation_date = htmlspecialchars(strip_tags($spotlight['inquiry_creation_date'] ?? ''));
$inquiry_closing = htmlspecialchars(strip_tags($spotlight['inquiry_closing'] ?? ''));
$inquiry_image = htmlspecialchars(strip_tags($spotlight['inquiry_image'] ?? ''));
$inquiry_overview = htmlspecialchars(strip_tags($spotlight['inquiry_overview'] ?? ''));
$bullet_one = htmlspecialchars(strip_tags($spotlight['bullet_one'] ?? ''));
$bullet_two = htmlspecialchars(strip_tags($spotlight['bullet_two'] ?? ''));
$bullet_three = htmlspecialchars(strip_tags($spotlight['bullet_three'] ?? ''));

if (empty($inquiry_image)) {
    $inquiry_image = 'media/links/default_spotlight_image.png';
}

$winner_query = "
    SELECT 
        sn.assignment_user,
        u.first_name,
        u.last_name,
        u.profile_pic,
        u.display_title,
        u.display_agency,
        COUNT(sb.answer_id) as vote_count
    FROM spotlight_nominee sn
    JOIN users u ON sn.assignment_user = u.user
    LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$spotlight_id'
    WHERE sn.question_id = '$spotlight_id'
    GROUP BY sn.assignment_user, u.first_name, u.last_name, u.profile_pic, u.display_title, u.display_agency
    HAVING vote_count > 0
    ORDER BY vote_count DESC
    LIMIT 1";

$winner_result = mysqli_query($dbc, $winner_query);
$winner = null;
$total_votes = 0;

if ($winner_result && mysqli_num_rows($winner_result) > 0) {
    $winner = mysqli_fetch_assoc($winner_result);
    
    // Get total votes
    $total_votes_query = "SELECT COUNT(*) as total FROM spotlight_ballot WHERE question_id = '$spotlight_id'";
    $total_votes_result = mysqli_query($dbc, $total_votes_query);
    if ($total_votes_result) {
        $total_votes_row = mysqli_fetch_assoc($total_votes_result);
        $total_votes = $total_votes_row['total'];
    }
}

$all_nominees = array();
$nominees_query = "
    SELECT 
        sn.assignment_user,
        u.first_name,
        u.last_name,
        u.profile_pic,
        u.display_title,
        u.display_agency,
        COUNT(sb.answer_id) as vote_count
    FROM spotlight_nominee sn
    JOIN users u ON sn.assignment_user = u.user
    LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$spotlight_id'
    WHERE sn.question_id = '$spotlight_id'
    GROUP BY sn.assignment_user, u.first_name, u.last_name, u.profile_pic, u.display_title, u.display_agency
    ORDER BY vote_count DESC";

$nominees_result = mysqli_query($dbc, $nominees_query);

if ($nominees_result && mysqli_num_rows($nominees_result) > 0) {
    while ($row = mysqli_fetch_assoc($nominees_result)) {
        $all_nominees[] = $row;
    }
}

echo '<div class="winner-showcase-page">';

echo '<div class="winner-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="h2 fw-bold mb-2">' . $inquiry_name . '</h1>
                <p class="mb-0 opacity-90">Spotlight Winner Recognition</p>
            </div>
            <div class="col-lg-4 text-center">
                <img src="' . $inquiry_image . '" alt="' . $inquiry_name . '" 
                     class="img-fluid rounded" style="max-height: 80px;"
                     onerror="this.src=\'media/links/default_spotlight_image.png\'">
            </div>
        </div>
      </div>';

if ($winner) {
    $winner_name = $winner['first_name'] . ' ' . $winner['last_name'];
    $winner_profile_pic = $winner['profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg';
    $winner_title = $winner['display_title'] ?: 'Team Member';
    $winner_agency = $winner['display_agency'] ?: 'Organization';
    $winner_votes = $winner['vote_count'];
    $winner_percentage = $total_votes > 0 ? round(($winner_votes / $total_votes) * 100, 1) : 0;
    
	echo '<div class="winner-spotlight">
            <div class="winner-crown">
                <i class="fa-solid fa-trophy"></i>
            </div>
            <img src="' . $winner_profile_pic . '" alt="' . $winner_name . '" 
                 class="winner-avatar rounded-circle mb-4"
                 onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
            <h2 class="fw-bold text-success mb-2">' . htmlspecialchars($winner_name) . '</h2>
            <h5 class="text-muted mb-3">' . htmlspecialchars($winner_title) . '</h5>
            <p class="text-muted mb-4">' . htmlspecialchars($winner_agency) . '</p>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="h3 text-primary mb-0">' . $winner_votes . '</div>
                            <small class="text-muted">Votes Received</small>
                        </div>
                        <div class="col-4">
                            <div class="h3 text-success mb-0">' . $winner_percentage . '%</div>
                            <small class="text-muted">of Total Votes</small>
                        </div>
                        <div class="col-4">
                            <div class="h3 text-info mb-0">' . $total_votes . '</div>
                            <small class="text-muted">Total Votes Cast</small>
                        </div>
                    </div>
                </div>
            </div>
          </div>';
    
 		 echo '<div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i>Campaign Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">' . (!empty($inquiry_overview) ? $inquiry_overview : 'Recognition for outstanding performance and dedication.') . '</p>
                        <div class="mb-2">
                            <strong>Campaign Manager:</strong> ' . $inquiry_author . '
                        </div>
                        <div class="mb-2">
                            <strong>Voting Period:</strong> ' . $inquiry_creation_date . ' - ' . $inquiry_closing . '
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-award me-2"></i>Recognition Criteria
                        </h5>
                    </div>
                    <div class="card-body">';
    
    if (!empty($bullet_one) || !empty($bullet_two) || !empty($bullet_three)) {
        echo '<ul class="list-unstyled mb-0">';
        if (!empty($bullet_one)) {
            echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_one . '</li>';
        }
        if (!empty($bullet_two)) {
            echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_two . '</li>';
        }
        if (!empty($bullet_three)) {
            echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_three . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="text-muted mb-0">Recognized for exceptional performance and dedication to excellence.</p>';
    }
    
    echo '</div>
                </div>
            </div>
          </div>';
    
	if (count($all_nominees) > 1) {
        echo '<div class="voting-stats">
                <h4 class="mb-4 text-center">
                    <i class="fa-solid fa-chart-bar me-2"></i>Final Voting Results
                </h4>
                <div class="row">';
        
        foreach ($all_nominees as $index => $nominee) {
            $nominee_name = $nominee['first_name'] . ' ' . $nominee['last_name'];
            $nominee_profile_pic = $nominee['profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg';
            $nominee_votes = $nominee['vote_count'];
            $nominee_percentage = $total_votes > 0 ? round(($nominee_votes / $total_votes) * 100, 1) : 0;
            $rank = $index + 1;
            
            $rank_class = '';
            $rank_icon = '';
            if ($rank == 1) {
                $rank_class = 'border-warning';
                $rank_icon = '<i class="fa-solid fa-trophy text-warning"></i>';
            } elseif ($rank == 2) {
                $rank_class = 'border-secondary';
                $rank_icon = '<i class="fa-solid fa-medal text-secondary"></i>';
            } elseif ($rank == 3) {
                $rank_class = 'border-info';
                $rank_icon = '<i class="fa-solid fa-award text-info"></i>';
            }
            
            echo '<div class="col-md-6 col-lg-4 mb-3">
                    <div class="card ' . $rank_class . '">
                        <div class="card-body text-center">
                            <div class="mb-2">' . $rank_icon . '</div>
                            <img src="' . $nominee_profile_pic . '" 
                                 class="rounded-circle mb-2" width="60" height="60"
                                 onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                            <h6 class="mb-1">' . htmlspecialchars($nominee_name) . '</h6>
                            <div class="fw-bold text-primary">' . $nominee_votes . ' votes</div>
                            <small class="text-muted">' . $nominee_percentage . '%</small>
                        </div>
                    </div>
                  </div>';
        }
        
        echo '  </div>
              </div>';
    }
    
} else {
	echo '<div class="alert alert-info text-center py-4">
            <i class="fa-solid fa-info-circle fa-2x mb-3"></i>
            <h4>No Winner Determined</h4>
            <p class="mb-0">This spotlight campaign was completed but no votes were cast.</p>
          </div>';
}

echo '</div>';

mysqli_close($dbc);
?>