<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('hd_links_view')) {
    header("Location: ../../index.php?msg1");
    exit;
}

if (isset($_GET["sort_my_link_order"])) {
    $obj = json_decode($_GET['sort_my_link_order']);
    $i = 0;

    foreach ($obj as $item => $ids) {
        $combo_ids = explode(":", $ids);
        $note_id = $combo_ids[0];
        $group_id = $combo_ids[1];
		$query = "UPDATE my_links SET my_link_display_order = ?, my_link_folder_group = ? WHERE my_link_id = ?";
        $stmt = mysqli_prepare($dbc, $query);
		if ($stmt === false) {
            die("MySQL prepare error.");
        }
		mysqli_stmt_bind_param($stmt, 'isi', $i, $group_id, $note_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt)) {
            die("Database error.");
        }
		mysqli_stmt_close($stmt);
        $i++;
    }
	mysqli_close($dbc);
}