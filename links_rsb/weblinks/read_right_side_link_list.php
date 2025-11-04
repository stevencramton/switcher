<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('hd_links_view')){
	header("Location:../../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id'])) {
    $data = '<ul class="list-group list-group-flush">';

    $query = "SELECT * FROM links ORDER BY link_display_order ASC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_array($result)) {
            $truncated = '';

            $id = htmlspecialchars($row['link_id']);
            $link_protocol = htmlspecialchars($row['link_protocol']);
            $link_url = htmlspecialchars($row['link_url']);
            $new_tab = htmlspecialchars($row['new_tab']);
            $link_full_name = htmlspecialchars($row['link_full_name']);
            $link_icon = htmlspecialchars($row['link_icon']);

            if ($link_protocol != 'no_protocol') {
                $full_url = $link_protocol . $link_url;
            } else {
                $full_url = $link_url;
                $truncated = (strlen($full_url) > 30) ? substr($full_url, 0, 30) . '...' : $full_url;
            }

            $data .= '<li class="list-group-item link_item p-0 right-search-three" style="border:none; background-color:transparent">';
            $data .= '<h6 class="my-0" style="font-size:14px;"><a href="' . $full_url . '"';
            
            if ($new_tab == "1") {
                $data .= ' target="_blank"';
            }

            $data .= '><i class="' . $link_icon . ' mr-2"></i>';

            if (strlen($link_full_name) >= 20) {
                $data .= substr($link_full_name, 0, 20) . '...';
            } else {
                $data .= $link_full_name;
            }

            $data .= '</a></h6></li>';
        }

        mysqli_stmt_close($stmt);
    }

    $data .= '</ul>';
    echo $data;
}

mysqli_close($dbc);