<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!isset($_SESSION['user'])) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!checkRole('user_role')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['role_id'])) {
	$selected_db_names = explode(",", $_POST['role_db_names_checked']);
    $unselected_db_names = explode(",", $_POST['role_db_names_unchecked']);

    $role_id = strip_tags($_POST['role_id']);
    $role_name = strip_tags($_POST['role_name']);
    $role_icon = strip_tags($_POST['role_icon']);
    $role_description = strip_tags($_POST['role_description']);

    $checked_col_count = count($selected_db_names);
    $checked_cols = "";
    $i = 0;

    foreach ($selected_db_names as $key => $db_name) {
        $i++;
        if ($i < $checked_col_count) {
            $checked_cols .= "`" . mysqli_real_escape_string($dbc, $db_name) . "` = 1, ";
        } else if ($i == $checked_col_count) {
            $checked_cols .= "`" . mysqli_real_escape_string($dbc, $db_name) . "` = 1 ";
            break;
        }
    }

    if ($_POST['role_db_names_unchecked'] !== "") {
        $unselected_db_names = explode(",", $_POST['role_db_names_unchecked']);
        $unchecked_col_count = count($unselected_db_names);
        $a = 0;
        $unchecked_cols = "";

        foreach ($unselected_db_names as $key => $db_name) {
            $a++;
            if ($a < $unchecked_col_count) {
                $unchecked_cols .= "`" . mysqli_real_escape_string($dbc, $db_name) . "` = 0, ";
            } else if ($a == $unchecked_col_count) {
                $unchecked_cols .= "`" . mysqli_real_escape_string($dbc, $db_name) . "` = 0 ";
                break;
            }
        }
    } else {
        $unselected_db_names = "";
        $unchecked_col_count = 0;
        $unchecked_cols = "";
    }

 	$query = "UPDATE roles_dev SET role_name = ?, role_icon = ?, role_description = ?, " . $checked_cols . ($unchecked_cols ? ", " . $unchecked_cols : "") . " WHERE role_id = ?";

    if ($stmt = mysqli_prepare($dbc, $query)) {
    	mysqli_stmt_bind_param($stmt, 'sssi', $role_name, $role_icon, $role_description, $role_id);
    	if (!mysqli_stmt_execute($stmt)) {
            echo "Error description.";
        }
		mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
	if ($_SESSION['role_id'] == $role_id) {
        generateRoleArrays($role_id);
        setRoleSessionVariables();
    }
 mysqli_close($dbc);
 
}