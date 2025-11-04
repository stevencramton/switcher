<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !checkRole('spotlight_admin')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
$winner_user = mysqli_real_escape_string($dbc, $_POST['winner_user'] ?? '');

if ($inquiry_id <= 0 || empty($winner_user)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

try {
	$spotlight_query = "SELECT award_type, inquiry_name FROM spotlight_inquiry WHERE inquiry_id = ?";
    $stmt = mysqli_prepare($dbc, $spotlight_query);
    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $spotlight = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$spotlight) {
        echo json_encode(['status' => 'error', 'message' => 'Spotlight not found']);
        exit();
    }
    
    if ($spotlight['award_type'] !== 'certificate') {
        echo json_encode(['status' => 'error', 'message' => 'This spotlight is not configured for certificate awards']);
        exit();
    }
    
 	$winner_check_query = "
        SELECT sn.assignment_user, COUNT(sb.ballot_id) as vote_count
        FROM spotlight_nominee sn
        LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id
        WHERE sn.question_id = ? AND sn.assignment_user = ?
        GROUP BY sn.assignment_user
    ";
	
    $stmt = mysqli_prepare($dbc, $winner_check_query);
    mysqli_stmt_bind_param($stmt, 'is', $inquiry_id, $winner_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $winner_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$winner_data) {
        echo json_encode(['status' => 'error', 'message' => 'User is not a nominee in this spotlight']);
        exit();
    }
    
  	$max_votes_query = "
        SELECT MAX(vote_count) as max_votes
        FROM (
            SELECT COUNT(sb.ballot_id) as vote_count
            FROM spotlight_nominee sn
            LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id
            WHERE sn.question_id = ?
            GROUP BY sn.assignment_user
        ) as vote_counts
    ";
	
    $stmt = mysqli_prepare($dbc, $max_votes_query);
    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $max_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($winner_data['vote_count'] < $max_data['max_votes'] || $max_data['max_votes'] == 0) {
        echo json_encode(['status' => 'error', 'message' => 'User is not a winner of this spotlight']);
        exit();
    }
    
	$existing_cert_query = "SELECT certificate_id FROM spotlight_certificates WHERE inquiry_id = ? AND winner_user = ?";
    $stmt = mysqli_prepare($dbc, $existing_cert_query);
    mysqli_stmt_bind_param($stmt, 'is', $inquiry_id, $winner_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_cert = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existing_cert) {
        echo json_encode(['status' => 'error', 'message' => 'Certificate already exists for this user']);
        exit();
    }
    
	$certificate_hash = hash('sha256', $inquiry_id . $winner_user . time() . random_bytes(16));
    
	$insert_cert_query = "INSERT INTO spotlight_certificates (inquiry_id, winner_user, certificate_hash, created_date) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($dbc, $insert_cert_query);
    mysqli_stmt_bind_param($stmt, 'iss', $inquiry_id, $winner_user, $certificate_hash);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        
    	$user = $_SESSION['user'] ?? 'unknown';
        $log_message = "User {$user} generated certificate for {$winner_user} in spotlight {$inquiry_id}";
      	
		echo json_encode([
            'status' => 'success', 
            'message' => 'Certificate generated successfully',
            'certificate_hash' => $certificate_hash
        ]);
	
    } else {
        mysqli_stmt_close($stmt);
        throw new Exception('Failed to create certificate record: ' . mysqli_error($dbc));
    }
    
} catch (Exception $e) {
    error_log('Certificate generation error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred while generating certificate'
    ]);
}
?>