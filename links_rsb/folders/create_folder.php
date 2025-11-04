<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_links_admin')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $folder_title = isset($_POST['folder_title']) ? $_POST['folder_title'] : '';
    $folder_description = isset($_POST['folder_description']) ? $_POST['folder_description'] : '';
    $folder_icon = isset($_POST['folder_icon']) ? $_POST['folder_icon'] : '';
    $folder_display_order = isset($_POST['folder_display_order']) ? $_POST['folder_display_order'] : 0;

    if (empty($folder_title)) {
        echo json_encode(['success' => false, 'message' => 'Folder title is required']);
        exit;
    }

	$query = "INSERT INTO links_rsb_folders (folder_title, folder_description, folder_icon, folder_display_order)
              VALUES (?, ?, ?, ?)";

	if ($stmt = $dbc->prepare($query)) {
     	$stmt->bind_param("sssi", $folder_title, $folder_description, $folder_icon, $folder_display_order);
		if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Folder created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
        }
 	   	$stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the database query']);
    }
	$dbc->close();
}