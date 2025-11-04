<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!checkRole('system_links_admin')) {
    header("Location:../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {

    $data = '<div class="list-group nested-sortable folder-list mt-2">';

    $query_weblinks = "SELECT * FROM links_rsb_weblinks ORDER BY link_display_order ASC";
    if ($stmt = mysqli_prepare($dbc, $query_weblinks)) {
        mysqli_stmt_execute($stmt);
        $result_weblinks = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result_weblinks) > 0) {
            
			while ($row_weblink = mysqli_fetch_assoc($result_weblinks)) {
                $link_full_name = htmlspecialchars($row_weblink['link_full_name']);
                
                $data .= '<div class="weblink bg-light border border-white p-1 mb-1" draggable="false">
                            <span class="fw-bold ms-1">' . $link_full_name . '</span>
                          </div>';
            }
        } else {
            $data .= '<p>No weblinks found!</p>';
        }
        mysqli_stmt_close($stmt);
    }

    $data .= '</div>';

    echo $data;

}

mysqli_close($dbc);
?>
