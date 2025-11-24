<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!checkRole('lighthouse_maritime')){
	http_response_code(403);
	die(json_encode(['success' => false, 'message' => 'Access denied']));
}

$priority_id = isset($_POST['priority_id']) ? intval($_POST['priority_id']) : 0;

if ($priority_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid priority ID']);
    exit();
}

// Check if there are any signals using this priority
$check_query = "SELECT COUNT(*) as count FROM lh_signals WHERE priority_id = ?";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $priority_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$check_row = mysqli_fetch_assoc($check_result);

if ($check_row['count'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete this priority. There are ' . $check_row['count'] . ' signal(s) using it. Please reassign those signals first.'
    ]);
    mysqli_stmt_close($check_stmt);
    exit();
}

mysqli_stmt_close($check_stmt);

// Delete the priority
$query = "DELETE FROM lh_priorities WHERE priority_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $priority_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Priority deleted successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete priority: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>
