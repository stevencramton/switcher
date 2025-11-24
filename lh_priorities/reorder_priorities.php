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

$order = isset($_POST['order']) ? json_decode($_POST['order'], true) : [];

if (empty($order)) {
    echo json_encode(['success' => false, 'message' => 'No order data provided']);
    exit();
}

$success = true;

foreach ($order as $item) {
    $priority_id = intval($item['priority_id']);
    $priority_order = intval($item['priority_order']);
    
    $query = "UPDATE lh_priorities SET priority_order = ? WHERE priority_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $priority_order, $priority_id);
    
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
