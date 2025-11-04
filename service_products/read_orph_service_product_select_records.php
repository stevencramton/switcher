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

$data ='<div class="form-floating">
			<select class="form-control" name="update_orph_product_service_group" id="update_orph_product_service_group">';

$query = 'SELECT * FROM service_groups ORDER BY group_name ASC';

if ($stmt = mysqli_prepare($dbc, $query)) {
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$group_id = $row['group_id'];
			$group_name = $row['group_name'];

			$data .= '<option value="'.$row['group_id'].'">'.$row['group_name'].'</option>';
		}
		$data .= '</select><label for="update_product_service_group">Group assignment:</label></div>';
	} else {
		$data .= '<option value="">No group assignments</option>';
	}

	mysqli_stmt_close($stmt);
} else {
	exit();
}

echo $data;

mysqli_close($dbc);
?>
