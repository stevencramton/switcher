<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['ballot_id'])) {
	$ballot_ids = explode(',', $_POST['ballot_id']);
	$query = "DELETE FROM poll_ballot WHERE ballot_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
 	   	mysqli_stmt_bind_param($stmt, 'i', $ballot_id);
        $success = true;

		foreach ($ballot_ids as $id) {
            $ballot_id = (int)$id;
            
			if (!mysqli_stmt_execute($stmt)) {
                $success = false;
                break;
            }
        }

		mysqli_stmt_close($stmt);

		if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Ballot votes deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error deleting ballot votes']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}

mysqli_close($dbc);
?>