<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('verse_view')){
 	 header("Location:../../index.php?msg1");
	 exit();
}

?>

<?php

if (isset($_SESSION['id'])) {

    $data = '<div class="accordion accordion-flush product_accordion" id="accordionFramework">';

    if (isset($_GET['verse_group_name']) && $_GET['verse_group_name'] !== "") {
        $verse_group_name = $_GET['verse_group_name'];
        $query = "SELECT * FROM verse_groups WHERE verse_group_id = ? ORDER BY verse_group_display_order ASC";
    } else {
        $query = "SELECT * FROM verse_groups ORDER BY verse_group_display_order ASC";
    }

    if ($stmt = mysqli_prepare($dbc, $query)) {
        if (isset($_GET['verse_group_name']) && $_GET['verse_group_name'] !== "") {
            mysqli_stmt_bind_param($stmt, "s", $verse_group_name);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $number = 1;
            while ($row = mysqli_fetch_assoc($result)) {
				$verse_group_id = htmlspecialchars($row['verse_group_id']);
                $verse_group_name = htmlspecialchars($row['verse_group_name']);

            	$data .= '<div class="accordion-item">
                            <h2 class="accordion-header">
                                <div class="d-flex align-items-center position-relative">
                                    <button type="button" class="text-white accordion-button accord-pad collapsed" data-bs-toggle="collapse" 
                                        data-bs-target="#accord_' . $verse_group_id . '" aria-expanded="false" aria-controls="flush-collapse' . $verse_group_id . '">
                                        <span class="btn btn-light btn-sm me-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Verse Group" style="width:40px;" disabled="">
                                            <i class="fa-solid fa-folder text-secondary"></i>
										</span>
                                        <span class="w-75">
                                            <strong class="dark-gray">' . $verse_group_name . '</strong>
                                        </span>
                                    </button>';

                if (checkRole("verse_group_delete")) {

                    $data .= '<div class="deletegrouphover">
                                        <span class="btn btn-light btn-sm me-2"><i class="fas fa-trash text-hot mt-1" role="button" onclick="deleteVerseGroup(' . $verse_group_id . ');"></i></span>
                                    </div>';
                }
				
                                $data .= '</div>
                            </h2>';
				
				$data .= '<div id="accord_' . $verse_group_id . '" class="accordion-collapse collapse" data-bs-parent="#accordionFramework">
            				<div class="accordion-body p-0">';

              	$query_two = "SELECT * FROM verse_notes WHERE verse_note_group = ? ORDER BY verse_note_display_order ASC";

                if ($stmt2 = mysqli_prepare($dbc, $query_two)) {
                    mysqli_stmt_bind_param($stmt2, "s", $verse_group_id);
                    mysqli_stmt_execute($stmt2);
                    $results = mysqli_stmt_get_result($stmt2);

                    if (mysqli_num_rows($results) > 0) {
                        while ($row = mysqli_fetch_assoc($results)) {

                            $verse_note_id = htmlspecialchars($row['verse_note_id']);
                            $verse_note_title = htmlspecialchars($row['verse_note_title']);
                            $verse_note_info = htmlspecialchars($row['verse_note_info']);
							$verse_note_private = htmlspecialchars($row['verse_note_private'] ?? '');
							$verse_note_tags = htmlspecialchars($row['verse_note_tags'] ?? '');

                            $data .= '<div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action" onclick="showEditVerseNote(' . $verse_note_id . ');">
                                    <span class="mr-2">' . $verse_note_title . '</span>';

                            if (checkRole("verse_delete_view")) {
                                $data .= '<span class="deletenotehover">
                                    <i class="fas fa-times mt-1 float-end" onclick="deleteVerseNote(' . $verse_note_id . ');"></i>
                                </span>';
                            }

                            $data .= '</a></div>';
                        }
                    } else {
                        $data .= '<svg version="1.1" class="svgcheck pt-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
                            <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                            <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
                        </svg>
                        <p class="one success">Empty!</p>
                        <p class="complete mb-4">Verse Notes not found!</p>';
                    }

                    mysqli_stmt_close($stmt2);
                }

                $data .= '</div></div></div>';
                $number++;
            }

            $data .= '</div>';
        } else {
            $data .= '<svg version="1.1" class="svgcheck pt-3" xmlns="http://www.w3.org/2000/svg" viewBox="1 0 130.2 130.2" style="margin: 0px auto 0 !important;">
                <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="one success">Records empty!</p>
            <p class="complete mb-4">Verse Groups not found!</p>';
        }

        echo $data;
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($dbc);
?>