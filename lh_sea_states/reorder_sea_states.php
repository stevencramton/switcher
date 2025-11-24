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

// jQuery sends this as an array already, no need to json_decode
$order = isset($_POST['order']) ? $_POST['order'] : [];

if (empty($order)) {
    echo json_encode(['success' => false, 'message' => 'No order data provided']);
    exit();
}

$success = true;

foreach ($order as $item) {
    // The JavaScript sends 'id' and 'order', not 'sea_state_id' and 'sea_state_order'
    $sea_state_id = intval($item['id']);
    $sea_state_order = intval($item['order']);
    
    $query = "UPDATE lh_sea_states SET sea_state_order = ? WHERE sea_state_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $sea_state_order, $sea_state_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        $success = false;
    }
    mysqli_stmt_close($stmt);
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order']);
}
?>