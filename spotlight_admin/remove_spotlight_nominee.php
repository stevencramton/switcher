<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

if (!checkRole('spotlight_admin')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

if (!isset($_POST['assignment_id']) || empty($_POST['assignment_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing assignment ID']);
    exit();
}

$assignment_id = mysqli_real_escape_string($dbc, $_POST['assignment_id']);

if (!is_numeric($assignment_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid assignment ID']);
    exit();
}

try {
	$get_nominee_query = "SELECT sn.assignment_user, sn.question_id, u.first_name, u.last_name 
                          FROM spotlight_nominee sn 
                          LEFT JOIN users u ON sn.assignment_user = u.user 
                          WHERE sn.assignment_id = ?";
    
    $stmt = mysqli_prepare($dbc, $get_nominee_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $assignment_user, $question_id, $first_name, $last_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    if (empty($assignment_user)) {
        echo json_encode(['status' => 'error', 'message' => 'Nominee not found']);
        exit();
    }
    
	$ballot_check_query = "SELECT COUNT(*) as vote_count FROM spotlight_ballot 
                          WHERE question_id = ? AND answer_id = ?";
    
    $stmt = mysqli_prepare($dbc, $ballot_check_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $question_id, $assignment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $vote_count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
 	mysqli_begin_transaction($dbc);
    
    if ($vote_count > 0) {
        $delete_votes_query = "DELETE FROM spotlight_ballot WHERE question_id = ? AND answer_id = ?";
        $stmt = mysqli_prepare($dbc, $delete_votes_query);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $question_id, $assignment_id);
        $delete_votes_result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$delete_votes_result) {
            throw new Exception('Failed to delete associated votes');
        }
    }
    
 	$delete_nominee_query = "DELETE FROM spotlight_nominee WHERE assignment_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_nominee_query);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . mysqli_error($dbc));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $assignment_id);
    $delete_result = mysqli_stmt_execute($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$delete_result) {
        throw new Exception('Failed to delete nominee');
    }
    
    if ($affected_rows === 0) {
        throw new Exception('Nominee not found or already deleted');
    }
    
	mysqli_commit($dbc);
    
  	$nominee_name = trim($first_name . ' ' . $last_name);
    if (empty($nominee_name)) {
        $nominee_name = $assignment_user;
    }
    
    $message = "Successfully removed nominee: " . $nominee_name;
    if ($vote_count > 0) {
        $message .= " (and " . $vote_count . " associated vote" . ($vote_count > 1 ? "s" : "") . ")";
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => $message,
        'assignment_id' => $assignment_id,
        'votes_removed' => $vote_count
    ]);
    
} catch (Exception $e) {
 	mysqli_rollback($dbc);
    
    error_log("Error removing spotlight nominee: " . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to remove nominee: ' . $e->getMessage()
    ]);
}

mysqli_close($dbc);
?>