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
$custom_navbar_enabled = isset($_POST['custom_navbar_enabled']) ? (int)$_POST['custom_navbar_enabled'] : 0;
$isToggleOff = ($custom_navbar_enabled == 0 && count($_POST) == 1);

if ($isToggleOff) {
    // Simple disable query
    $query = "UPDATE user_theme SET custom_navbar_enabled = ? WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("ii", $custom_navbar_enabled, $switch_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "success", "message" => "Custom colors disabled"]);
        } else {
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to disable colors"]);
        }
    } else {
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database error"]);
    }
} else {
    // Full save - get all values
    $navbar_bg = isset($_POST['custom_navbar_bg_color']) ? $_POST['custom_navbar_bg_color'] : null;
    $navbar_text = isset($_POST['custom_navbar_text_color']) ? $_POST['custom_navbar_text_color'] : null;
    $brand_icon = isset($_POST['custom_brand_icon_color']) ? $_POST['custom_brand_icon_color'] : null;
    $hover_bg = isset($_POST['custom_hover_bg_color']) ? $_POST['custom_hover_bg_color'] : null;
    $search_bg = isset($_POST['custom_search_bg_color']) ? $_POST['custom_search_bg_color'] : null;
    $search_text = isset($_POST['custom_search_text_color']) ? $_POST['custom_search_text_color'] : null;
    $navbar_border = isset($_POST['custom_navbar_border_color']) ? $_POST['custom_navbar_border_color'] : null;
    $nav_toggle_border = isset($_POST['custom_nav_toggle_border_color']) ? $_POST['custom_nav_toggle_border_color'] : null;
    
    $gradient_enabled = isset($_POST['custom_navbar_gradient_enabled']) ? (int)$_POST['custom_navbar_gradient_enabled'] : 0;
    $gradient_color1 = isset($_POST['custom_navbar_gradient_color1']) ? $_POST['custom_navbar_gradient_color1'] : null;
    $gradient_color2 = isset($_POST['custom_navbar_gradient_color2']) ? $_POST['custom_navbar_gradient_color2'] : null;
    $gradient_angle = isset($_POST['custom_navbar_gradient_angle']) ? (int)$_POST['custom_navbar_gradient_angle'] : 180;
    
    // ONLY validate gradient colors if gradient is actually enabled
    if ($gradient_enabled == 1) {
        if (empty($gradient_color1) || empty($gradient_color2)) {
            outputJson(["status" => "error", "message" => "Gradient colors required when gradient is enabled"]);
        }
        
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $gradient_color1) || 
            !preg_match('/^#[a-fA-F0-9]{6}$/', $gradient_color2)) {
            outputJson(["status" => "error", "message" => "Invalid gradient color format"]);
        }
        
        if ($gradient_angle < 0 || $gradient_angle > 360) {
            $gradient_angle = 180;
        }
    } else {
        // When gradient is disabled, validate format if colors are provided, but don't require them
        if (!empty($gradient_color1) && !preg_match('/^#[a-fA-F0-9]{6}$/', $gradient_color1)) {
            outputJson(["status" => "error", "message" => "Invalid gradient color 1 format"]);
        }
        if (!empty($gradient_color2) && !preg_match('/^#[a-fA-F0-9]{6}$/', $gradient_color2)) {
            outputJson(["status" => "error", "message" => "Invalid gradient color 2 format"]);
        }
        
        // Ensure angle is always valid
        if ($gradient_angle < 0 || $gradient_angle > 360) {
            $gradient_angle = 180;
        }
    }
    
    $query = "UPDATE user_theme SET 
                custom_navbar_enabled = ?,
                custom_navbar_bg_color = ?,
                custom_navbar_text_color = ?,
                custom_navbar_hover_bg_color = ?,
                custom_navbar_brand_color = ?,
                custom_search_bg_color = ?,
                custom_search_text_color = ?,
                custom_navbar_border_color = ?,
                custom_nav_toggle_border_color = ?,
                custom_navbar_gradient_enabled = ?,
                custom_navbar_gradient_color1 = ?,
                custom_navbar_gradient_color2 = ?,
                custom_navbar_gradient_angle = ?
              WHERE user_theme_switch_id = ?";

    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("issssssssisssi", 
            $custom_navbar_enabled,      // i
            $navbar_bg,                  // s
            $navbar_text,                // s
            $hover_bg,                   // s
            $brand_icon,                 // s
            $search_bg,                  // s
            $search_text,                // s
            $navbar_border,              // s
            $nav_toggle_border,          // s
            $gradient_enabled,           // i
            $gradient_color1,            // s - saved even when disabled
            $gradient_color2,            // s - saved even when disabled
            $gradient_angle,             // i - saved even when disabled
            $switch_id                   // i
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update session variables
            $_SESSION['custom_navbar_enabled'] = $custom_navbar_enabled;
            $_SESSION['custom_navbar_bg_color'] = $navbar_bg;
            $_SESSION['custom_navbar_text_color'] = $navbar_text;
            $_SESSION['custom_navbar_hover_bg_color'] = $hover_bg;
            $_SESSION['custom_navbar_brand_color'] = $brand_icon;
            $_SESSION['custom_search_bg_color'] = $search_bg;
            $_SESSION['custom_search_text_color'] = $search_text;
            $_SESSION['custom_navbar_border_color'] = $navbar_border;
            $_SESSION['custom_nav_toggle_border_color'] = $nav_toggle_border;
            $_SESSION['custom_navbar_gradient_enabled'] = $gradient_enabled;
            $_SESSION['custom_navbar_gradient_color1'] = $gradient_color1;
            $_SESSION['custom_navbar_gradient_color2'] = $gradient_color2;
            $_SESSION['custom_navbar_gradient_angle'] = $gradient_angle;
            
            mysqli_close($dbc);
            
            outputJson([
                "status" => "success", 
                "message" => "Colors saved successfully",
                "gradient_enabled" => $gradient_enabled,
                "gradient_color1" => $gradient_color1,
                "gradient_color2" => $gradient_color2,
                "gradient_angle" => $gradient_angle
            ]);
        } else {
            error_log("SQL execution failed: " . $stmt->error);
            $stmt->close();
            mysqli_close($dbc);
            outputJson(["status" => "error", "message" => "Failed to save colors"]);
        }
    } else {
        error_log("SQL prepare failed: " . $dbc->error);
        mysqli_close($dbc);
        outputJson(["status" => "error", "message" => "Database prepare failed"]);
    }
} // <-- THIS WAS THE MISSING CLOSING BRACE!
?>