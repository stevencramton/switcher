<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if(isset($_SESSION['id'])) {
	$switch_id = strip_tags($_SESSION['switch_id']);
    $version_view = strip_tags($_POST['version_view'] ?? '');
	$weather_icon = strip_tags($_POST['weather_icon'] ?? '');
	$weather_widget = strip_tags($_POST['weather_widget'] ?? '');
    $profile_view = strip_tags($_POST['profile_view'] ?? '');
    $profile_icon = strip_tags($_POST['profile_icon'] ?? '');
    $left_sidebar_search = strip_tags($_POST['left_sidebar_search'] ?? '');
    $right_sidebar_view = strip_tags($_POST['right_sidebar_view'] ?? '');
    $breadcrumb = strip_tags($_POST['breadcrumb'] ?? '');
    $left_sidebar_pin = strip_tags($_POST['left_sidebar_pin'] ?? '');
    $right_sidebar_pin = strip_tags($_POST['right_sidebar_pin'] ?? '');
    $nav_shadow = strip_tags($_POST['nav_shadow'] ?? '');
    $footer = strip_tags($_POST['footer'] ?? '');
    $athena_agent = strip_tags($_POST['athena_agent'] ?? '');
    $toggle_alerts = strip_tags($_POST['toggle_alerts'] ?? '');
    $quick_view = strip_tags($_POST['quick_view'] ?? '');
	$hr_style_toggle = strip_tags($_POST['hr_style_toggle'] ?? '');

	$queries = [
	    'version_view' => "UPDATE user_settings SET version_on = ? WHERE user_settings_switch_id = ?",
	    'weather_icon' => "UPDATE user_settings SET weather_icon = ? WHERE user_settings_switch_id = ?",
	    'weather_widget' => "UPDATE user_settings SET weather_widget = ? WHERE user_settings_switch_id = ?",
	    'quick_view' => "UPDATE user_settings SET quick_view_links_on = ? WHERE user_settings_switch_id = ?",
	    'left_sidebar_search' => "UPDATE user_settings SET left_sidebar_search_on = ? WHERE user_settings_switch_id = ?",
	    'right_sidebar_view' => "UPDATE user_settings SET right_sidebar_on = ? WHERE user_settings_switch_id = ?",
	    'profile_view' => "UPDATE user_settings SET sidebar_profile = ? WHERE user_settings_switch_id = ?",
	    'profile_icon' => "UPDATE user_settings SET sidebar_icon = ? WHERE user_settings_switch_id = ?",
	    'breadcrumb' => "UPDATE user_settings SET breadcrumbs_on = ? WHERE user_settings_switch_id = ?",
	    'nav_shadow' => "UPDATE user_settings SET nav_shadow_on = ? WHERE user_settings_switch_id = ?",
	    'left_sidebar_pin' => "UPDATE user_settings SET left_sidebar_pin = ? WHERE user_settings_switch_id = ?",
	    'right_sidebar_pin' => "UPDATE user_settings SET right_sidebar_pin = ? WHERE user_settings_switch_id = ?",
	    'footer' => "UPDATE user_settings SET footer_on = ? WHERE user_settings_switch_id = ?",
	    'athena_agent' => "UPDATE user_settings SET athena_agent = ? WHERE user_settings_switch_id = ?",
	    'toggle_alerts' => "UPDATE user_settings SET alerts_on = ? WHERE user_settings_switch_id = ?",
	    'hr_style_toggle' => "UPDATE user_settings SET hr_style_toggle = ? WHERE user_settings_switch_id = ?"
	];

	$settings = [
	    'version_view' => $version_view,
	    'weather_icon' => $weather_icon,
	    'weather_widget' => $weather_widget,
	    'quick_view' => $quick_view,
	    'left_sidebar_search' => $left_sidebar_search,
	    'right_sidebar_view' => $right_sidebar_view,
	    'profile_view' => $profile_view,
	    'profile_icon' => $profile_icon,
	    'breadcrumb' => $breadcrumb,
	    'nav_shadow' => $nav_shadow,
	    'left_sidebar_pin' => $left_sidebar_pin,
	    'right_sidebar_pin' => $right_sidebar_pin,
	    'footer' => $footer,
	    'athena_agent' => $athena_agent,
	    'toggle_alerts' => $toggle_alerts,
	    'hr_style_toggle' => $hr_style_toggle
	];

    $response = "success";

    foreach ($settings as $key => $value) {
        $value = ($value == 1) ? 1 : 0;
        $query = $queries[$key];
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, 'is', $value, $switch_id);
        if (!mysqli_stmt_execute($stmt)) {
            exit();
        }
        mysqli_stmt_close($stmt);
    }
	echo json_encode($response);
}
mysqli_close($dbc);