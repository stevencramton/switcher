<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}
?>

<script>
$(document).ready(function(){
	$('[data-bs-toggle="tooltip"]').tooltip();
});
</script>

<?php
if (isset($_SESSION['id'])) {
    $data = '<table class="table table-sm table-bordered">
                <thead class="table-secondary">
                    <tr>
                        <th scope="col">Photo</th>
                        <th scope="col">Name</th>';

    $time_query = "SELECT * FROM luncheon_admin";

    if ($time_statement = mysqli_prepare($dbc, $time_query)) {
        mysqli_stmt_execute($time_statement);
        $time_result = mysqli_stmt_get_result($time_statement);

        while ($time_row = mysqli_fetch_array($time_result)) {
            $new_time = "";
            $time_format = strip_tags($time_row['time_format']);
            $start_time = strtotime(strip_tags($time_row['start_time']));
            $end_time = strtotime(strip_tags($time_row['end_time']));
            $x = 0;

            while ($new_time < $end_time) {
                if ($x == 0) {
                    $data .= '<th class="cell-square-start" colspan="4">' . date($time_format, $start_time) . '</th>';
                    $new_time = $start_time + 3600;
                    $x++;
                } else if ($x != 0) {
                    $data .= '<th class="cell-square-start" colspan="4">' . date($time_format, $new_time) . '</th>';
                    $new_time = $new_time + 3600;
                    $x++;
                }
            }

            if ($new_time == $end_time) {
                $data .= '';
            }
        }
    }

    $data .= '</tr></thead><tbody>';

	$luncheon_query = "SELECT * FROM luncheon";
    if ($select_luncheon_statement = mysqli_prepare($dbc, $luncheon_query)) {
        mysqli_stmt_execute($select_luncheon_statement);
        $select_luncheon_record = mysqli_stmt_get_result($select_luncheon_statement);

        while ($row = mysqli_fetch_assoc($select_luncheon_record)) {
            $luncheon_id = $row['luncheon_id'];
            $luncheon_sender = $row['luncheon_sender'];
            $luncheon_time_start = $row['luncheon_time_start'];
            $luncheon_time_end = $row['luncheon_time_end'];
            $luncheon_color = $row['luncheon_color'];
            $luncheon_status = $row['luncheon_status'];

            if ($luncheon_status == 1) {
                $table_away = "table-away";
            } else {
                $table_away = "table-available";
            }

            $data .= '<style>
                        .highlighted_' . $luncheon_id . ' {
                            background-color: ' . (empty($luncheon_color) ? '#aad400' : $luncheon_color) . ' !important;
                            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
                        }
                    </style>';

			$submitter_query = "SELECT * FROM users WHERE user = ? AND account_delete = 0";
            if ($submitter_statement = mysqli_prepare($dbc, $submitter_query)) {
                mysqli_stmt_bind_param($submitter_statement, "s", $luncheon_sender);
                mysqli_stmt_execute($submitter_statement);
                $submitter_result = mysqli_stmt_get_result($submitter_statement);

                if (mysqli_num_rows($submitter_result) > 0) {
                    $submitter_row = mysqli_fetch_array($submitter_result);
                    $submitter_pic = $submitter_row['profile_pic'];
                    $submitter_name = $submitter_row['first_name'] . ' ' . $submitter_row['last_name'];

                    $data .= '<tr class="' . $table_away . '" id="' . $luncheon_id . '">
                                <td style="width:50px;text-align:center;"><img src="' . $submitter_pic . '" class="profile-photo-luncheon-table"></td>
                                <td style="width:300px;" data-toggle="tooltip" data-placement="right" title="START: ' . $luncheon_time_start . ' END: ' . $luncheon_time_end . '">' . $submitter_name . '</td>';

                	$col_names = array_filter(array_keys($row), function ($key) {
                        return strpos($key, 'time_cell_') !== false;
                    });

                    $col_count = count($col_names);
                    $a = 1;
                    $x = 0;

                    foreach ($col_names as $value) {
                        $edit_begin_time_string = substr($value, 10);
                        $edit_begin_time_string = substr_replace($edit_begin_time_string, ':', 2, 0);
                        $edit_begin_time_string = strtotime($edit_begin_time_string);
                        $edit_end_time_string = $edit_begin_time_string + 900;
                        $edit_begin_time_string = date('h:i a', $edit_begin_time_string);
                        $edit_end_time_string = date('h:i a', $edit_end_time_string);

                        if ($x == 0 && $a < $col_count) {
                            $data .= '<td' . ($row[$value] == 1 ? ' class="highlighted_' . $luncheon_id . '"' : '') . ' id="' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                            $x++;
                            $a++;
                        } else if ($x < 3 && $a < $col_count) {
                            $data .= '<td' . ($row[$value] == 1 ? ' class="highlighted_' . $luncheon_id . '"' : '') . ' id="' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                            $x++;
                            $a++;
                        } else if ($x == 3 && $a < $col_count) {
                            $data .= '<td' . ($row[$value] == 1 ? ' class="highlighted_' . $luncheon_id . '"' : '') . ' id="' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                            $x = 0;
                            $a++;
                        }
                    }

                    $data .= '</tr>';
                }
            }
        }
    }

    $data .= '</tbody></table>';

    echo $data;
	
	mysqli_close($dbc);
}
?>