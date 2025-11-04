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
    $category_title = isset($_POST['category_title']) ? $_POST['category_title'] : '';
    $category_display_order = isset($_POST['category_display_order']) ? $_POST['category_display_order'] : 0;

    if (empty($category_title)) {
        echo json_encode(['success' => false, 'message' => 'Category title is required']);
        exit;
    }

    $query = "INSERT INTO links_rsb_categories (category_title, category_display_order) VALUES (?, ?)";

    if ($stmt = $dbc->prepare($query)) {
        $stmt->bind_param("si", $category_title, $category_display_order);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Category created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create category']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the database query']);
    }

    $dbc->close();
}
?>