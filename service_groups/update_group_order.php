<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_groups_manage')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST["sort_order"])) {
    $id_ary = explode(",", $_POST["sort_order"]);
    
	$validated_ids = array();
    foreach ($id_ary as $id) {
        $id = trim($id);
        if (ctype_digit($id) || (is_numeric($id) && $id > 0)) {
            $validated_ids[] = (int)$id;
        } else {
         	http_response_code(400);
            die("Invalid group ID: " . htmlspecialchars($id, ENT_QUOTES, 'UTF-8'));
        }
    }
    
    $stmt = mysqli_prepare($dbc, "UPDATE service_groups SET group_display_order = ? WHERE group_id = ?");
    if ($stmt === false) {
        http_response_code(500);
        die("Error preparing the statement.");
    }
    
    for ($i = 0; $i < count($validated_ids); $i++) {
        mysqli_stmt_bind_param($stmt, 'ii', $i, $validated_ids[$i]);
        if (!mysqli_stmt_execute($stmt)) {
          	http_response_code(500);
            die("Error executing query for group ID " . $validated_ids[$i]);
        }
    }
    
    mysqli_stmt_close($stmt);
    
	http_response_code(200);
    echo json_encode(array("status" => "success", "message" => "Order updated successfully"));
}

mysqli_close($dbc);
?>