<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!checkRole('system_links_admin')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {
	$data = '<div class="list-group nested-sortable" id="category-list">';
	$query_categories = "SELECT * FROM links_rsb_categories ORDER BY category_display_order ASC";
    
	if ($stmt = mysqli_prepare($dbc, $query_categories)) {
        mysqli_stmt_execute($stmt);
        $result_categories = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result_categories) > 0) {
            while ($row_category = mysqli_fetch_assoc($result_categories)) {
				$category_id = htmlspecialchars($row_category['category_id']);
                $category_title = htmlspecialchars($row_category['category_title']);

              	$data .= '<div class="category p-1 border border-white mb-1" style="background-color:#c5d0da;">
                    <span class="js-handle-category px-1 fw-bold">' . $category_title . '</span>';

             	$query_folders = "SELECT * FROM links_rsb_folders WHERE category_id = ? ORDER BY folder_display_order ASC";
                if ($stmt2 = mysqli_prepare($dbc, $query_folders)) {
                    mysqli_stmt_bind_param($stmt2, "i", $category_id);
                    mysqli_stmt_execute($stmt2);
                    $result_folders = mysqli_stmt_get_result($stmt2);

                    if (mysqli_num_rows($result_folders) > 0) {
                        $data .= '<div class="list-group mt-2">
                            <div class="accordion accordion-flush" id="accordionFolder' . $category_id . '">';

                        while ($row_folder = mysqli_fetch_assoc($result_folders)) {
                            $folder_id = htmlspecialchars($row_folder['folder_id']);
                            $folder_title = htmlspecialchars($row_folder['folder_title']);

                            $data .= '<div class="accordion-item border shadow-sm mb-1">
                                <h5 class="accordion-header p-2" style="background-color:#dfe7ee;">
                                    <span class="fw-bold" role="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse' . $folder_id . '" aria-expanded="false" aria-controls="flush-collapse' . $folder_id . '">
                                        ' . $folder_title . '
                                    </span>
                                </h5>
                                <div id="flush-collapse' . $folder_id . '" class="accordion-collapse collapse show" data-bs-parent="#accordionFolder' . $category_id . '">
                                    <div class="accordion-body p-1">
                                        <div class="list-group nested-sortable weblink-list mt-0">';

                            $query_weblinks_folder = "SELECT * FROM links_rsb_weblinks WHERE folder_id = ? ORDER BY link_display_order ASC";
                            if ($stmt3 = mysqli_prepare($dbc, $query_weblinks_folder)) {
                                mysqli_stmt_bind_param($stmt3, "i", $folder_id);
                                mysqli_stmt_execute($stmt3);
                                $result_weblinks_folder = mysqli_stmt_get_result($stmt3);

                                if (mysqli_num_rows($result_weblinks_folder) > 0) {
                                    while ($row_weblink = mysqli_fetch_assoc($result_weblinks_folder)) {
                                        $link_full_name = htmlspecialchars($row_weblink['link_full_name']);

                                        $data .= '<div class="weblink bg-light border border-white p-1 mb-1" draggable="false">
                                            <span class="fw-bold">' . $link_full_name . '</span>
                                        </div>';
                                    }
                                }
                                mysqli_stmt_close($stmt3);
                            }

                            $data .= '</div>
                                    </div>
                                </div>
                            </div>';
                        }

                        $data .= '</div></div>';
                    }
                    mysqli_stmt_close($stmt2);
                }

                $query_weblinks_category = "SELECT * FROM links_rsb_weblinks WHERE category_id = ? AND folder_id IS NULL ORDER BY link_display_order ASC";
                if ($stmt4 = mysqli_prepare($dbc, $query_weblinks_category)) {
                    mysqli_stmt_bind_param($stmt4, "i", $category_id);
                    mysqli_stmt_execute($stmt4);
                    $result_weblinks_category = mysqli_stmt_get_result($stmt4);

                    if (mysqli_num_rows($result_weblinks_category) > 0) {
                        $data .= '<div class="list-group nested-sortable folder-list mt-2">
                            <div class="weblink bg-light border border-white p-1 mb-1" draggable="false">
                                <span class="fw-bold ms-1">Weblinks in ' . $category_title . '</span>
                            </div>';

                        while ($row_weblink = mysqli_fetch_assoc($result_weblinks_category)) {
                            $link_full_name = htmlspecialchars($row_weblink['link_full_name']);
                            $data .= '<div class="weblink bg-light border border-white p-1 mb-1" draggable="false">
                                <span class="fw-bold ms-1">' . $link_full_name . '</span>
                            </div>';
                        }

                        $data .= '</div>';
                    }
                    mysqli_stmt_close($stmt4);
                }

                $data .= '</div>';
            }
        } else {
            $data .= '<p>No categories found!</p>';
        }
        mysqli_stmt_close($stmt);
    }

    $data .= '</div>';

    echo $data;
}
mysqli_close($dbc);
?>