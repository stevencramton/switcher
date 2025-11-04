<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !checkRole('spotlight_admin')) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$certificate_id = (int)($_POST['certificate_id'] ?? 0);

if ($certificate_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid certificate ID']);
    exit();
}

try {
	$cert_query = "SELECT inquiry_id, winner_user FROM spotlight_certificates WHERE certificate_id = ?";
    $stmt = mysqli_prepare($dbc, $cert_query);
    mysqli_stmt_bind_param($stmt, 'i', $certificate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cert_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$cert_data) {
        echo json_encode(['status' => 'error', 'message' => 'Certificate not found']);
        exit();
    }
    
	$delete_query = "DELETE FROM spotlight_certificates WHERE certificate_id = ?";
    $stmt = mysqli_prepare($dbc, $delete_query);
    mysqli_stmt_bind_param($stmt, 'i', $certificate_id);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        
      	$user = $_SESSION['user'] ?? 'unknown';
        $log_message = "User {$user} revoked certificate {$certificate_id} for user {$cert_data['winner_user']} in spotlight {$cert_data['inquiry_id']}";
        error_log($log_message);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Certificate has been revoked successfully'
        ]);
    } else {
        mysqli_stmt_close($stmt);
        throw new Exception('Failed to revoke certificate: ' . mysqli_error($dbc));
    }
    
} catch (Exception $e) {
    error_log('Certificate revocation error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error occurred while revoking certificate'
    ]);
}
?>