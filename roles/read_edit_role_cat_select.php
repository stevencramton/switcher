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

$data = '<select class="form-select" name="editRoleTypeCat" id="editRoleTypeCat">';

$role_cat_query = "SELECT * FROM roles_categories";
if ($stmt = mysqli_prepare($dbc, $role_cat_query)) {
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

    while ($role_cat_row = mysqli_fetch_assoc($result)) {
        $role_cat_id = htmlspecialchars($role_cat_row['role_cat_id']);
        $role_cat_name = htmlspecialchars($role_cat_row['role_cat_name']);
        
        $data .= '<option value="' . $role_cat_id . '">' . $role_cat_name . '</option>';
    }
	mysqli_stmt_close($stmt);
}

$data .= '</select>';

echo $data;
mysqli_close($dbc);