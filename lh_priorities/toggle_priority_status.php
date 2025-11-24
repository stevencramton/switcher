<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['success' => false, 'message' => 'Access denied']));
}

$priority_id = isset($_POST['priority_id']) ? intval($_POST['priority_id']) : 0;
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

if ($priority_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid priority ID']);
    exit();
}

$query = "UPDATE lh_priorities SET is_active = ? WHERE priority_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'ii', $is_active, $priority_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
} else {
	error_log('Failed to toggle priority status (ID: ' . $priority_id . '): ' . mysqli_error($dbc));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status. Please try again or contact support.'
    ]);
}

mysqli_stmt_close($stmt);
?>
