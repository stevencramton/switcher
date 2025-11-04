<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_view')) {
    header("Location:../../index.php?msg1");
	exit();
}

$response = array();

if (isset($_POST['selected_values']) && !empty($_POST['selected_values'])) {
	$ids = strip_tags($_POST['selected_values']);

	$query = "SELECT switchboard_contacts.switchboard_id, switchboard_contacts.switch_id, 
              switchboard_contacts.first_name, switchboard_contacts.last_name, 
              switchboard_contacts.department, switchboard_contacts.extension, 
              switchboard_contacts.cell, switchboard_contacts.phone_code, 
              switchboard_contacts.area_location, switchboard_contacts.area_agency, 
              switchboard_contacts.email, switchboard_contacts.switchboard_note, 
              switchboard_contacts.switchboard_cat_id, 
              users.switch_id, users.display_name, users.pronouns, users.profile_pic,
              user_settings_search.search_cell as cell_visibility
              FROM switchboard_contacts 
              LEFT JOIN users ON switchboard_contacts.switch_id = users.switch_id 
              LEFT JOIN user_settings_search ON switchboard_contacts.switch_id = user_settings_search.user_settings_switch_id
              WHERE switchboard_contacts.switchboard_id = ?";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $ids);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        confirmQuery($result);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
             	$cell_visibility = $row['cell_visibility'] ?? 1;
                
                if ($cell_visibility == 0) {
                    $row['cell'] = '';
                }
                
              	$response = $row;
            }
        } else {
            $response['status'] = 200;
            $response['message'] = "Data not found!";
        }
		mysqli_stmt_close($stmt);
    } else {
        $response['status'] = 500;
        $response['message'] = "Database query failed!";
    }
	echo json_encode($response);
} else {
    $response['status'] = 200;
    $response['message'] = "Invalid Request!";
}
mysqli_close($dbc);