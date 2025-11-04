<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_send')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {

    function awardGEM($userId) {
        global $dbc;
        $gem_title = 'Adoration';
        $gem_description = 'Awarded for discovering the insecure direct object reference.';
        $gem_discovered_by = $userId;
        date_default_timezone_set("America/New_York");
        $gem_discovery_date = date('m-d-Y g:i A');
        $gem_reward = 'Adoration Gem';
        $gem_location = 'messages.php';

        $query = "INSERT INTO gems (gem_title, gem_description, gem_discovered_by, gem_discovery_date, gem_reward, gem_location) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $query);
        if ($stmt === false) {
            die('Error.');
        }

        mysqli_stmt_bind_param($stmt, 'ssssss', $gem_title, $gem_description, $gem_discovered_by, $gem_discovery_date, $gem_reward, $gem_location);
        $execute_result = mysqli_stmt_execute($stmt);
        if ($execute_result === false) {
            die('Error.');
        }

        mysqli_stmt_close($stmt);
    }

    function hasGEM($userId) {
        global $dbc;
        $query = "SELECT * FROM gems WHERE gem_title = 'Adoration' AND gem_discovered_by = ?";
        $stmt = mysqli_prepare($dbc, $query);
        if ($stmt === false) {
            die('Error.');
        }

        mysqli_stmt_bind_param($stmt, 's', $userId);
        $execute_result = mysqli_stmt_execute($stmt);
        if ($execute_result === false) {
            die('Error.');
        }

        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
           die('Error.');
        }

        $hasGEM = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $hasGEM;
    }

    $error = 0;

    if (empty($_POST['recipient'])) {
        $response = "recipient";
        $error = 1;
    }

    if (empty($_POST['subject'])) {
        $response = "subject";
        $error = 1;
    }

    if (empty($_POST['message'])) {
        $response = "message";
        $error = 1;
    }

    if (empty($_POST['priority'])) {
        $response = "priority";
        $error = 1;
    }

    if ($error != 1) {
        $message_profile_pic = $_SESSION['profile_pic'];
        $sender = trim(strip_tags($_POST['sender']));
        $subject = strip_tags($_POST['subject']);
        $recipient = strip_tags($_POST['recipient']);
        $message = strip_tags($_POST['message']);
        $message_force = strip_tags($_POST['message_force']);
        $date = strip_tags($_POST['date']);
        $priority = strip_tags($_POST['priority']);

		$user_query = "SELECT first_name, last_name FROM users WHERE user = ?";
        $stmt = mysqli_prepare($dbc, $user_query);
        if ($stmt === false) {
            die('Error.');
        }

        mysqli_stmt_bind_param($stmt, 's', $recipient);
        $execute_result = mysqli_stmt_execute($stmt);
        if ($execute_result === false) {
            die('Error.');
        }

        mysqli_stmt_bind_result($stmt, $first_name, $last_name);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $full_name = $first_name . " " . $last_name;
        $message_read = 1; 

        if ($sender !== $_SESSION['first_name'] . " " . $_SESSION['last_name']) {
            if (hasGEM($_SESSION['id'])) {
                $response = "gem_already_discovered";
            } else {
                awardGEM($_SESSION['id']);
                $response = "gem_awarded";
            }
        } else {
         	$query = "INSERT INTO messages (message_profile_pic, recipient, full_name, message, message_force, date, sender, subject, message_read, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($dbc, $query);
            if ($stmt === false) {
                die('Error.');
            }

            mysqli_stmt_bind_param($stmt, 'ssssssssis', $message_profile_pic, $recipient, $full_name, $message, $message_force, $date, $sender, $subject, $message_read, $priority);
            $execute_result = mysqli_stmt_execute($stmt);
            if ($execute_result === false) {
                die('Error.');
            }

            mysqli_stmt_close($stmt);

       	 	$query_sent = "INSERT INTO messages_sent (message_profile_pic, recipient, full_name, message, message_force, date, sender, subject, message_read, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_sent = mysqli_prepare($dbc, $query_sent);
            if ($stmt_sent === false) {
                die('Error.');
            }

            mysqli_stmt_bind_param($stmt_sent, 'ssssssssis', $message_profile_pic, $recipient, $full_name, $message, $message_force, $date, $sender, $subject, $message_read, $priority);
            $execute_result_sent = mysqli_stmt_execute($stmt_sent);
            if ($execute_result_sent === false) {
                die('Error.');
            }

            mysqli_stmt_close($stmt_sent);

            $response = "success";
        }

        echo json_encode($response);
    }
}

mysqli_close($dbc);
?>