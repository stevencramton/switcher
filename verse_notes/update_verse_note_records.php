<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_edit_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'], $_POST['verse_note_id'], $_POST['verse_note_title'], $_POST['verse_note_info'], $_POST['verse_note_group'])) {
	$verse_note_id = strip_tags($_POST['verse_note_id']);
    $verse_note_title = strip_tags($_POST['verse_note_title']);
    $verse_note_info = strip_tags($_POST['verse_note_info']);
    $verse_note_group = strip_tags($_POST['verse_note_group']);

	$old_verse_note_query = "SELECT * FROM verse_notes WHERE verse_note_id = ?";
    $stmt = mysqli_prepare($dbc, $old_verse_note_query);
    mysqli_stmt_bind_param($stmt, 's', $verse_note_id);
    mysqli_stmt_execute($stmt);
    $old_verse_note_result = mysqli_stmt_get_result($stmt);
    $old_verse_note_row = mysqli_fetch_array($old_verse_note_result);
    mysqli_stmt_close($stmt);

    $old_verse_note_title = strip_tags($old_verse_note_row['verse_note_title']);
    $old_verse_note_info = strip_tags($old_verse_note_row['verse_note_info']);
    $old_verse_note_group = strip_tags($old_verse_note_row['verse_note_group']);

	$old_verse_group_query = "SELECT * FROM verse_groups WHERE verse_group_id = ?";
    $stmt = mysqli_prepare($dbc, $old_verse_group_query);
    mysqli_stmt_bind_param($stmt, 's', $old_verse_note_group);
    mysqli_stmt_execute($stmt);
    $old_verse_group_result = mysqli_stmt_get_result($stmt);
    $old_verse_group_row = mysqli_fetch_array($old_verse_group_result);
    mysqli_stmt_close($stmt);

    $old_verse_group_name = strip_tags($old_verse_group_row['verse_group_name']);

	$query = "UPDATE verse_notes SET verse_note_title = ?, verse_note_info = ?, verse_note_group = ? WHERE verse_note_id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssss', $verse_note_title, $verse_note_info, $verse_note_group, $verse_note_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

	$response = $result ? "success" : "failure";
    echo json_encode($response);

    if ($result) {
 	   	$new_verse_note_query = "SELECT * FROM verse_notes WHERE verse_note_id = ?";
        $stmt = mysqli_prepare($dbc, $new_verse_note_query);
        mysqli_stmt_bind_param($stmt, 's', $verse_note_id);
        mysqli_stmt_execute($stmt);
        $new_verse_note_result = mysqli_stmt_get_result($stmt);
        $new_verse_note_row = mysqli_fetch_array($new_verse_note_result);
        mysqli_stmt_close($stmt);

        $new_verse_note_title = strip_tags($new_verse_note_row['verse_note_title']);
        $new_verse_note_info = strip_tags($new_verse_note_row['verse_note_info']);
        $new_verse_note_group = strip_tags($new_verse_note_row['verse_note_group']);

     	$new_verse_group_query = "SELECT * FROM verse_groups WHERE verse_group_id = ?";
        $stmt = mysqli_prepare($dbc, $new_verse_group_query);
        mysqli_stmt_bind_param($stmt, 's', $new_verse_note_group);
        mysqli_stmt_execute($stmt);
        $new_verse_group_result = mysqli_stmt_get_result($stmt);
        $new_verse_group_row = mysqli_fetch_array($new_verse_group_result);
        mysqli_stmt_close($stmt);

        $new_verse_group_name = strip_tags($new_verse_group_row['verse_group_name']);

      	$audit_user = strip_tags($_SESSION['user']);
        $audit_first_name = strip_tags($_SESSION['first_name']);
        $audit_last_name = strip_tags($_SESSION['last_name']);
        $audit_profile_pic = strip_tags($_SESSION['profile_pic']);
        $switch_id = strip_tags($_SESSION['switch_id']);
        date_default_timezone_set("America/New_York");
        $audit_date = date('m-d-Y g:i A');
        $audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fas fa-feather-alt"></i> Updated Verse Note</span>';
        $audit_action = 'Updated Verse Note';
        $audit_ip = strip_tags($_SERVER['REMOTE_ADDR']);
        $audit_source = strip_tags($_SERVER['REQUEST_URI']);
        $audit_domain = strip_tags($_SERVER['SERVER_NAME']);

      	if (strcmp($old_verse_group_name, $new_verse_group_name) == 0) {
            $verse_note_group_name = '';
        } else {
            $verse_note_group_name = '<span class="dark-gray fw-bold">Updated Verse Note Group</span>: ' . $old_verse_group_name . ' <span class="dark-gray fw-bold">to</span>: ' . $new_verse_group_name . '<br>';
        }

      	if (strcmp($old_verse_note_title, $new_verse_note_title) == 0) {
            $verse_note_title_change = '';
        } else {
            $verse_note_title_change = '<span class="dark-gray fw-bold">Updated Verse Note Title</span>: ' . $old_verse_note_title . ' <span class="dark-gray fw-bold">to</span>: ' . $new_verse_note_title . '<br>';
        }

      	if (strcmp($old_verse_note_info, $new_verse_note_info) == 0) {
            $verse_note_info_change = '';
        } else {
            $verse_note_info_change = '<span class="dark-gray fw-bold">Updated Verse Note Info</span>: ' . $old_verse_note_info . ' <span class="dark-gray fw-bold">to</span>: ' . $new_verse_note_info . '<br>';
        }

        $audit_detailed_action = $verse_note_group_name . $verse_note_title_change . $verse_note_info_change;

        if (empty($audit_detailed_action)) {
            $audit_detailed_action = 'No modifications were made.';
        } else {
            $audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);
        }

        $new_verse_note_title = (strlen($new_verse_note_title) > 30) ? substr($new_verse_note_title, 0, 30) . '...' : $new_verse_note_title;

        $audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'sssssssssssss', $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $new_verse_note_title, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
        $audit_result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        confirmQuery($audit_result);
    }
}
mysqli_close($dbc);