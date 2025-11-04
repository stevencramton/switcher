<?php
ob_start();
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('feedback_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['pk'])) {
    $feedback_id = mysqli_real_escape_string($dbc, $_POST['pk']);
    $value = trim($_POST['value']);

    $feedback_message_query = "SELECT feedback_message FROM feedback WHERE feedback_id = ?";
    $stmt_message = mysqli_prepare($dbc, $feedback_message_query);
    mysqli_stmt_bind_param($stmt_message, "i", $feedback_id);
    mysqli_stmt_execute($stmt_message);
    $result_message = mysqli_stmt_get_result($stmt_message);
    $row = mysqli_fetch_array($result_message);
    $feedback_message_original = $row['feedback_message'];

    mysqli_stmt_close($stmt_message);

    if (empty($value)){
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid title";
    } else {
        $query = "UPDATE feedback SET feedback_message = ? WHERE feedback_id = ?";
        $stmt_update = mysqli_prepare($dbc, $query);
		mysqli_stmt_bind_param($stmt_update, "si", $value, $feedback_id);
		$result_update = mysqli_stmt_execute($stmt_update);

        if ($result_update) {
            $feedback_message = $value;
			$audit_user = $_SESSION['user'];
            $audit_first_name = $_SESSION['first_name'];
            $audit_last_name = $_SESSION['last_name'];
            $audit_profile_pic = $_SESSION['profile_pic'];
            $switch_id = $_SESSION['switch_id'];
            date_default_timezone_set("America/New_York");
            $audit_date = date('m-d-Y g:i A');
            $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="far fa-dot-circle"></i> Updated Feedback Message</span>';
            $audit_action = 'Updated Feedback Message ID: ' . $feedback_id;
            $audit_ip = $_SERVER['REMOTE_ADDR'];
            $audit_source = $_SERVER['REQUEST_URI'];
            $audit_domain = $_SERVER['SERVER_NAME'];
            $audit_detailed_action = '<span class="dark-gray fw-bold">From</span>: ' . $feedback_message_original . '<br>' . '<span class="dark-gray fw-bold">To</span>: ' . $value;

            $value_summary = (strlen($value) > 30) ? substr($value, 0, 30).'...' : $value;

            $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_audit = mysqli_prepare($dbc, $audit_query);

            mysqli_stmt_bind_param($stmt_audit, "ssssissssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $value_summary, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);

          	$result_audit = mysqli_stmt_execute($stmt_audit);

         	if (!$result_audit) {
                error_log("Failed to log audit trail.");
            }
			mysqli_stmt_close($stmt_audit);
			echo "Feedback Message Successfully Updated";
        } else {
            error_log("Failed to update feedback message.");
            http_response_code(500);
            echo "Failed to update feedback message. Please try again later.";
        }

        mysqli_stmt_close($stmt_update);
    }
}
mysqli_close($dbc);
?>