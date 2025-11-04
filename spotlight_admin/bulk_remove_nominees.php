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

if (!isset($_POST['assignment_ids']) || !is_array($_POST['assignment_ids']) || empty($_POST['assignment_ids'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No nominees selected for removal']);
    exit();
}

$assignment_ids = $_POST['assignment_ids'];

foreach ($assignment_ids as $id) {
    if (!is_numeric($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid assignment ID format']);
        exit();
    }
}

$total_requested = count($assignment_ids);
$removed_count = 0;
$votes_removed_count = 0;
$errors = [];
$removed_names = [];

try {
	mysqli_begin_transaction($dbc);
    
    foreach ($assignment_ids as $assignment_id) {
        $assignment_id = mysqli_real_escape_string($dbc, $assignment_id);
        
    	$get_nominee_query = "SELECT sn.assignment_user, sn.question_id, u.first_name, u.last_name 
                              FROM spotlight_nominee sn 
                              LEFT JOIN users u ON sn.assignment_user = u.user 
                              WHERE sn.assignment_id = ?";
        
        $stmt = mysqli_prepare($dbc, $get_nominee_query);
        if (!$stmt) {
            $errors[] = "Database prepare failed for ID $assignment_id";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $assignment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $assignment_user, $question_id, $first_name, $last_name);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        if (empty($assignment_user)) {
            $errors[] = "Nominee with ID $assignment_id not found";
            continue;
        }
        
     	$vote_count_query = "SELECT COUNT(*) as vote_count FROM spotlight_ballot 
                            WHERE question_id = ? AND answer_id = ?";
        
        $stmt = mysqli_prepare($dbc, $vote_count_query);
        if (!$stmt) {
            $errors[] = "Database prepare failed for vote count check for ID $assignment_id";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $question_id, $assignment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $vote_count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
      	if ($vote_count > 0) {
            $delete_votes_query = "DELETE FROM spotlight_ballot WHERE question_id = ? AND answer_id = ?";
            $stmt = mysqli_prepare($dbc, $delete_votes_query);
            if (!$stmt) {
                $errors[] = "Database prepare failed for vote deletion for ID $assignment_id";
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, "ii", $question_id, $assignment_id);
            $delete_votes_result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if (!$delete_votes_result) {
                $errors[] = "Failed to delete votes for nominee ID $assignment_id";
                continue;
            }
            
            $votes_removed_count += $vote_count;
        }
        
     	$delete_nominee_query = "DELETE FROM spotlight_nominee WHERE assignment_id = ?";
        $stmt = mysqli_prepare($dbc, $delete_nominee_query);
        if (!$stmt) {
            $errors[] = "Database prepare failed for nominee deletion for ID $assignment_id";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $assignment_id);
        $delete_result = mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$delete_result || $affected_rows === 0) {
            $errors[] = "Failed to delete nominee ID $assignment_id";
            continue;
        }
        
     	$removed_count++;
        $nominee_name = trim($first_name . ' ' . $last_name);
        if (empty($nominee_name)) {
            $nominee_name = $assignment_user;
        }
        $removed_names[] = $nominee_name;
    }
    
  	if ($removed_count > 0) {
     	mysqli_commit($dbc);
        
     	$message = "Successfully removed $removed_count nominee" . ($removed_count > 1 ? 's' : '');
        
        if ($votes_removed_count > 0) {
            $message .= " and $votes_removed_count associated vote" . ($votes_removed_count > 1 ? 's' : '');
        }
        
       	if (count($removed_names) <= 3) {
            $message .= ": " . implode(', ', $removed_names);
        } else {
            $message .= " including: " . implode(', ', array_slice($removed_names, 0, 3)) . " and " . (count($removed_names) - 3) . " more";
        }
        
        $response = [
            'status' => 'success',
            'message' => $message,
            'removed_count' => $removed_count,
            'votes_removed' => $votes_removed_count,
            'total_requested' => $total_requested
        ];
        
      	if (!empty($errors)) {
            $response['warnings'] = $errors;
            $response['message'] .= " (with " . count($errors) . " error" . (count($errors) > 1 ? 's' : '') . ")";
        }
        
        echo json_encode($response);
        
    } else {
      	mysqli_rollback($dbc);
        
        $error_message = "Failed to remove any nominees";
        if (!empty($errors)) {
            $error_message .= ": " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $error_message .= " and " . (count($errors) - 3) . " more errors";
            }
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => $error_message,
            'errors' => $errors,
            'removed_count' => 0,
            'total_requested' => $total_requested
        ]);
    }
    
} catch (Exception $e) {
  	mysqli_rollback($dbc);
 	echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred during bulk removal',
        'removed_count' => $removed_count,
        'total_requested' => $total_requested
    ]);
}

mysqli_close($dbc);
?>