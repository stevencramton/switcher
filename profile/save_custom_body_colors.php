<?php
session_start();

// Clear any output buffer to prevent interference
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Disable all error output
ini_set('display_errors', 0);
error_reporting(0);

// Function to safely output JSON and exit
function outputJson($data) {
    // Clear any accumulated output
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Ensure we're sending only JSON
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    
    // Flush and exit
    if (ob_get_level()) {
        ob_end_flush();
    }
    exit;
}

// Include database connection
include '../../mysqli_connect.php';

// Check database connection
if (!$dbc) {
    error_log("Database connection failed");
    outputJson(["status" => "error", "message" => "Database connection failed"]);
}

// Check session
if (!isset($_SESSION['switch_id'])) {
    error_log("No switch_id in session");
    outputJson(["status" => "error", "message" => "No user session"]);
}

$switch_id = (int)$_SESSION['switch_id'];
$custom_body_enabled = isset($_POST['custom_body_enabled']) ? (int)$_POST['custom_body_enabled'] : 0;
$isToggleOff = ($custom_body_enabled == 0 && count($_POST) == 1);

if ($isToggleOff) {
    // Simple disable query
    $query = "UPDATE user_theme SET custom_body_enabled = ? WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("ii", $custom_body_enabled, $switch_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_body_enabled'] = 0;
            $_SESSION['custom_body_bg_color'] = null;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom body color disabled"]);
        } else {
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to disable body color"]);
        }
    } else {
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
} else {
    // Full save - get all values
    $body_bg = isset($_POST['custom_body_bg_color']) ? $_POST['custom_body_bg_color'] : null;
    
    // Validate hex colors
    $validHexPattern = '/^#[0-9A-Fa-f]{6}$/';
    
    if ($body_bg && !preg_match($validHexPattern, $body_bg)) {
        outputJson(["status" => "error", "message" => "Invalid body background color format"]);
    }
    
    // Update query with body color field
    $query = "UPDATE user_theme SET 
                custom_body_enabled = ?,
                custom_body_bg_color = ?
              WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("isi", 
            $custom_body_enabled,
            $body_bg,
            $switch_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_body_enabled'] = $custom_body_enabled;
            $_SESSION['custom_body_bg_color'] = $body_bg;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom body color saved successfully"]);
        } else {
            error_log("SQL execution failed: " . $stmt->error);
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to save custom body color"]);
        }
    } else {
        error_log("SQL prepare failed: " . $dbc->error);
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
}
?>