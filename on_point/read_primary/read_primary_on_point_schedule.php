<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_view')) {
    header("Location:../../../index.php?msg1");
}

if (isset($_SESSION['user'])) {
    $data = '<table class="table table-sm table-bordered mb-0" id="">
        <thead class="">
            <tr>
                <th class="text-center" scope="col"></th>
                <th class="text-center" scope="col">Monday</th>
                <th class="text-center" scope="col">Tuesday</th>
                <th class="text-center" scope="col">Wednesday</th>
                <th class="text-center" scope="col">Thursday</th>
                <th class="text-center" scope="col">Friday</th>
                <th class="text-center" scope="col">Saturday</th>
                <th class="text-center" scope="col">Sunday</th>
             </tr>
        </thead>
        <tbody class="bg-white" id="">';

    $query = "SELECT on_point_id, on_point_time, on_point_monday, on_point_tuesday, on_point_wednesday, on_point_thursday, on_point_friday, on_point_saturday, on_point_sunday, on_point_monday_color, on_point_tuesday_color, on_point_wednesday_color, on_point_thursday_color, on_point_friday_color, on_point_saturday_color, on_point_sunday_color, on_point_monday_text_color, on_point_tuesday_text_color, on_point_wednesday_text_color, on_point_thursday_text_color, on_point_friday_text_color, on_point_saturday_text_color, on_point_sunday_text_color FROM on_point ORDER BY on_point_display_order ASC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
  	  	mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $on_point_id, $on_point_time, $on_point_monday, $on_point_tuesday, $on_point_wednesday, $on_point_thursday, $on_point_friday, $on_point_saturday, $on_point_sunday, $on_point_monday_color, $on_point_tuesday_color, $on_point_wednesday_color, $on_point_thursday_color, $on_point_friday_color, $on_point_saturday_color, $on_point_sunday_color, $on_point_monday_text_color, $on_point_tuesday_text_color, $on_point_wednesday_text_color, $on_point_thursday_text_color, $on_point_friday_text_color, $on_point_saturday_text_color, $on_point_sunday_text_color);

		while (mysqli_stmt_fetch($stmt)) {
            $on_point_id = htmlspecialchars($on_point_id);
            $on_point_time = htmlspecialchars($on_point_time);
            $on_point_monday = htmlspecialchars($on_point_monday);
            $on_point_tuesday = htmlspecialchars($on_point_tuesday);
            $on_point_wednesday = htmlspecialchars($on_point_wednesday);
            $on_point_thursday = htmlspecialchars($on_point_thursday);
            $on_point_friday = htmlspecialchars($on_point_friday);
            $on_point_saturday = htmlspecialchars($on_point_saturday);
            $on_point_sunday = htmlspecialchars($on_point_sunday);
            $on_point_monday_color = htmlspecialchars($on_point_monday_color);
            $on_point_tuesday_color = htmlspecialchars($on_point_tuesday_color);
            $on_point_wednesday_color = htmlspecialchars($on_point_wednesday_color);
            $on_point_thursday_color = htmlspecialchars($on_point_thursday_color);
            $on_point_friday_color = htmlspecialchars($on_point_friday_color);
            $on_point_saturday_color = htmlspecialchars($on_point_saturday_color);
            $on_point_sunday_color = htmlspecialchars($on_point_sunday_color);
            $on_point_monday_text_color = htmlspecialchars($on_point_monday_text_color);
            $on_point_tuesday_text_color = htmlspecialchars($on_point_tuesday_text_color);
            $on_point_wednesday_text_color = htmlspecialchars($on_point_wednesday_text_color);
            $on_point_thursday_text_color = htmlspecialchars($on_point_thursday_text_color);
            $on_point_friday_text_color = htmlspecialchars($on_point_friday_text_color);
            $on_point_saturday_text_color = htmlspecialchars($on_point_saturday_text_color);
            $on_point_sunday_text_color = htmlspecialchars($on_point_sunday_text_color);
			
            $data .= '<tr class="" data-id="' . $on_point_id . '">
                <th class="thead-dark text-white text-center" scope="row">
                    <span>' . $on_point_time . '</span>
                </th>
                <td class="td-color" style="color: ' . $on_point_monday_text_color . '; background-color: ' . $on_point_monday_color . ';">
                    <span>' . $on_point_monday . '</span>
                </td>
                <td class="td-color" style="color: ' . $on_point_tuesday_text_color . '; background-color: ' . $on_point_tuesday_color . ';">
                    <span>' . $on_point_tuesday . ' </span>
                </td>
                <td class="td-color" style="color: ' . $on_point_wednesday_text_color . '; background-color: ' . $on_point_wednesday_color . ' !important;">
                    <span>' . $on_point_wednesday . ' </span>
                </td>
                <td class="td-color" style="color: ' . $on_point_thursday_text_color . '; background-color: ' . $on_point_thursday_color . ' !important;">
                    <span>' . $on_point_thursday . ' </span>
                </td>
                <td class="td-color" style="color: ' . $on_point_friday_text_color . '; background-color: ' . $on_point_friday_color . ' !important;">
                    <span>' . $on_point_friday . ' </span>
                </td>
                <td class="td-color" style="color: ' . $on_point_saturday_text_color . '; background-color: ' . $on_point_saturday_color . ' !important;">
                    <span>' . $on_point_saturday . ' </span>
                </td>
                <td class="td-color" style="color: ' . $on_point_sunday_text_color . '; background-color: ' . $on_point_sunday_color . ' !important;">
                    <span>' . $on_point_sunday . ' </span>
                </td>
            </tr>';
        }

     	mysqli_stmt_close($stmt);
    }

    $data .= '</tbody></table>';

    echo $data;
}
mysqli_close($dbc);