<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $users = json_decode($_POST['user']);
	$user_arr = [];
    $response = "success";

    foreach($users as $user) {
		$check_query = "SELECT skills FROM users WHERE user = ?";
        if ($check_stmt = mysqli_prepare($dbc, $check_query)) {
            mysqli_stmt_bind_param($check_stmt, 's', $user);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $badges);
            mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            $badge_list = explode(', ', $badges);

            if (!in_array($id, $badge_list)) {
				$update_query = "UPDATE skill_assignments SET skill_assignment_id = CONCAT(skill_assignment_id, ', ?') WHERE user = ?";
                if ($update_stmt = mysqli_prepare($dbc, $update_query)) {
                    mysqli_stmt_bind_param($update_stmt, 'ss', $id, $user);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                } else {
                    $response = "error";
                    break;
                }

				$insert_query = "INSERT INTO skill_assignments (skill_assignment_user) VALUES (?)";
                if ($insert_stmt = mysqli_prepare($dbc, $insert_query)) {
                    $skill_assignment_user = $user;
                    mysqli_stmt_bind_param($insert_stmt, 's', $skill_assignment_user);
                    mysqli_stmt_execute($insert_stmt);
                    mysqli_stmt_close($insert_stmt);
                } else {
                    $response = "error";
                    break;
                }
            } else {
                $user_arr[] = $user;
            }
        } else {
            $response = "error";
            break;
        }
    }

    if (empty($user_arr)) {
        echo json_encode($response);
    } else {
        echo json_encode($user_arr);
    }
}

mysqli_close($dbc);