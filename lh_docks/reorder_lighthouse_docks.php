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

if (empty($_POST['order'])) {
    echo json_encode(['success' => false, 'message' => 'Order data required']);
    exit();
}

$order = json_decode($_POST['order'], true);

if (!is_array($order)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit();
}

// Start transaction
mysqli_begin_transaction($dbc);

try {
    $stmt = mysqli_prepare($dbc, "UPDATE lh_docks SET dock_order = ? WHERE dock_id = ?");
    
    foreach ($order as $item) {
        $order_num = (int)$item['dock_order'];
        $dept_id = (int)$item['dock_id'];
        
        mysqli_stmt_bind_param($stmt, 'ii', $order_num, $dept_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update order');
        }
    }
    
    mysqli_commit($dbc);
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($dbc);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save order: ' . $e->getMessage()
    ]);
}
?>
