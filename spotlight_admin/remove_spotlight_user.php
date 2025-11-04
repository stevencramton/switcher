<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
    header("Location: ../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id']) && isset($_POST['assignment_user'])) {
	$spotlight_id = intval($_POST['inquiry_id']);
    $spotlight_users = explode(',', $_POST['assignment_user']);
    
	if (empty($spotlight_users) || (count($spotlight_users) == 1 && empty($spotlight_users[0]))) {
        echo json_encode(array('status' => 'error', 'message' => 'No users selected for removal.'));
        exit();
    }
    
 	$query_two = "DELETE FROM spotlight_assignment WHERE spotlight_id = ? AND (";
    $placeholders = array_fill(0, count($spotlight_users), 'assignment_user = ?');
    $query_two .= implode(' OR ', $placeholders) . ")";
    
    if ($stmt = mysqli_prepare($dbc, $query_two)) {
     	$types = 'i' . str_repeat('s', count($spotlight_users));
        
     	mysqli_stmt_bind_param($stmt, $types, $spotlight_id, ...$spotlight_users);
        
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            echo json_encode(array(
                'status' => 'success', 
                'message' => $affected_rows . ' user(s) have been removed.'
            ));
        } else {
            $error_message = mysqli_stmt_error($stmt);
            error_log("DELETE query failed: " . $error_message);
            echo json_encode(array('status' => 'error', 'message' => 'Database error: ' . $error_message));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = mysqli_error($dbc);
        error_log("Prepare failed: " . $error_message);
        echo json_encode(array('status' => 'error', 'message' => 'Database preparation error: ' . $error_message));
    }
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request parameters.'));
}

mysqli_close($dbc);
?>