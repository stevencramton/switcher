<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
if (isset($_SESSION['switch_id'])) {
	$switch_id = $_SESSION['switch_id'];
	$data_theme = $theme_bg = $data_bg = $data_nav = $data_bord = $data_right = $data_bread = $data_body = $dark_mode = $bg_mode = $dark_themes = $data_opacity = $sidebar_bg_opacity = "";
	
	$sidebar_borders_enabled = $sidebar_border_color = "";
	
    if (isset($_POST['data_theme'])) { $data_theme = strip_tags($_POST['data_theme']); }
    if (isset($_POST['theme_bg'])) { $theme_bg = strip_tags($_POST['theme_bg']); }
    if (isset($_POST['data_bg'])) { $data_bg = strip_tags($_POST['data_bg']); }
    if (isset($_POST['data_nav'])) { $data_nav = strip_tags($_POST['data_nav']); }
    if (isset($_POST['data_bord'])) { $data_bord = strip_tags($_POST['data_bord']); }
    if (isset($_POST['data_right'])) { $data_right = strip_tags($_POST['data_right']); }
    if (isset($_POST['data_bread'])) { $data_bread = strip_tags($_POST['data_bread']); }
    if (isset($_POST['data_body'])) { $data_body = strip_tags($_POST['data_body']); }
    if (isset($_POST['dark_mode'])) { $dark_mode = strip_tags($_POST['dark_mode']); }
    if (isset($_POST['bg_mode'])) { $bg_mode = strip_tags($_POST['bg_mode']); }
    if (isset($_POST['dark_themes'])) { $dark_themes = strip_tags($_POST['dark_themes']); }
    if (isset($_POST['data_opacity'])) { $data_opacity = strip_tags($_POST['data_opacity']); }
    if (isset($_POST['sidebar_bg_opacity'])) { $sidebar_bg_opacity = strip_tags($_POST['sidebar_bg_opacity']); }
	
	if (isset($_POST['sidebar_borders_enabled'])) { $sidebar_borders_enabled = (int)$_POST['sidebar_borders_enabled']; }
	if (isset($_POST['sidebar_border_color'])) { $sidebar_border_color = strip_tags($_POST['sidebar_border_color']); }
    
    
    if ($bg_mode == 1) {
        $bg_mode = 1;
        $_SESSION['bg_mode'] = 1;
    } else {
        $bg_mode = 0;
        $_SESSION['bg_mode'] = 0;
    }
    if ($dark_mode == 1) {
        $dark_mode = 1;
        $_SESSION['dark_mode'] = 1;
    } else {
        $dark_mode = 0;
        $_SESSION['dark_mode'] = 0;
    }
    
	$query = "UPDATE user_theme SET 
	                data_theme = ?, 
	                theme_bg = ?, 
	                bg_mode = ?, 
	                data_bg = ?, 
	                data_nav = ?, 
	                data_bord = ?, 
	                data_right = ?, 
	                data_bread = ?, 
	                data_body = ?, 
	                dark_themes = ?, 
	                data_opacity = ?,
	                sidebar_bg_opacity = ?,
	                sidebar_borders_enabled = ?,
	                sidebar_border_color = ?
	            WHERE user_theme_switch_id = ?";
				if ($stmt = $dbc->prepare($query)) {
				        // UPDATE bind_param to include the new fields (add "is" for int and string):
				        $stmt->bind_param("ssisssssssssisi", 
				            $data_theme,             // s
				            $theme_bg,               // s  
				            $bg_mode,                // i
				            $data_bg,                // s
				            $data_nav,               // s
				            $data_bord,              // s
				            $data_right,             // s
				            $data_bread,             // s
				            $data_body,              // s
				            $dark_themes,            // s
				            $data_opacity,           // s
				            $sidebar_bg_opacity,     // s
				            $sidebar_borders_enabled, // i (NEW)
				            $sidebar_border_color,   // s (NEW)
				            $switch_id);             // i
            
				        $stmt->execute();
				        $stmt->close();
						$_SESSION['data_theme'] = $data_theme;
						        $_SESSION['theme_bg'] = $theme_bg;
						        $_SESSION['data_bg'] = $data_bg;
						        $_SESSION['data_nav'] = $data_nav;
						        $_SESSION['data_bord'] = $data_bord;
						        $_SESSION['data_right'] = $data_right;
						        $_SESSION['data_bread'] = $data_bread;
						        $_SESSION['data_body'] = $data_body;
						        $_SESSION['dark_themes'] = $dark_themes;
						        $_SESSION['data_opacity'] = $data_opacity;
						        $_SESSION['sidebar_bg_opacity'] = $sidebar_bg_opacity;
        
						        // ADD THESE 2 SESSION VARIABLES:
						        $_SESSION['sidebar_borders_enabled'] = $sidebar_borders_enabled;
						        $_SESSION['sidebar_border_color'] = $sidebar_border_color;
        $response = "success";
        echo json_encode($response);
    } else {
        error_log("Database error: " . $dbc->error);
        http_response_code(500);
        die("Database error.");
    }
}
mysqli_close($dbc);
?> 