<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['id'])) {
	$poll_ids = explode(',', $_POST['id']);
    
	function executePreparedStmt($dbc, $query, $ids) {
        if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);

            foreach ($ids as $id) {
                $id = (int)trim($id);
                if (!mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    return false;
                }
            }
			mysqli_stmt_close($stmt);
            return true;
        } else {
            return false;
        }
    }

    $success = true;
	$query = "DELETE FROM poll_inquiry WHERE inquiry_id = ?";
    if (!executePreparedStmt($dbc, $query, $poll_ids)) {
        $success = false;
    }

	$query_two = "DELETE FROM poll_response WHERE question_id = ?";
    if (!executePreparedStmt($dbc, $query_two, $poll_ids)) {
        $success = false;
    }

	$query_three = "DELETE FROM poll_ballot WHERE question_id = ?";
    if (!executePreparedStmt($dbc, $query_three, $poll_ids)) {
        $success = false;
    }

	$query_four = "DELETE FROM poll_assignment WHERE poll_id = ?";
    if (!executePreparedStmt($dbc, $query_four, $poll_ids)) {
        $success = false;
    }

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Records deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting records']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

mysqli_close($dbc);