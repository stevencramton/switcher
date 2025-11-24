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

if (empty($_POST['sea_state_id']) || empty($_POST['sea_state_name']) || empty($_POST['sea_state_color'])) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

$sea_state_id = intval($_POST['sea_state_id']);
$sea_state_name = trim($_POST['sea_state_name']);
$sea_state_description = isset($_POST['sea_state_description']) ? trim($_POST['sea_state_description']) : '';
$sea_state_color = trim($_POST['sea_state_color']);
$sea_state_icon = isset($_POST['sea_state_icon']) ? trim($_POST['sea_state_icon']) : 'fa-solid fa-circle';
$is_active = isset($_POST['is_active']) ? 1 : 0;
$is_closed_resolution = isset($_POST['is_closed_resolution']) ? (int)$_POST['is_closed_resolution'] : 0;

$query = "UPDATE lh_sea_states 
          SET sea_state_name = ?, sea_state_description = ?, sea_state_color = ?, sea_state_icon = ?, is_active = ?, is_closed_resolution = ?
          WHERE sea_state_id = ?";
$stmt = mysqli_prepare($dbc, $query);

mysqli_stmt_bind_param($stmt, 'ssssiii', 
    $sea_state_name,
    $sea_state_description,
    $sea_state_color,
    $sea_state_icon,
    $is_active,
    $is_closed_resolution,
    $sea_state_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Sea state updated successfully'
    ]);
} else {
    error_log('Update sea state error (Sea State ID: ' . $sea_state_id . '): ' . mysqli_error($dbc));
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update sea state. Please try again.'
    ]);
}
mysqli_stmt_close($stmt);
?>
