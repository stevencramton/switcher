<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('documentation_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id']) && isset($_POST['pk'])) {
    $id = mysqli_real_escape_string($dbc, $_POST['pk']);
    $value = mysqli_real_escape_string($dbc, $_POST['value']);

    if (empty($value)) {
        header('HTTP/1.0 400 Bad Request', true, 400);
        echo "Please enter a valid category name";
    } else {
     	$query = "SELECT doc_cat_name FROM docs_categories WHERE doc_cat_id = ?";
        $stmt_fetch = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt_fetch, "i", $id);
        mysqli_stmt_execute($stmt_fetch);
        mysqli_stmt_bind_result($stmt_fetch, $doc_cat_name);
        mysqli_stmt_fetch($stmt_fetch);
        mysqli_stmt_close($stmt_fetch);

      	$query = "UPDATE docs_categories SET doc_cat_name = ? WHERE doc_cat_id = ?";
        $stmt_update = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt_update, "si", $value, $id);
        $result_update = mysqli_stmt_execute($stmt_update);

        if ($result_update) {
           	$audit_user = mysqli_real_escape_string($dbc, $_SESSION['user']);
            $audit_first_name = mysqli_real_escape_string($dbc, $_SESSION['first_name']);
            $audit_last_name = mysqli_real_escape_string($dbc, $_SESSION['last_name']);
            $audit_profile_pic = mysqli_real_escape_string($dbc, $_SESSION['profile_pic']);
            $switch_id = mysqli_real_escape_string($dbc, $_SESSION['switch_id']);
            $audit_date = date('m-d-Y g:i A');
            $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-calendar-alt"></i> Updated Document Category</span>';
            $audit_action = 'Updated Document Category Name';
            $audit_ip = mysqli_real_escape_string($dbc, $_SERVER['REMOTE_ADDR']);
            $audit_source = mysqli_real_escape_string($dbc, $_SERVER['REQUEST_URI']);
            $audit_domain = mysqli_real_escape_string($dbc, $_SERVER['SERVER_NAME']);
            $audit_detailed_action = '<span class="dark-gray fw-bold">From</span>:' . ' ' . htmlspecialchars($doc_cat_name) . '<br>' . '<span class="dark-gray fw-bold">To</span>:' . ' ' . htmlspecialchars($value);

            $summary_value = (strlen($value) > 30) ? substr($value, 0, 30) . '...' : $value;

          	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_audit = mysqli_prepare($dbc, $audit_query);

            mysqli_stmt_bind_param($stmt_audit, "ssssissssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $summary_value, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);

            mysqli_stmt_execute($stmt_audit);
            mysqli_stmt_close($stmt_audit);
            echo "Document Category Successfully Updated";
        } else {
            error_log("Failed to update document category.");
            http_response_code(500);
            echo "Failed to update document category. Please try again later.";
        }

        mysqli_stmt_close($stmt_update);
    }
}

mysqli_close($dbc);
?>
