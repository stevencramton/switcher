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

    $inquiry_id = strip_tags($_POST['inquiry_id']);
	$query_assign = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query_assign)) {
        mysqli_stmt_bind_param($stmt, "s", $inquiry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row_assign = mysqli_fetch_assoc($result)) {
            $inquiry_name = strip_tags($row_assign['inquiry_name']);
        }
        mysqli_stmt_close($stmt);
    }

    $spotlight_user_assignment = json_decode(strip_tags($_POST['assignment_user']));

	foreach ($spotlight_user_assignment as $item) {
        
        $check_query = "SELECT * FROM spotlight_assignment WHERE assignment_user = ? AND spotlight_id = ?";
        if ($stmt = mysqli_prepare($dbc, $check_query)) {
            mysqli_stmt_bind_param($stmt, "ss", $item, $inquiry_id);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            $check_row = mysqli_fetch_array($check_result);

            if (mysqli_num_rows($check_result) == 0) {
                $assignment_status = 'Yes'; 

                $query = "INSERT INTO spotlight_assignment (assignment_user, assignment_status, spotlight_id, spotlight_name) VALUES (?, ?, ?, ?)";
                if ($insert_stmt = mysqli_prepare($dbc, $query)) {
                    mysqli_stmt_bind_param($insert_stmt, "ssss", $item, $assignment_status, $inquiry_id, $inquiry_name);
                    $add_spotlight_query = mysqli_stmt_execute($insert_stmt);

                    if(!$add_spotlight_query) {
                        die('Query Failed.');
                    }

                    $response = "success";
                    mysqli_stmt_close($insert_stmt);
                }
            } else {
                $user_arr[] = $item;
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    if(empty($user_arr)){
        echo json_encode($response);
    } else {
        echo json_encode($user_arr);
    }
}
mysqli_close($dbc);