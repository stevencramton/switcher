<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

if (isset($_POST['id'])) {
    $userId = $_SESSION['switch_id'];
    $passkeyId = (int) $_POST['id'];

    $query = "SELECT switch_key FROM users_passkey WHERE id = ? AND switch_key = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, "ii", $passkeyId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $deleteQuery = "DELETE FROM users_passkey WHERE id = ? AND switch_key = ?";
            if ($deleteStmt = mysqli_prepare($dbc, $deleteQuery)) {
                mysqli_stmt_bind_param($deleteStmt, "ii", $passkeyId, $userId);
                if (mysqli_stmt_execute($deleteStmt)) {
                    echo json_encode(["status" => "success", "message" => "Passkey deleted successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to delete passkey."]);
                }
                mysqli_stmt_close($deleteStmt);
            } else {
                echo json_encode(["status" => "error", "message" => "Error preparing delete statement."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "You do not have permission to delete this passkey."]);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(["status" => "error", "message" => "Error checking passkey ownership."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Passkey ID is missing."]);
}

mysqli_close($dbc);
?>