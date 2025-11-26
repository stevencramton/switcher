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

$sea_state_id = isset($_POST['sea_state_id']) ? intval($_POST['sea_state_id']) : 0;

if ($sea_state_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sea state ID']);
    exit();
}

$check_query = "SELECT COUNT(*) as count FROM lh_signals WHERE sea_state_id = ?";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $sea_state_id);
mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);
$check_row = mysqli_fetch_assoc($check_result);

if ($check_row['count'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete this sea state. There are ' . $check_row['count'] . ' signal(s) using it. Please reassign those signals first.'
    ]);
    mysqli_stmt_close($check_stmt);
    exit();
}
mysqli_stmt_close($check_stmt);

$query = "DELETE FROM lh_sea_states WHERE sea_state_id = ?";
$stmt = mysqli_prepare($dbc, $query);

mysqli_stmt_bind_param($stmt, 'i', $sea_state_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Sea state deleted successfully'
    ]);
} else {
    error_log('Delete sea state error (Sea State ID: ' . $sea_state_id . '): ' . mysqli_error($dbc));
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete sea state. Please try again.'
    ]);
}
mysqli_stmt_close($stmt);
?>