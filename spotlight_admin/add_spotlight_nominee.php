<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('spotlight_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['inquiry_id'])) { 

    $inquiry_id = $_POST['inquiry_id'];
    
	$query_assign = "SELECT inquiry_name FROM spotlight_inquiry WHERE inquiry_id = ?";
    $stmt = mysqli_prepare($dbc, $query_assign);
    mysqli_stmt_bind_param($stmt, "i", $inquiry_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $inquiry_name);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    $spotlight_user_assignment = json_decode(strip_tags($_POST['assignment_user']));
    
	$response = 'success';
    $user_arr = array();

	foreach ($spotlight_user_assignment as $item) {

    	$check_query = "SELECT 1 FROM spotlight_nominee WHERE assignment_user = ? AND question_id = ?";
        $stmt = mysqli_prepare($dbc, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $item, $inquiry_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) == 0) {
            mysqli_stmt_close($stmt);

            $assignment_status = 'Yes'; 

       	 	$query = "INSERT INTO spotlight_nominee (assignment_user, assignment_status, question_id, spotlight_name) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $query);
            mysqli_stmt_bind_param($stmt, "ssis", $item, $assignment_status, $inquiry_id, $inquiry_name);
            $add_spotlight_query = mysqli_stmt_execute($stmt);

            if (!$add_spotlight_query) {
                die('Query Failed.'); 
            }
        } else {
            $user_arr[] = $item;
        }

        mysqli_stmt_close($stmt);
    }
     
    if (empty($user_arr)) {
        echo json_encode($response);
    } else {
        echo json_encode($user_arr);
    }
}

mysqli_close($dbc);