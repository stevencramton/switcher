<?php
ob_start();
session_start();
require_once '../../vendor/autoload.php';
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

$id_token = mysqli_real_escape_string($dbc, strip_tags($_POST['id_token']));
$CLIENT_ID = "";
$client = new Google_Client(['client_id' => $CLIENT_ID]);
$payload = $client->verifyIdToken($id_token);

if ($payload) {
	$google_email = mysqli_real_escape_string($dbc, strip_tags($payload['email']));
	$email_query = "SELECT switch_id, personal_email FROM users WHERE personal_email = ?";
    $stmt_email = mysqli_prepare($dbc, $email_query);
    mysqli_stmt_bind_param($stmt_email, "s", $google_email);
    mysqli_stmt_execute($stmt_email);
    $email_result = mysqli_stmt_get_result($stmt_email);

	if ($email_result) {
	    $email_count = mysqli_num_rows($email_result);

	    if ($email_count > 0) {
	        $email_row = mysqli_fetch_array($email_result);
	        $switch_id = mysqli_real_escape_string($dbc, strip_tags($email_row['switch_id']));

			$query = "SELECT * FROM users
			    JOIN user_theme ON users.switch_id = user_theme.user_theme_switch_id
			    JOIN user_notify ON users.switch_id = user_notify.user_notify_switch_id
			    WHERE users.switch_id = ?";

	        if ($stmt = mysqli_prepare($dbc, $query)) {
	            
				mysqli_stmt_bind_param($stmt, "s", $switch_id);
				mysqli_stmt_execute($stmt);
				$r = mysqli_stmt_get_result($stmt);
        			
				while($row = mysqli_fetch_array($r, MYSQLI_ASSOC)){
           		 	$count = mysqli_num_rows($r);
          		  	$id = mysqli_real_escape_string($dbc, strip_tags($row['id']));
					$switch_id = mysqli_real_escape_string($dbc, strip_tags($row['switch_id']));
					$hash = mysqli_real_escape_string($dbc, strip_tags($row['password']));
					$first_name = mysqli_real_escape_string($dbc, strip_tags($row['first_name']));
					$last_name = mysqli_real_escape_string($dbc, strip_tags($row['last_name']));
					$user = mysqli_real_escape_string($dbc, strip_tags($row['user']));
					$role_id = mysqli_real_escape_string($dbc, strip_tags($row['role_id']));
					generateRoleArrays($role_id);
					$locked = mysqli_real_escape_string($dbc, strip_tags($row['account_locked']));
					$account_delete = mysqli_real_escape_string($dbc, strip_tags($row['account_delete']));
					$unique_id = mysqli_real_escape_string($dbc, strip_tags($row['unique_id']));
					$ip = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REMOTE_ADDR']));
					$login_user_agent = mysqli_real_escape_string($dbc, strip_tags($_SERVER['HTTP_USER_AGENT']));
					$login_source = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REQUEST_URI']));
					$login_domain = mysqli_real_escape_string($dbc, strip_tags($_SERVER['SERVER_NAME']));
					$session_token = bin2hex(random_bytes(32));
					$profile_pic = mysqli_real_escape_string($dbc, strip_tags($row['profile_pic']));
					$display_name = mysqli_real_escape_string($dbc, strip_tags($row['display_name']));
					$personal_email = mysqli_real_escape_string($dbc, strip_tags($row['personal_email']));
					$microsoft = mysqli_real_escape_string($dbc, strip_tags($row['microsoft_email']));
					$data_theme = mysqli_real_escape_string($dbc, strip_tags($row['data_theme']));
					$data_nav = mysqli_real_escape_string($dbc, strip_tags($row['data_nav']));
					$theme_bg = mysqli_real_escape_string($dbc, strip_tags($row['theme_bg']));
					$bg_mode = mysqli_real_escape_string($dbc, strip_tags($row['bg_mode']));
					$data_bg = mysqli_real_escape_string($dbc, strip_tags($row['data_bg']));
					$data_bord = mysqli_real_escape_string($dbc, strip_tags($row['data_bord']));
					$data_right = mysqli_real_escape_string($dbc, strip_tags($row['data_right']));
					$data_bread = mysqli_real_escape_string($dbc, strip_tags($row['data_bread']));
					$data_body = mysqli_real_escape_string($dbc, strip_tags($row['data_body']));
					$dark_themes = mysqli_real_escape_string($dbc, strip_tags($row['dark_themes']));
					$dark_mode = mysqli_real_escape_string($dbc, strip_tags($row['dark_mode']));
					$data_opacity = mysqli_real_escape_string($dbc, strip_tags($row['data_opacity']));
					$nav_shadow = mysqli_real_escape_string($dbc, strip_tags($row['nav_shadow_on'] ?? '0'));
					$search = 'category';
					$search_select = '';
					$search_email = 1;
					$search_notes = 1;
					$search_user_agency = 1;
					$search_area_location = 1;
					$search_department = 1;
					$search_pronouns = 1;
				}
			}

			if ($count == 1 && $google_email == $personal_email && $account_delete != 1) {
				date_default_timezone_set('America/New_York');
    			$checked = isset($_POST['remember-me']) ? 1 : 0;
    			$timestamp = date('Y-m-d G:i:s');
				$update_query = "UPDATE users SET last_activity = ?, login_ip = ?, login_success = '1', session_token = ? WHERE switch_id = ?";
			    $update_stmt = mysqli_prepare($dbc, $update_query);
				mysqli_stmt_bind_param($update_stmt, "ssss", $timestamp, $ip, $session_token, $switch_id);
				mysqli_stmt_execute($update_stmt);
				mysqli_stmt_close($update_stmt);
				$other_login_query = "INSERT INTO log_ins (
				                        switch_id, profile_pic, user, first_name, 
				                        last_name, login_time, login_user_agent, login_source, 
				                        login_domain, login_ip, successful_login
				                      )
				                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1')";
									  
			    $other_login_stmt = mysqli_prepare($dbc, $other_login_query);

				mysqli_stmt_bind_param($other_login_stmt, "ssssssssss", $switch_id,
									   $profile_pic, $user, $first_name, $last_name, 
									   $timestamp, $login_user_agent, $login_source, 
				                       $login_domain, $ip);

				mysqli_stmt_execute($other_login_stmt);
				mysqli_stmt_close($other_login_stmt);

            	setRoleSessionVariables();
            	$_SESSION['loggedin_time'] = time();
            	$_SESSION['user'] = $user;
            	$_SESSION['switch_id'] = $switch_id;
            	$_SESSION['first_name'] = $first_name;
           	 	$_SESSION['last_name'] = $last_name;
            	$_SESSION['role_id'] = $role_id;
            	$_SESSION['id'] = $id;
            	$_SESSION['profile_pic'] = $profile_pic;
            	$_SESSION['display_name'] = $display_name;
				$_SESSION['data_theme'] = $data_theme;
            	$_SESSION['theme_bg'] = $theme_bg;
				$_SESSION['bg_mode'] = $bg_mode;
            	$_SESSION['data_bord'] = $data_bord;
            	$_SESSION['data_bg'] = $data_bg;
            	$_SESSION['data_right'] = $data_right;
            	$_SESSION['data_nav'] = $data_nav;
				$_SESSION['data_bread'] = $data_bread;
            	$_SESSION['data_body'] = $data_body;
           	 	$_SESSION['dark_themes'] = $dark_themes;
            	$_SESSION['data_opacity'] = $data_opacity;
           	 	$_SESSION['dark_mode'] = $dark_mode;
            	$_SESSION['nav_shadow'] = $nav_shadow;
            	$_SESSION['session_token'] = $session_token;
            	$_SESSION['unique_id'] = $unique_id;
				$_SESSION['search'] = $search;
				$_SESSION['search_select'] = $search_select;
				$_SESSION['search_email'] = $search_email;
				$_SESSION['search_notes'] = $search_notes;
				$_SESSION['search_user_agency'] = $search_user_agency;
				$_SESSION['search_area_location'] = $search_area_location;
				$_SESSION['search_department'] = $search_department;
				$_SESSION['search_pronouns'] = $search_pronouns;
				
				if ($checked == 1){
              		setcookie('remember-me', $checked, time() + 28800, '', '', true, true);
				}
		
			} else {
        
				$update_query = "UPDATE users SET login_success = '0' WHERE user = ?";
				$update_stmt = mysqli_prepare($dbc, $update_query);

				mysqli_stmt_bind_param($update_stmt, "s", $username);
				mysqli_stmt_execute($update_stmt);

				if (mysqli_stmt_affected_rows($update_stmt) > 0) {
				
				} else { }

				mysqli_stmt_close($update_stmt);

				if ($locked == 1) {

          	  		echo '<script>
                	$(window).ready(function() {
          		  		$("#locked-account").fadeIn(1000);
            		});
         	   		</script>
               		<div class="alert alert-login alert-dismissible text-center mb-3 show" id="locked-account" role="alert" style="display:none; border: 1px solid #ffc4e1;">
          	  			<strong>Account locked!</strong> Please see a Supervisor.</strong>
                		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
           			</div>';

        		} else {

        			echo '<script>
            		$(window).ready(function() {
          	  			$("#inco-credentials").fadeIn("3000");
         			});
      	  			</script>
					<div class="alert alert-login alert-dismissible text-center mb-3 show" id="inco-credentials" role="alert" style="display:none; border: 1px solid #ffc4e1;">
        				<strong>Username or Password are incorrect!</strong>
              			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
       				</div>';
		 		}
			}

	 		if (isset($_SESSION["id"])) {
		 
		 		if (!isLoginSessionExpired()) {
					$response = "success";
					echo json_encode($response);
				} else {
					header("Location:login.php?session_expired=1");
				}
			}

		} else {
			$response = "new";
			echo json_encode($response);
		}
		
	} else {
  	  	$response = "new";
		echo json_encode($response);
	}

} else {
	$response = "fail_token";
	echo json_encode($response);
}