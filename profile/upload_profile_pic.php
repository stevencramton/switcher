<?php
session_start();
include '../../mysqli_connect.php';

$response = array("status" => "error", "message" => "");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode($response);
    exit();
}

if (isset($_SESSION['user'])) { 
	$user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
    $user_id = mysqli_real_escape_string($dbc, strip_tags($_SESSION['id']));
	$max_profile_pics = 6;
	$target_dir = "../../img/profile_pic/";
	$files = scandir($target_dir);
    $user_pics = array_filter($files, function($file) use ($user) {
        return strpos($file, $user . "_") === 0;
    });
    
    $photo_count = count($user_pics);

	if ($photo_count >= $max_profile_pics) {
        $response["status"] = "error";
        $response["message"] = "Maximum number of profile photos reached. Please delete old ones before uploading new pictures.";
        echo json_encode($response);
        exit();
    }

	$filename = $user . "_" . basename($_FILES["fileToUpload"]["name"]);
    $target_file = $target_dir . $filename;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

	if ($_FILES["fileToUpload"]["error"] != UPLOAD_ERR_OK) {
        $error_code = $_FILES["fileToUpload"]["error"];
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'large',
            UPLOAD_ERR_FORM_SIZE => 'large',
            UPLOAD_ERR_PARTIAL => 'partial',
            UPLOAD_ERR_NO_FILE => 'no_file',
            UPLOAD_ERR_NO_TMP_DIR => 'no_tmp_dir',
            UPLOAD_ERR_CANT_WRITE => 'cant_write',
            UPLOAD_ERR_EXTENSION => 'extension'
        ];
        
        $response["message"] = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'unknown';
        echo json_encode($response);
        exit();
    }

	$check = @getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check === false) {
        $uploadOk = 0;
        $response["message"] = "notimage";
    } else {
        $response["status"] = "success";
    }

	if (file_exists($target_file)) {
        $uploadOk = 0;
        $response["message"] = "exists failure";
    }

	if ($_FILES["fileToUpload"]["size"] > 5242880) {
        $uploadOk = 0;
        $response["message"] = "large";
    }

	if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        $uploadOk = 0;
        $response["message"] = "filetype failure";
    }

	if ($uploadOk == 0) {
        $response["status"] = "error";
    } else {
		if (strpos($filename, '..') !== false) {
       	 	$hidden_dir = "../../img/sojourn/";
            $filename = basename($_FILES["fileToUpload"]["name"]);
            $target_file = $hidden_dir . $filename;

            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
              	$check_query = "SELECT * FROM gems WHERE gem_discovered_by = ? AND gem_title = 'Sojourn' AND gem_status = 'Success'";
                $check_stmt = mysqli_prepare($dbc, $check_query);
				mysqli_stmt_bind_param($check_stmt, "i", $user_id);
				mysqli_stmt_execute($check_stmt);
				mysqli_stmt_store_result($check_stmt);

                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $response["status"] = "error";
                    $response["message"] = "You have already claimed this reward!";
                } else {
                    $gem_title = "Sojourn";
                    $gem_description = "This reward is given for your sorjoun.";
                    $gem_reward = "Sojourn Gem";
                    $gem_location = "profile.php";
                    date_default_timezone_set("America/New_York"); 
                    $gem_discovery_date = date('m-d-Y g:i A'); 
                    $gem_status = "Success";

					$insert_query = "INSERT INTO gems (gem_title, gem_description, gem_discovered_by, gem_discovery_date, gem_reward, gem_location, gem_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = mysqli_prepare($dbc, $insert_query);

					mysqli_stmt_bind_param($insert_stmt, "sssssss", $gem_title, $gem_description, $user_id, $gem_discovery_date, $gem_reward, $gem_location, $gem_status);

					mysqli_stmt_execute($insert_stmt);
                    $error = mysqli_stmt_error($insert_stmt);
                    
                    if ($error) {
                        $response["status"] = "error";
                        $response["message"] = "Database query failed.";
                    } else {
                        $response["status"] = "success";
                        $response["message"] = "Congratulations! You've achieved the Sojourn Gem.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
                mysqli_stmt_close($check_stmt);
            } else {
                $response["status"] = "error";
                $response["message"] = "Error moving file to special directory.";
            }
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $stmt = mysqli_prepare($dbc, "UPDATE users SET profile_pic = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $filePath, $user_id);
                $filePath = "img/profile_pic/" . $filename;
                mysqli_stmt_execute($stmt);
                $error = mysqli_stmt_error($stmt);
                
                if ($error) {
                    $response["status"] = "error";
                    $response["message"] = "Database query failed.";
                } else {
                    $_SESSION['profile_pic'] = $filePath;
                    $response["status"] = "success";
                    $response["message"] = "Profile picture uploaded successfully.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $response["status"] = "error";
                $response["message"] = "Upload error.";
            }
        }
    }
    echo json_encode($response);
}
mysqli_close($dbc);