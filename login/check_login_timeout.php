<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (isLoginSessionExpired()) {
    $switch_id = strip_tags($_SESSION['switch_id']);
    $logout_query = "DELETE FROM logged_in WHERE switch_id = ?";
    $stmt = $dbc->prepare($logout_query);
    $stmt->bind_param("s", $switch_id);
    $stmt->execute();
    $stmt->close();

    $token_query = "UPDATE users SET session_token = NULL WHERE switch_id = ?";
    $stmt = $dbc->prepare($token_query);
    $stmt->bind_param("s", $switch_id);
    $stmt->execute();
    $stmt->close();

	if (isset($_COOKIE['remember-me'])) {
	    setcookie('remember-me', '', time() - 3600, "/", "", true, true);
	}
	
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
	   	setcookie(session_name(), '', time() - 42000,
	    	$params["path"], $params["domain"],
	     	$params["secure"], $params["httponly"]
	  	);
	}

    session_unset();
    session_destroy();
	
	$url = "login.php";
	if (isset($_GET["session_expired"])) {
	    $session_expired = htmlspecialchars($_GET["session_expired"], ENT_QUOTES, 'UTF-8');
	    $url .= "?session_expired=" . $session_expired;
	}

    $response = $switch_id;

} else {

    if (isset($_SESSION['user'])) {
    	$role_id = $_SESSION['role_id'];
        generateRoleArrays($role_id);
        setRoleSessionVariables();
        $_SESSION['id'] = $_SESSION['id'];
        $_SESSION['switch_id'] = $_SESSION['switch_id'];
        $_SESSION['first_name'] = $_SESSION['first_name'];
        $_SESSION['last_name'] = $_SESSION['last_name'];
		$_SESSION['user'] = $_SESSION['user'];
		$_SESSION['role_id'] = $_SESSION['role_id'];
      	$_SESSION['unique_id'] = $_SESSION['unique_id'];
		$_SESSION['session_token'] = $_SESSION['session_token'];
        $_SESSION['profile_pic'] = $_SESSION['profile_pic'];
        $_SESSION['display_name'] = $_SESSION['display_name'];
    	$_SESSION['data_theme'] = $_SESSION['data_theme'];
        $_SESSION['theme_bg'] = $_SESSION['theme_bg'];
        $_SESSION['bg_mode'] = $_SESSION['bg_mode'];
        $_SESSION['data_bord'] = $_SESSION['data_bord'];
        $_SESSION['data_bg'] = $_SESSION['data_bg'];
        $_SESSION['data_right'] = $_SESSION['data_right'];
        $_SESSION['data_nav'] = $_SESSION['data_nav'];
		$_SESSION['data_bread'] = $_SESSION['data_bread'];
        $_SESSION['data_body'] = $_SESSION['data_body'];
        $_SESSION['dark_themes'] = $_SESSION['dark_themes'];
     	$_SESSION['dark_mode'] = $_SESSION['dark_mode'];
		$_SESSION['data_opacity'] = $_SESSION['data_opacity'];
		$_SESSION['nav_shadow'] = $_SESSION['nav_shadow'];
  	  	$_SESSION['search'] = $_SESSION['search'];
        $_SESSION['search_select'] = $_SESSION['search_select'];
        $_SESSION['search_email'] = $_SESSION['search_email'];
        $_SESSION['search_notes'] = $_SESSION['search_notes'];
        $_SESSION['search_user_agency'] = $_SESSION['search_user_agency'];
        $_SESSION['search_area_location'] = $_SESSION['search_area_location'];
        $_SESSION['search_department'] = $_SESSION['search_department'];
        $_SESSION['search_pronouns'] = $_SESSION['search_pronouns'];
   	 	$response = "Stay";
		echo json_encode($response);
    }
}
mysqli_close($dbc);