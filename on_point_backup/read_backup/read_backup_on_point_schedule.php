<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_view')) {
    header("Location: ../../../index.php?msg1");
    exit();
}

if (isset($_SESSION['user'])) {

    $data = '<table class="table table-sm table-bordered mb-0" id="">
        <thead class="">
            <tr>
                <th scope="col"></th>
                <th scope="col">Monday</th>
                <th scope="col">Tuesday</th>
                <th scope="col">Wednesday</th>
                <th scope="col">Thursday</th>
                <th scope="col">Friday</th>
                <th scope="col">Saturday</th>
                <th scope="col">Sunday</th>
            </tr>
        </thead>
        <tbody class="bg-white" id="">';

		$query = "SELECT on_point_backup_id, on_point_backup_time, on_point_backup_monday, on_point_backup_tuesday, on_point_backup_wednesday, on_point_backup_thursday, on_point_backup_friday, on_point_backup_saturday, on_point_backup_sunday, on_point_backup_monday_color, on_point_backup_tuesday_color, on_point_backup_wednesday_color, on_point_backup_thursday_color, on_point_backup_friday_color, on_point_backup_saturday_color, on_point_backup_sunday_color FROM on_point_backup ORDER BY on_point_backup_display_order ASC";

		    if ($stmt = mysqli_prepare($dbc, $query)) {
		  	  	mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $on_point_backup_id, $on_point_backup_time, $on_point_backup_monday, $on_point_backup_tuesday, $on_point_backup_wednesday, $on_point_backup_thursday, $on_point_backup_friday, $on_point_backup_saturday, $on_point_backup_sunday, $on_point_backup_monday_color, $on_point_backup_tuesday_color, $on_point_backup_wednesday_color, $on_point_backup_thursday_color, $on_point_backup_friday_color, $on_point_backup_saturday_color, $on_point_backup_sunday_color);

		  	  	while (mysqli_stmt_fetch($stmt)) {
		      	  	$on_point_backup_id = htmlspecialchars($on_point_backup_id ?? '');
					$on_point_backup_time = htmlspecialchars($on_point_backup_time ?? '');
					$on_point_backup_monday = htmlspecialchars($on_point_backup_monday ?? '');
					$on_point_backup_tuesday = htmlspecialchars($on_point_backup_tuesday ?? '');
					$on_point_backup_wednesday = htmlspecialchars($on_point_backup_wednesday ?? '');
					$on_point_backup_thursday = htmlspecialchars($on_point_backup_thursday ?? '');
					$on_point_backup_friday = htmlspecialchars($on_point_backup_friday ?? '');
					$on_point_backup_saturday = htmlspecialchars($on_point_backup_saturday ?? '');
					$on_point_backup_sunday = htmlspecialchars($on_point_backup_sunday ?? '');
					$on_point_backup_monday_color = htmlspecialchars($on_point_backup_monday_color ?? '');
					$on_point_backup_tuesday_color = htmlspecialchars($on_point_backup_tuesday_color ?? '');
					$on_point_backup_wednesday_color = htmlspecialchars($on_point_backup_wednesday_color ?? '');
					$on_point_backup_thursday_color = htmlspecialchars($on_point_backup_thursday_color ?? '');
					$on_point_backup_friday_color = htmlspecialchars($on_point_backup_friday_color ?? '');
					$on_point_backup_saturday_color = htmlspecialchars($on_point_backup_saturday_color ?? '');
					$on_point_backup_sunday_color = htmlspecialchars($on_point_backup_sunday_color ?? '');
		            
            $data .= '
                <tr class="" data-id="' . $on_point_backup_id . '">
                    <th class="thead-dark text-white" scope="row">
                        <span>' . $on_point_backup_time . ' </span>
                    </th>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_monday_color . ' !important;">
                        <span>' . $on_point_backup_monday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_tuesday_color . ' !important;">
                        <span>' . $on_point_backup_tuesday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_wednesday_color . ' !important;">
                        <span>' . $on_point_backup_wednesday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_thursday_color . ' !important;">
                        <span>' . $on_point_backup_thursday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_friday_color . ' !important;">
                        <span>' . $on_point_backup_friday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_saturday_color . ' !important;">
                        <span>' . $on_point_backup_saturday . ' </span>
                    </td>
                    <td class="td-color-backup" style="background-color: ' . $on_point_backup_sunday_color . ' !important;">
                        <span>' . $on_point_backup_sunday . ' </span>
                    </td>
                </tr>';
        }
		mysqli_stmt_close($stmt);
    } else {
        die('Query Failed.');
    }

    $data .= '</tbody></table>';

    echo $data;
}
mysqli_close($dbc);