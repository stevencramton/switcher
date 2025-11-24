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

// Validate service ID
if (empty($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit();
}

$service_id = intval($_POST['service_id']);

// Delete service
$query = "DELETE FROM lh_services WHERE service_id = ?";
$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $service_id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Service deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Service not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete service: ' . mysqli_error($dbc)
    ]);
}

mysqli_stmt_close($stmt);
?>