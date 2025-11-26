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

// Validate order data
if (empty($_POST['order'])) {
    echo json_encode(['success' => false, 'message' => 'Order data is required']);
    exit();
}

$order_data = json_decode($_POST['order'], true);

if (!is_array($order_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data format']);
    exit();
}

// Update each service's order
$stmt = mysqli_prepare($dbc, "UPDATE lh_services SET service_order = ? WHERE service_id = ?");

mysqli_autocommit($dbc, FALSE); // Start transaction

$success = true;
foreach ($order_data as $item) {
    if (!isset($item['service_id']) || !isset($item['service_order'])) {
        $success = false;
        break;
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $item['service_order'], $item['service_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        $success = false;
        break;
    }
}

if ($success) {
    mysqli_commit($dbc);
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully'
    ]);
} else {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update order'
    ]);
}

mysqli_autocommit($dbc, TRUE); // End transaction
mysqli_stmt_close($stmt);
?>