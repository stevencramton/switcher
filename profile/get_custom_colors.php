<?php
session_start();
include '../../mysqli_connect.php';

if (isset($_SESSION['switch_id'])) {
    $switch_id = $_SESSION['switch_id'];
    
    // Updated query to include gradient fields
	$query = "SELECT custom_navbar_enabled, custom_navbar_bg_color, 
                     custom_navbar_text_color, custom_navbar_hover_bg_color,
                     custom_navbar_brand_color, custom_search_bg_color, 
                     custom_search_text_color, custom_navbar_border_color,
                     custom_nav_toggle_border_color, custom_navbar_gradient_enabled,
                     custom_navbar_gradient_color1, custom_navbar_gradient_color2,
                     custom_navbar_gradient_angle
              FROM user_theme WHERE user_theme_switch_id = ?";
    
    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("i", $switch_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $colors = $result->fetch_assoc();
            $stmt->close();
            
            // Enhanced defaults including gradient fields
        	$colors = array_merge([
                'custom_navbar_enabled' => 0,
                'custom_navbar_bg_color' => null,
                'custom_navbar_text_color' => null,
                'custom_navbar_hover_bg_color' => null,
                'custom_navbar_brand_color' => null,
                'custom_search_bg_color' => null,
                'custom_search_text_color' => null,
                'custom_navbar_border_color' => null,
                'custom_nav_toggle_border_color' => null,
                'custom_navbar_gradient_enabled' => 0,
                'custom_navbar_gradient_color1' => null,
                'custom_navbar_gradient_color2' => null,
                'custom_navbar_gradient_angle' => 180
            ], $colors ?: []);
            
            // Ensure gradient angle is within valid range
            if ($colors['custom_navbar_gradient_angle'] < 0 || $colors['custom_navbar_gradient_angle'] > 360) {
                $colors['custom_navbar_gradient_angle'] = 180;
            }
            
            // Add debug info for development
            $colors['debug_info'] = [
                'user_id' => $switch_id,
                'query_executed' => true,
                'gradient_data_available' => !empty($colors['custom_navbar_gradient_color1'])
            ];
            
            echo json_encode($colors);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['error' => 'Execute failed', 'details' => $stmt->error]);
        }
    } else {
        error_log("Prepare failed: " . $dbc->error);
        echo json_encode(['error' => 'Prepare failed', 'details' => $dbc->error]);
    }
} else {
    error_log("No switch_id in session for get_custom_colors");
    echo json_encode(['error' => 'No user session']);
}

mysqli_close($dbc);
?>