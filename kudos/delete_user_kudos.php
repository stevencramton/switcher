<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('kudos_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['kudos_id'])) {
	$kudos_ids = array_map('intval', explode(',', $_POST['kudos_id']));
	$placeholders = implode(',', array_fill(0, count($kudos_ids), '?'));
	$query = "DELETE FROM kudos WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt) {
		$types = str_repeat('i', count($kudos_ids));
		mysqli_stmt_bind_param($stmt, $types, ...$kudos_ids);
		mysqli_stmt_execute($stmt);

		$affected_rows = mysqli_stmt_affected_rows($stmt);
        if ($affected_rows > 0) {
            echo json_encode(array(
                'status' => 'success',
                'message' => "Deleted $affected_rows records.",
                'kudos_ids' => $_POST['kudos_id']
            ));
        } else {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'No records deleted.',
                'kudos_ids' => $_POST['kudos_id']
            ));
        }
		mysqli_stmt_close($stmt);
    } else {
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Failed to prepare statement.'
        ));
    }
} else {
    echo json_encode(array(
        'status' => 'error',
        'message' => "'kudos_id' is not set."
    ));
}
mysqli_close($dbc);