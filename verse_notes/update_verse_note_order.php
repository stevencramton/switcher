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

if (isset($_SESSION['id'], $_GET["sort_verse_note_order"])) {
    $obj = json_decode($_GET['sort_verse_note_order'], true);
    $i = 0;

    foreach ($obj as $item => $ids) {
        $combo_ids = explode(":", $ids);
        $note_id = $combo_ids[0];
        $group_id = $combo_ids[1];
		$query = "UPDATE verse_notes SET verse_note_display_order = ?, verse_note_group = ? WHERE verse_note_id = ?";
        $stmt = mysqli_prepare($dbc, $query);
     	mysqli_stmt_bind_param($stmt, 'iis', $i, $group_id, $note_id);
		mysqli_stmt_execute($stmt);

		if (mysqli_stmt_errno($stmt) !== 0) {
            die("Database error.");
        }
		mysqli_stmt_close($stmt);
		$i++;
    }
}
mysqli_close($dbc);