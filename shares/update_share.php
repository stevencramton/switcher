<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:index.php?msg1");
    exit();
}

if (isset($_POST['pk'])) {
	$id = mysqli_real_escape_string($dbc, strip_tags($_POST['pk']));
    $value = mysqli_real_escape_string($dbc, strip_tags($_POST['value']));
    date_default_timezone_set("America/New_York");
    $share_updated_date = date('m-d-Y');
    $share_updated_time = date('g:i A');
    $first_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['first_name']));
    $last_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['last_name']));
    $share_updated_by = $first_name . " " . $last_name;
	$update_query = "UPDATE shares SET ";
    $params = [];
    $types = "";

    if ($_GET['type'] == "share_drive_name") {
		$existing_query = "SELECT * FROM shares WHERE share_id = ?";
        $stmt = mysqli_prepare($dbc, $existing_query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $existing_result = mysqli_stmt_get_result($stmt);
        $existing_row = mysqli_fetch_array($existing_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $share_drive_name = $existing_row['share_drive_name'];
        $share_server = $existing_row['share_server'];

        if ($share_drive_name == $value) {
            $update_query .= "share_drive_name = ?";
            $params = [$value];
            $types = "s";
        } else {
            $mapping = "//" . $share_server . ".plymouth.edu/" . $value;
            $update_query .= "share_drive_name = ?, share_mapping = ?";
            $params = [$value, $mapping];
            $types = "ss";
        }

    } elseif ($_GET['type'] == "share_ad_name") {
        $update_query .= "share_ad_name = ?";
        $params = [$value];
        $types = "s";

    } elseif ($_GET['type'] == "share_server") {
		$existing_query = "SELECT * FROM shares WHERE share_id = ?";
        $stmt = mysqli_prepare($dbc, $existing_query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $existing_result = mysqli_stmt_get_result($stmt);
        $existing_row = mysqli_fetch_array($existing_result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
		$share_drive_name = $existing_row['share_drive_name'];
        $share_server = $existing_row['share_server'];

        if ($share_server == $value) {
            $update_query .= "share_server = ?";
            $params = [$value];
            $types = "s";
        } else {
            $mapping = "//" . $value . ".plymouth.edu/" . $share_drive_name;
            $update_query .= "share_server = ?, share_mapping = ?";
            $params = [$value, $mapping];
            $types = "ss";
        }

    } elseif ($_GET['type'] == "share_mapping") {
        $update_query .= "share_mapping = ?";
        $params = [$value];
        $types = "s";
    }

    $update_query .= ", share_updated_date = ?, share_updated_time = ?, share_updated_by = ? WHERE share_id = ?";
    $params = array_merge($params, [$share_updated_date, $share_updated_time, $share_updated_by, $id]);
    $types .= "sssi";

    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        echo("Error description.");
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);