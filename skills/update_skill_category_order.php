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

if(isset($_GET["sort_order"])) {
	$id_ary = explode(",", $_GET["sort_order"]);
	$query = "UPDATE skill_categories SET cat_skill_display_order = ? WHERE cat_skill_id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    if ($stmt === false) {
        die("Statement preparation failed.");
    }

    for($i = 0; $i < count($id_ary); $i++) {
        mysqli_stmt_bind_param($stmt, "ii", $i, $id_ary[$i]);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_errno($stmt)) {
            die("Database error.");
        }
    }
	mysqli_stmt_close($stmt);
}
mysqli_close($dbc);