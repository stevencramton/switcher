<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit();
}

if (!isset($_POST['inquiry_id']) || empty($_POST['inquiry_id'])) {
    echo '<div class="alert alert-danger">Invalid request.</div>';
    exit();
}

$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));

$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
$result = mysqli_query($dbc, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Spotlight not found.</div>';
    exit();
}

$row = mysqli_fetch_assoc($result);

$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? ''));
$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? ''));
$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? ''));
$inquiry_closing = htmlspecialchars(strip_tags($row['inquiry_closing'] ?? ''));
$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? ''));
$inquiry_overview = htmlspecialchars(strip_tags($row['inquiry_overview'] ?? ''));
$nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? 'Team Members'));
$bullet_one = htmlspecialchars(strip_tags($row['bullet_one'] ?? ''));
$bullet_two = htmlspecialchars(strip_tags($row['bullet_two'] ?? ''));
$bullet_three = htmlspecialchars(strip_tags($row['bullet_three'] ?? ''));
$special_preview = htmlspecialchars(strip_tags($row['special_preview'] ?? ''));

if (empty($inquiry_image)) {
    $inquiry_image = 'media/links/default_spotlight_image.png';
}

$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM spotlight_nominee WHERE question_id = '$inquiry_id') as nominee_count,
        (SELECT COUNT(*) FROM spotlight_ballot WHERE question_id = '$inquiry_id') as vote_count,
        (SELECT COUNT(*) FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id') as enrollment_count";

$stats_result = mysqli_query($dbc, $stats_query);
$nominee_count = 0;
$vote_count = 0;
$enrollment_count = 0;
$participation_rate = 0;

if ($stats_result) {
    $stats_row = mysqli_fetch_assoc($stats_result);
    $nominee_count = $stats_row['nominee_count'];
    $vote_count = $stats_row['vote_count'];
    $enrollment_count = $stats_row['enrollment_count'];
    
    if ($enrollment_count > 0) {
        $participation_rate = round(($vote_count / $enrollment_count) * 100, 1);
    }
}

$nominees_query = "
    SELECT 
        sn.assignment_id,
        sn.assignment_user,
        sn.assignment_agency,
        u.first_name,
        u.last_name,
        u.profile_pic,
        u.display_title,
        COUNT(sb.answer_id) as vote_count
    FROM spotlight_nominee sn
    JOIN users u ON sn.assignment_user = u.user
    LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = '$inquiry_id'
    WHERE sn.question_id = '$inquiry_id'
    GROUP BY sn.assignment_id, sn.assignment_user, sn.assignment_agency, u.first_name, u.last_name, u.profile_pic, u.display_title
    ORDER BY vote_count DESC, u.first_name ASC";

$nominees_result = mysqli_query($dbc, $nominees_query);
$nominees = array();
$highest_votes = 0;

if ($nominees_result) {
    while ($nominee_row = mysqli_fetch_assoc($nominees_result)) {
        $votes = $nominee_row['vote_count'];
        $nominees[] = array(
            'name' => $nominee_row['first_name'] . ' ' . $nominee_row['last_name'],
            'user' => $nominee_row['assignment_user'],
            'agency' => $nominee_row['assignment_agency'] ?: 'Unassigned',
            'title' => $nominee_row['display_title'] ?: 'Team Member',
            'profile_pic' => $nominee_row['profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg',
            'votes' => $votes
        );
        
        if ($votes > $highest_votes) {
            $highest_votes = $votes;
        }
    }
}

$status_config = array(
    'Active' => array('class' => 'bg-success', 'icon' => 'fa-circle-play', 'text' => 'Currently Active'),
    'Closed' => array('class' => 'bg-dark', 'icon' => 'fa-circle-check', 'text' => 'Completed'),
    'Paused' => array('class' => 'bg-warning', 'icon' => 'fa-circle-pause', 'text' => 'Currently Paused')
);

$status_info = $status_config[$inquiry_status] ?? array('class' => 'bg-secondary', 'icon' => 'fa-circle-question', 'text' => 'Unknown Status');

echo '<div class="spotlight-details">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <img src="' . $inquiry_image . '" class="img-fluid rounded shadow-sm" 
                     alt="' . $inquiry_name . '" style="max-height: 200px; width: 100%; object-fit: cover;"
                     onerror="this.src=\'media/links/default_spotlight_image.png\'">
            </div>
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h4 class="fw-bold mb-1">' . $inquiry_name . '</h4>
                    <span class="badge ' . $status_info['class'] . ' text-white">
                        <i class="fa-solid ' . $status_info['icon'] . ' me-1"></i>
                        ' . $status_info['text'] . '
                    </span>
                </div>
                
                <div class="mb-3">
                    <div class="text-muted mb-2">
                        <i class="fa-solid fa-user me-1"></i>Created by: <strong>' . $inquiry_author . '</strong>
                    </div>
                    <div class="text-muted mb-2">
                        <i class="fa-solid fa-calendar-plus me-1"></i>Started: <strong>' . $inquiry_creation_date . '</strong>
                    </div>';

if ($inquiry_status == 'Closed' && !empty($inquiry_closing)) {
    echo '          <div class="text-muted mb-2">
                        <i class="fa-solid fa-calendar-check me-1"></i>Completed: <strong>' . $inquiry_closing . '</strong>
                    </div>';
}

echo '          </div>
                
                <div class="row g-3">
                    <div class="col-sm-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h5 fw-bold text-primary mb-0">' . $nominee_count . '</div>
                            <small class="text-muted">Nominees</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h5 fw-bold text-info mb-0">' . $vote_count . '</div>
                            <small class="text-muted">Votes Cast</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h5 fw-bold text-success mb-0">' . $enrollment_count . '</div>
                            <small class="text-muted">Enrolled</small>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="h5 fw-bold text-warning mb-0">' . $participation_rate . '%</div>
                            <small class="text-muted">Participation</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
 	   <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fa-solid fa-info-circle me-2"></i>Campaign Description
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-0">' . (!empty($inquiry_overview) ? $inquiry_overview : 'No description provided.') . '</p>
            </div>
        </div>';

if (!empty($bullet_one) || !empty($bullet_two) || !empty($bullet_three)) {
    echo '  <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-award me-2"></i>Recognition Criteria
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">';
    
    if (!empty($bullet_one)) {
        echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_one . '</li>';
    }
    if (!empty($bullet_two)) {
        echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_two . '</li>';
    }
    if (!empty($bullet_three)) {
        echo '<li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>' . $bullet_three . '</li>';
    }
    
    echo '</ul>
   	 </div>
 	</div>';
}

if (!empty($special_preview)) {
    echo '<div class="alert alert-info">
                <i class="fa-solid fa-bullhorn me-2"></i>
                <strong>Notice:</strong> ' . $special_preview . '
            </div>';
}

if (!empty($nominees)) {
    echo '<div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-users me-2"></i>Nominees & Results (' . count($nominees) . ')
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">';
    
    foreach ($nominees as $index => $nominee) {
        $rank = $index + 1;
        $percentage = $vote_count > 0 ? round(($nominee['votes'] / $vote_count) * 100, 1) : 0;
        $is_leading = ($nominee['votes'] == $highest_votes && $highest_votes > 0);
        
        $card_class = '';
        $badge_class = 'bg-secondary';
        $badge_text = '#' . $rank;
        
        if ($is_leading && $inquiry_status == 'Closed') {
            $card_class = 'border-success';
            $badge_class = 'bg-success';
            $badge_text = 'WINNER';
        } else if ($is_leading && $inquiry_status == 'Active') {
            $card_class = 'border-warning';
            $badge_class = 'bg-warning text-dark';
            $badge_text = 'LEADING';
        }
        
        echo '<div class="col-md-6 col-lg-4">
                    <div class="card h-100 ' . $card_class . '">
                        <div class="card-body text-center position-relative">
                            <span class="position-absolute top-0 end-0 m-2">
                                <span class="badge ' . $badge_class . '">' . $badge_text . '</span>
                            </span>
                            
                            <img src="' . $nominee['profile_pic'] . '" 
                                 class="rounded-circle mb-3" width="80" height="80"
                                 onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                            
                            <h6 class="fw-bold mb-1">' . htmlspecialchars($nominee['name']) . '</h6>
                            <div class="text-muted small mb-2">' . htmlspecialchars($nominee['title']) . '</div>
                            <div class="badge bg-light text-dark mb-3">' . htmlspecialchars($nominee['agency']) . '</div>
                            
                            <div class="mt-auto">
                                <div class="fw-bold text-primary mb-1">' . $nominee['votes'] . ' votes</div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: ' . $percentage . '%"></div>
                                </div>
                                <small class="text-muted">' . $percentage . '% of total votes</small>
                            </div>
                        </div>
                    </div>
                </div>';
    }
    
    echo '</div>
                </div>
            </div>';
} else {
    echo '<div class="alert alert-info">
                <i class="fa-solid fa-users me-2"></i>
                <strong>No nominees yet.</strong> Nominees will appear here once they are added to this spotlight.
            </div>';
}

echo '</div>';

mysqli_close($dbc);