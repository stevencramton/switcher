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
$custom_breadcrumb_enabled = isset($_POST['custom_breadcrumb_enabled']) ? (int)$_POST['custom_breadcrumb_enabled'] : 0;
$isToggleOff = ($custom_breadcrumb_enabled == 0 && count($_POST) == 1);

if ($isToggleOff) {
    // Simple disable query
    $query = "UPDATE user_theme SET custom_breadcrumb_enabled = ? WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("ii", $custom_breadcrumb_enabled, $switch_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_breadcrumb_enabled'] = 0;
            $_SESSION['custom_breadcrumb_bg_color'] = null;
            $_SESSION['custom_breadcrumb_text_color'] = null;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom breadcrumb colors disabled"]);
        } else {
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to disable breadcrumb colors"]);
        }
    } else {
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
} else {
    // Full save - get all values
    $breadcrumb_bg = isset($_POST['custom_breadcrumb_bg_color']) ? $_POST['custom_breadcrumb_bg_color'] : null;
    $breadcrumb_text = isset($_POST['custom_breadcrumb_text_color']) ? $_POST['custom_breadcrumb_text_color'] : null;
    
    // Validate hex colors
    $validHexPattern = '/^#[0-9A-Fa-f]{6}$/';
    
    if ($breadcrumb_bg && !preg_match($validHexPattern, $breadcrumb_bg)) {
        outputJson(["status" => "error", "message" => "Invalid breadcrumb background color format"]);
    }
    if ($breadcrumb_text && !preg_match($validHexPattern, $breadcrumb_text)) {
        outputJson(["status" => "error", "message" => "Invalid breadcrumb text color format"]);
    }
    
    // Update query with breadcrumb color fields
    $query = "UPDATE user_theme SET 
                custom_breadcrumb_enabled = ?,
                custom_breadcrumb_bg_color = ?,
                custom_breadcrumb_text_color = ?
              WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("issi", 
            $custom_breadcrumb_enabled,
            $breadcrumb_bg,
            $breadcrumb_text,
            $switch_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_breadcrumb_enabled'] = $custom_breadcrumb_enabled;
            $_SESSION['custom_breadcrumb_bg_color'] = $breadcrumb_bg;
            $_SESSION['custom_breadcrumb_text_color'] = $breadcrumb_text;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom breadcrumb colors saved successfully"]);
        } else {
            error_log("SQL execution failed: " . $stmt->error);
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to save custom breadcrumb colors"]);
        }
    } else {
        error_log("SQL prepare failed: " . $dbc->error);
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
}
?>