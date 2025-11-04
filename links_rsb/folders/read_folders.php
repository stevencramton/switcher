<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!checkRole('system_links_admin')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {

    $data = '<div class="list-group mt-2">
                <div class="accordion accordion-flush" id="accordionFolderList">';

    $query_folders = "SELECT * FROM links_rsb_folders ORDER BY folder_display_order ASC";
    if ($stmt = mysqli_prepare($dbc, $query_folders)) {
        mysqli_stmt_execute($stmt);
        $result_folders = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result_folders) > 0) {
        	$accordion_counter = 1;
            
			while ($row_folder = mysqli_fetch_assoc($result_folders)) {
				$folder_id = htmlspecialchars($row_folder['folder_id']);
                $folder_title = htmlspecialchars($row_folder['folder_title']);
				$collapse_id = 'flush-collapse' . $accordion_counter;

             	$data .= '<div class="accordion-item border shadow-sm mb-1">
                            <h5 class="accordion-header p-2" style="background-color:#dfe7ee;">
                                <span class="fw-bold" role="button" data-bs-toggle="collapse" data-bs-target="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '">
                                    ' . $folder_title . '
                                </span>
                            </h5>
                            <div id="' . $collapse_id . '" class="accordion-collapse collapse" data-bs-parent="#accordionFolderList">
                                <div class="accordion-body p-1">
                                    <div class="list-group nested-sortable weblink-list mt-0"></div>
                                </div>
                            </div>
                        </div>';

                $accordion_counter++;
            }
        } else {
            $data .= '<p>No folders found!</p>';
        }
        mysqli_stmt_close($stmt);
    }

    $data .= '</div></div>';

    echo $data;
}
mysqli_close($dbc);
?>