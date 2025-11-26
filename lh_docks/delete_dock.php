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

if (empty($_POST['dock_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dock ID required']);
    exit();
}

$dock_id = (int)$_POST['dock_id'];

$check_query = "SELECT COUNT(*) as count FROM lh_signals WHERE dock_id = ? AND is_deleted = 0";
$check_stmt = mysqli_prepare($dbc, $check_query);
mysqli_stmt_bind_param($check_stmt, 'i', $dock_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$check_row = mysqli_fetch_assoc($check_result);

if ($check_row['count'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot delete dock with existing waves. Please reassign lh_signals first.'
    ]);
    exit();
}

$query = "DELETE FROM lh_docks WHERE dock_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $dock_id);

if (mysqli_stmt_execute($stmt)) {
	$reorder_query = "SET @order = 0; 
                      UPDATE lh_docks 
                      SET dock_order = (@order := @order + 1) 
                      ORDER BY dock_order ASC";
    mysqli_multi_query($dbc, $reorder_query);
    
	while (mysqli_next_result($dbc)) {
        if ($result = mysqli_store_result($dbc)) {
            mysqli_free_result($result);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Dock deleted successfully'
    ]);
} else {
	error_log('Failed to delete dock (ID: ' . $dock_id . '): ' . mysqli_error($dbc));
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete dock. Please try again or contact support.'
    ]);
}

mysqli_stmt_close($stmt);
?>