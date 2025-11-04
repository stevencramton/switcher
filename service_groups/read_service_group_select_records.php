<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id'])) {

    $data = '<div class="form-floating">
             <select class="form-select" name="product_service_group" id="product_service_group" aria-label="Create Product Floating label select">';

    $query = 'SELECT * FROM service_groups ORDER BY group_name ASC';

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $group_id = $row['group_id'];
                $group_name = $row['group_name'];

                $data .= '<option value="' . htmlspecialchars($group_id) . '">' . htmlspecialchars($group_name) . '</option>';
            }

            $data .= '</select><label for="product_service_group">Group assignment:</label></div>';
        } else {
     	   	$data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
            <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2)" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
            <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="one success">Records empty!</p>
            <p class="complete">Service Groups not found!</p><script>$("#service_product").prop("disabled",true);</script>';
        }

        mysqli_stmt_close($stmt);
    } else {
        exit("Error.");
    }
    echo $data;
}
mysqli_close($dbc);