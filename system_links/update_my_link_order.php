<?php
session_start();
include '../../mysqli_connect.php';

if (isset($_GET["sort_my_link_order"])) {
    $obj = json_decode($_GET['sort_my_link_order']);
    $i = 0;
	$query = "UPDATE my_links SET my_link_display_order = ?, my_link_folder_group = ? WHERE my_link_id = ?";
    if ($stmt = mysqli_prepare($dbc, $query)) {
    	foreach ($obj as $item => $ids) {
            $combo_ids = explode(":", $ids);
            $note_id = $combo_ids[0];
            $group_id = $combo_ids[1];
			mysqli_stmt_bind_param($stmt, 'iis', $i, $group_id, $note_id);
			if (!mysqli_stmt_execute($stmt)) {
                die('Query Failed.');
            }
			$i++;
        }
		mysqli_stmt_close($stmt);
    } else {
        die('Query Prep Failed.');
    }
	mysqli_close($dbc);
}