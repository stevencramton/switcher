<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !checkRole('spotlight_admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';
$inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
$winner_user = htmlspecialchars($_POST['winner_user'] ?? '');

if (empty($action) || $inquiry_id <= 0 || empty($winner_user)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    switch ($action) {
        case 'regenerate':
            $delete_query = "DELETE FROM spotlight_certificates WHERE inquiry_id = ? AND winner_user = ?";
            $stmt = mysqli_prepare($dbc, $delete_query);
            
            if (!$stmt) {
                // Log the real error for debugging
                error_log("Certificate regenerate - DB preparation failed: " . mysqli_error($dbc));
                throw new Exception('DATABASE_ERROR');
            }
            
            mysqli_stmt_bind_param($stmt, 'is', $inquiry_id, $winner_user);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificate record reset successfully. A new certificate will be generated on next access.'
                ]);
            } else {
                // Log the real error
                error_log("Certificate regenerate - Execution failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception('EXECUTION_ERROR');
            }
            break;
            
        case 'delete':
            $delete_query = "DELETE FROM spotlight_certificates WHERE inquiry_id = ? AND winner_user = ?";
            $stmt = mysqli_prepare($dbc, $delete_query);
            
            if (!$stmt) {
                // Log the real error for debugging
                error_log("Certificate delete - DB preparation failed: " . mysqli_error($dbc));
                throw new Exception('DATABASE_ERROR');
            }
            
            mysqli_stmt_bind_param($stmt, 'is', $inquiry_id, $winner_user);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificate has been permanently revoked. User no longer has access.'
                ]);
            } else {
                // Log the real error
                error_log("Certificate delete - Execution failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                throw new Exception('EXECUTION_ERROR');
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    // Log the actual exception for debugging
    error_log("Certificate action error - Action: $action, Inquiry: $inquiry_id, User: $winner_user, Exception: " . $e->getMessage());
    
    // Map error codes to safe user-friendly messages
    $error_messages = [
        'DATABASE_ERROR' => 'A database error occurred. Please try again later.',
        'EXECUTION_ERROR' => 'Unable to complete the operation. Please try again later.',
    ];
    
    // Return generic message to user
    $user_message = $error_messages[$e->getMessage()] ?? 'An unexpected error occurred. Please try again later.';
    
    echo json_encode(['success' => false, 'message' => $user_message]);
}

mysqli_close($dbc);
?>