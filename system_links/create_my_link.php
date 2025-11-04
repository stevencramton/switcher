<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if(isset($_POST['my_link_new_tab'])) {
    $query = "INSERT INTO my_links(my_link_name, my_link_description, my_link_url, my_link_created_by, switch_id, my_link_image, my_link_favorite, my_link_new_tab, my_link_protocol) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
	$stmt = mysqli_stmt_init($dbc);
    if(mysqli_stmt_prepare($stmt, $query)) {
     	mysqli_stmt_bind_param($stmt, "ssssissss", 
            $_POST['my_link_name'],
            $_POST['my_link_description'],
            $_POST['my_link_url'],
            $_SESSION['user'],
            $_SESSION['switch_id'],
            $_POST['my_link_image'],
            $_POST['my_link_favorite'],
            $_POST['my_link_new_tab'],
            $_POST['my_link_protocol']
        );

     	mysqli_stmt_execute($stmt);
		if(mysqli_stmt_affected_rows($stmt) > 0) {
            echo "New record inserted successfully.";
        } else {
            echo "Error: Insertion failed.";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error: Unable to prepare statement.";
    }
}
mysqli_close($dbc);