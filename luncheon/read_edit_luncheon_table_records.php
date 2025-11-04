<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['id'])) {

    $data = '<style>
    td { vertical-align: middle!important; }
    table td.edit-time-td { height:37px; }
    table td.edit-time-td:hover { color: black; font-family: sans-serif; font-size: 12px; cursor: url(img/darkroller.png), default; }
    </style>';

    $data .= '<table class="table table-sm table-bordered" id="editUserLunchTable">
    <thead class="table-secondary">
        <tr>
            <th scope="col" style="text-align:center;">Edit</th>
            <th scope="col">Photo</th>
            <th scope="col">Name</th>';

	$time_query = "SELECT * FROM luncheon_admin";

    if ($stmt = mysqli_prepare($dbc, $time_query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($time_row = mysqli_fetch_array($result)) {
            $new_time = "";
            $time_format = isset($time_row['time_format']) ? htmlspecialchars(strip_tags($time_row['time_format'])) : '';
            $start_time = isset($time_row['start_time']) ? strtotime(htmlspecialchars(strip_tags($time_row['start_time']))) : 0;
            $end_time = isset($time_row['end_time']) ? strtotime(htmlspecialchars(strip_tags($time_row['end_time']))) : 0;
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
        mysqli_stmt_close($stmt);
    }

    $data .= '</tr>
    </thead>
    <tbody>';

    $luncheon_sender = isset($_SESSION['user']) ? htmlspecialchars(strip_tags($_SESSION['user'])) : '';
	$luncheon_query = "SELECT * FROM luncheon";

    if ($select_luncheon_record = mysqli_query($dbc, $luncheon_query)) {
        while ($row = mysqli_fetch_assoc($select_luncheon_record)) {
            $luncheon_id = isset($row['luncheon_id']) ? htmlspecialchars(strip_tags($row['luncheon_id'])) : '';
            $luncheon_sender = isset($row['luncheon_sender']) ? htmlspecialchars(strip_tags($row['luncheon_sender'])) : '';
            $luncheon_time_start = isset($row['luncheon_time_start']) ? htmlspecialchars(strip_tags($row['luncheon_time_start'])) : '';
            $luncheon_time_end = isset($row['luncheon_time_end']) ? htmlspecialchars(strip_tags($row['luncheon_time_end'])) : '';
            $luncheon_color = isset($row['luncheon_color']) ? htmlspecialchars(strip_tags($row['luncheon_color'])) : '';
            $luncheon_status = isset($row['luncheon_status']) ? htmlspecialchars(strip_tags($row['luncheon_status'])) : '';

            if ($luncheon_status == 1) {
                $table_status = "Away";
                $table_away = "table-away";
                $luncheon_icon = "fas fa-eye-slash";
                $luncheon_icon_text = "text-away";
            } else {
                $table_status = "Available";
                $table_away = "table-available";
                $luncheon_icon = "far fa-eye";
                $luncheon_icon_text = "text-available";
            }

            $data .= '<style>
                .highlighted_' . $luncheon_id . ' {
                    background-color:';
                    $data .= empty($luncheon_color) ? '#aad400' : $luncheon_color;
                    $data .= ';
                    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
                }
            </style>';

            $luncheon_submitter = isset($row['luncheon_sender']) ? htmlspecialchars(strip_tags($row['luncheon_sender'])) : '';

          	$submitter_query = "SELECT * FROM users WHERE user = ? AND account_delete = 0";
            if ($stmt = mysqli_prepare($dbc, $submitter_query)) {
                mysqli_stmt_bind_param($stmt, 's', $luncheon_submitter);
                mysqli_stmt_execute($stmt);
                $submitter_result = mysqli_stmt_get_result($stmt);
                $submitter_row = mysqli_fetch_array($submitter_result);
				$submitter_pic = isset($submitter_row['profile_pic']) ? htmlspecialchars(strip_tags($submitter_row['profile_pic'])) : '';
                $submitter_first_name = isset($submitter_row['first_name']) ? htmlspecialchars(strip_tags($submitter_row['first_name'])) : '';
                $submitter_last_name = isset($submitter_row['last_name']) ? htmlspecialchars(strip_tags($submitter_row['last_name'])) : '';
				$submitter_name = $submitter_first_name . ' ' . $submitter_last_name;
				mysqli_stmt_close($stmt);
            }

            $data .= '<tr class="' . $table_away . '" id="lunch_row_' . $luncheon_id . '" data-lunch-id="' . $luncheon_id . '">
                <td style="width:50px;text-align:center;">
                    <div class="form-check form-switch ms-1">
                        <input type="checkbox" class="form-check-input edit-user-lunch-chk" name="edit_lunch" id="edit_user_lunch_' . $luncheon_id . '" data-lunch-id=' . $luncheon_id . '>
                        <label class="form-check-label" for="edit_user_lunch_' . $luncheon_id . '"></label>
                    </div>
                </td>
                <td style="width:50px;text-align:center;"><img src="' . $submitter_pic . '" class="profile-photo-luncheon-table"></td>
                <td style="width:300px;">' . $submitter_name . '</td>';

            $col_names = [];
            $search = "time_cell_";

            foreach ($row as $key => $value) {
                if (strpos($key, $search) !== false) {
                    $col_names[] = $key;
                }
            }

            $col_count = count($col_names);

            $a = 1;
            $x = 0;

            foreach ($col_names as $key => $value) {
                $edit_begin_time_string = substr($value, 10);
                $edit_begin_time_string = substr_replace($edit_begin_time_string, ':', 2, 0);
                $edit_begin_time_string = strtotime($edit_begin_time_string);
                $edit_end_time_string = $edit_begin_time_string + 900;
                $edit_begin_time_string = date('h:i a', $edit_begin_time_string);
                $edit_end_time_string = date('h:i a', $edit_end_time_string);

                if ($x == 0 && $a < $col_count) {
                    if (isset($row[$value]) && $row[$value] == 1) {
                        $data .= '<td class="highlighted_' . $luncheon_id . ' time-cell-td" id="edit_' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    } else {
                        $data .= '<td id="edit_' . $value . '" class="time-cell-td"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    }
                    $x++;
                    $a++;
                } else if ($x < 3 && $a < $col_count) {
                    if (isset($row[$value]) && $row[$value] == 1) {
                        $data .= '<td class="highlighted_' . $luncheon_id . ' time-cell-td" id="edit_' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    } else {
                        $data .= '<td id="edit_' . $value . '" class="time-cell-td"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    }
                    $x++;
                    $a++;
                } else if ($x == 3 && $a < $col_count) {
                    if (isset($row[$value]) && $row[$value] == 1) {
                        $data .= '<td class="highlighted_' . $luncheon_id . ' time-cell-td" id="edit_' . $value . '"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    } else {
                        $data .= '<td id="edit_' . $value . '" class="time-cell-td"><span style="display:none;">' . $edit_begin_time_string . ' and ' . $edit_end_time_string . '</span></td>';
                    }
                    $x = 0;
                    $a++;
                } else if ($a == $col_count) {
                    $data .= "";
                }
            }

            $data .= '</tr>';
        }
    }

    $data .= '</tbody></table>';

    echo $data;
}
?>

<script>
$(document).ready(function(){
	$(document).on("click", ".edit-user-lunch-chk",function(){
		var len = $(".edit-user-lunch-chk:checked").length;
			if (len > 0){
				$("#manageLunchButtons").fadeIn();
				if ($(this).is(":checked")){
					luncheon_id = $(this).data("lunch-id");
					var table_row = $(this).closest("tr");
					$(table_row).find(".time-cell-td").each(function(){
						$(this).addClass("edit-time-td");
					})
					if (!$("#editUserLunchTable").hasClass("editActive")){
						$("#editUserLunchTable").addClass("editActive");
						var isMouseDown = false,
						isHighlighted;

						$(document).on("mousedown", ".edit-time-td", function () {
							var second_table_row = $(this).closest("tr");
							row_lunch_id = $(second_table_row).data("lunch-id");
							isMouseDown = true;
							if ($(this).hasClass("highlighted_"+row_lunch_id+"") == true){
								$(this).removeClass("highlighted_"+row_lunch_id+"");
							} else if ($(this).hasClass("highlighted_"+row_lunch_id+"") == false){
								$(this).addClass("highlighted_"+row_lunch_id+"");
							}
							isHighlighted = $(this).hasClass("highlighted_"+row_lunch_id+"");
							return false;
						})
						$(document).on("mouseover", ".edit-time-td", function () {
							var third_table_row = $(this).closest("tr");
							row_lunch_id = $(third_table_row).data("lunch-id");
							if (isMouseDown) {
								$(this).toggleClass("highlighted_"+row_lunch_id+"", isHighlighted);
							}
						})
						$(document).on("bind", ".edit-time-td", function () {
							return false;
						})
						$(document).on("mouseup", ".edit-time-td", function () {
							isMouseDown = false;
						});

				} else {}
			} else {
				var table_row = $(this).closest("tr");
				$(table_row).find(".time-cell-td").each(function(){
					$(this).removeClass("edit-time-td");
				})
			}

		} else {
			$("#manageLunchButtons").fadeOut();
			$(this).closest("tr").find(".edit-time-td").unbind();
			$(this).closest("tr").find(".time-cell-td").removeClass("edit-time-td");
		}
	})
});
</script>
