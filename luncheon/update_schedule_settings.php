<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['id'])) {
	$schedule_start_time = roundTime($_POST['schedule_start_time']);
    $schedule_end_time = roundTime($_POST['schedule_end_time']);
	$time_format = strip_tags($_POST['time_format']);
	$update_query = "UPDATE luncheon_admin SET start_time = ?, end_time = ?, time_format = ?";
    $update_statement = mysqli_prepare($dbc, $update_query);
	mysqli_stmt_bind_param($update_statement, "sss", $schedule_start_time, $schedule_end_time, $time_format);
	$update_result = mysqli_stmt_execute($update_statement);

 	if ($update_result) {
    	$schedule_start_time = strtotime($schedule_start_time);
        $schedule_end_time = strtotime($schedule_end_time);
        $x = 0;
        $new_time = $schedule_start_time;
        $col_names = [];
    } else {
     	echo "Error updating schedule.";
    }

	$query = "SELECT * FROM information_schema.columns WHERE table_name=? AND column_name LIKE ?";
    $table_name = 'luncheon';
    $column_name_pattern = 'time_cell%';

 	if ($stmt = mysqli_prepare($dbc, $query)) {
    	mysqli_stmt_bind_param($stmt, "ss", $table_name, $column_name_pattern);

      	if (mysqli_stmt_execute($stmt)) {
          	$result = mysqli_stmt_get_result($stmt);
			$col_names = [];

         	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $col_names[] = $row['COLUMN_NAME'];
            }

          	mysqli_stmt_close($stmt);

       	 	$col_names_string = implode(', ', array_map(function($col_name) {
                return "DROP `$col_name`";
            }, $col_names));

            $delete_col_query = "ALTER TABLE luncheon $col_names_string";

          	if (!$delete_col_result = mysqli_query($dbc, $delete_col_query)) {
                echo("Error description.");
            }
        } else {
         	echo("Error description.");
        }
    }

	$string = "";
    $new_time = $schedule_start_time;
    $x = 0;

	while ($new_time < $schedule_end_time) {
        if ($x == 0) {
            $converted_time = date('Hi', $schedule_start_time);
            $string .= "ADD time_cell_".$converted_time." int(11) NOT NULL DEFAULT 0, ";
            $new_time = $schedule_start_time + 900;
            $x++;
        } else {
            $converted_time = date('Hi', $new_time);
            $string .= "ADD time_cell_".$converted_time." int(11) NOT NULL DEFAULT 0, ";
            $new_time = $new_time + 900;
            $x++;
        }
    }

	if ($new_time == $schedule_end_time) {
        $converted_time = date('Hi', $schedule_end_time);
        $string .= "ADD time_cell_".$converted_time." int(11) NOT NULL DEFAULT 0";
    }

  	$column_query = "ALTER TABLE luncheon ".$string;
    if ($stmt = mysqli_prepare($dbc, $column_query)) {
     	if (!mysqli_stmt_execute($stmt)) {
            echo "Error description.";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Statement error.";
    }

 	$delete_entries_query = "TRUNCATE TABLE luncheon";
    if ($stmt = mysqli_prepare($dbc, $delete_entries_query)) {
      	if (!mysqli_stmt_execute($stmt)) {
            echo "Error.";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "Error.";
    }

	mysqli_close($dbc);
}
?>