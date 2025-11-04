<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
header('Content-Type: application/json');

if (!checkRole('spotlight_admin')){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session not found']);
    exit();
}

if (!isset($_POST['inquiry_id']) || !isset($_POST['winner_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$inquiry_id = (int)$_POST['inquiry_id'];
$winner_user = mysqli_real_escape_string($dbc, $_POST['winner_user']);

try {
	$award_config = getSpotlightAwardConfig($inquiry_id, $dbc);
    $badge_id = $award_config['award_settings']['badge_id'] ?? null;
    
    if (!$badge_id) {
        echo json_encode(['status' => 'error', 'message' => 'No badge configured for this spotlight']);
        exit();
    }
    
 	$check_query = "SELECT spotlight_badge_id FROM spotlight_badges WHERE inquiry_id = ? AND badge_id = ? AND winner_user = ?";
    $stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($stmt, 'iis', $inquiry_id, $badge_id, $winner_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'message' => 'Badge already assigned to this winner']);
        exit();
    }
    mysqli_stmt_close($stmt);
    
  	$assign_result = assignSpotlightBadge($inquiry_id, $badge_id, $winner_user, $dbc);
    
    if ($assign_result['status'] === 'success') {
     	$admin_user = $_SESSION['user'] ?? 'unknown';
     	echo json_encode([
            'status' => 'success', 
            'message' => 'Badge assigned successfully to winner'
        ]);
    } else {
        echo json_encode($assign_result);
    }
    
} catch (Exception $e) {
  	echo json_encode([
        'status' => 'error', 
        'message' => 'An error occurred while assigning the badge'
    ]);
}

exit();
?>