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
$custom_sidebar_enabled = isset($_POST['custom_sidebar_enabled']) ? (int)$_POST['custom_sidebar_enabled'] : 0;
$isToggleOff = ($custom_sidebar_enabled == 0 && count($_POST) == 1);

if ($isToggleOff) {
    // Simple disable query
    $query = "UPDATE user_theme SET custom_sidebar_enabled = ? WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("ii", $custom_sidebar_enabled, $switch_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_sidebar_enabled'] = 0;
            $_SESSION['custom_sidebar_bg_color'] = null;
            $_SESSION['custom_sidebar_text_color'] = null;
            $_SESSION['custom_sidebar_hover_color'] = null;
            $_SESSION['custom_sidebar_accent_color'] = null;
            $_SESSION['custom_sidebar_header_color'] = null;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom sidebar colors disabled"]);
        } else {
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to disable custom sidebar colors"]);
        }
    } else {
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
} else {
    // Full save - get all values
    $sidebar_bg = isset($_POST['custom_sidebar_bg_color']) ? $_POST['custom_sidebar_bg_color'] : null;
    $sidebar_text = isset($_POST['custom_sidebar_text_color']) ? $_POST['custom_sidebar_text_color'] : null;
    $sidebar_hover = isset($_POST['custom_sidebar_hover_color']) ? $_POST['custom_sidebar_hover_color'] : null;
    $sidebar_accent = isset($_POST['custom_sidebar_accent_color']) ? $_POST['custom_sidebar_accent_color'] : null;
    $sidebar_header = isset($_POST['custom_sidebar_header_color']) ? $_POST['custom_sidebar_header_color'] : null;
    
    // Validate hex colors
    $validHexPattern = '/^#[0-9A-Fa-f]{6}$/';
    
    if ($sidebar_bg && !preg_match($validHexPattern, $sidebar_bg)) {
        outputJson(["status" => "error", "message" => "Invalid background color format"]);
    }
    if ($sidebar_text && !preg_match($validHexPattern, $sidebar_text)) {
        outputJson(["status" => "error", "message" => "Invalid text color format"]);
    }
    if ($sidebar_hover && !preg_match($validHexPattern, $sidebar_hover)) {
        outputJson(["status" => "error", "message" => "Invalid hover color format"]);
    }
    if ($sidebar_accent && !preg_match($validHexPattern, $sidebar_accent)) {
        outputJson(["status" => "error", "message" => "Invalid accent color format"]);
    }
    if ($sidebar_header && !preg_match($validHexPattern, $sidebar_header)) {
        outputJson(["status" => "error", "message" => "Invalid header color format"]);
    }
    
    // Update query with all sidebar color fields
    $query = "UPDATE user_theme SET 
                custom_sidebar_enabled = ?,
                custom_sidebar_bg_color = ?,
                custom_sidebar_text_color = ?,
                custom_sidebar_hover_color = ?,
                custom_sidebar_accent_color = ?,
                custom_sidebar_header_color = ?
              WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("isssssi", 
            $custom_sidebar_enabled,
            $sidebar_bg,
            $sidebar_text,
            $sidebar_hover,
            $sidebar_accent,
            $sidebar_header,
            $switch_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_sidebar_enabled'] = $custom_sidebar_enabled;
            $_SESSION['custom_sidebar_bg_color'] = $sidebar_bg;
            $_SESSION['custom_sidebar_text_color'] = $sidebar_text;
            $_SESSION['custom_sidebar_hover_color'] = $sidebar_hover;
            $_SESSION['custom_sidebar_accent_color'] = $sidebar_accent;
            $_SESSION['custom_sidebar_header_color'] = $sidebar_header;
            
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom sidebar colors saved successfully"]);
        } else {
            error_log("SQL execution failed: " . $stmt->error);
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to save custom sidebar colors"]);
        }
    } else {
        error_log("SQL prepare failed: " . $dbc->error);
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare error"]);
    }
}
?>