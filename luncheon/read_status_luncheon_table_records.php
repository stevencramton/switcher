<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['user'])) {
	
	$data ='<table class="table table-sm mb-0">
			<thead class="table-secondary">
				<tr>
					<th scope="col">Photo</th>
					<th scope="col">Name</th>
					<th scope="col">Date</th>
					<th scope="col">Start</th>
					<th scope="col">End</th>
					<th scope="col">Status</th>
					<th scope="col" style="text-align:center;">Icon</th>
				</tr>
			</thead>
			<tbody>';

	$luncheon_sender = $_SESSION['user'];
	$luncheon_query = "SELECT * FROM luncheon WHERE luncheon_sender = ?";
	
	if ($select_luncheon_statement = mysqli_prepare($dbc, $luncheon_query)) {
		mysqli_stmt_bind_param($select_luncheon_statement, "s", $luncheon_sender);
		mysqli_stmt_execute($select_luncheon_statement);
		$select_luncheon_result = mysqli_stmt_get_result($select_luncheon_statement);

		if (!$select_luncheon_result) {
			exit();
		}

		while ($row = mysqli_fetch_assoc($select_luncheon_result)) {
			$luncheon_id = $row['luncheon_id'];
			$luncheon_time_start = $row['luncheon_time_start'];
			$luncheon_time_end = $row['luncheon_time_end'];
			$luncheon_status = $row['luncheon_status'];
			$luncheon_color = $row['luncheon_color'];

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

			$luncheon_submitter = $row['luncheon_sender'];
			$submitter_query = "SELECT * FROM users WHERE user = ?";
			
			if ($select_submitter_statement = mysqli_prepare($dbc, $submitter_query)) {
				mysqli_stmt_bind_param($select_submitter_statement, "s", $luncheon_submitter);
				mysqli_stmt_execute($select_submitter_statement);
				$select_submitter_result = mysqli_stmt_get_result($select_submitter_statement);

				if (!$select_submitter_result) {
					exit();
				}

				$submitter_row = mysqli_fetch_assoc($select_submitter_result);
				$submitter_pic = $submitter_row['profile_pic'];
				$submitter_first_name = $submitter_row['first_name'];
				$submitter_last_name = $submitter_row['last_name'];
				$submitter_name = $submitter_first_name.' '.$submitter_last_name;
				
				mysqli_stmt_close($select_submitter_statement);
			} else {
				exit("Submitter statement preparation error.");
			}

			date_default_timezone_set('America/New_York');

			$data .='<tr class="'. $table_away .'" id="'.$luncheon_id.'" style="">
						<td style="width:50px;text-align:center;"><img src="'.$submitter_pic.'" class="profile-photo-luncheon-table"></td>
						<td style="width:300px;">'.$submitter_name.'</td>
						<td>'. date("m.d.Y") .'</td>
						<td>'. $luncheon_time_start .'</td>
						<td>'. $luncheon_time_end .'</td>
						<td>'. $table_status . '</td>
						<td style="text-align:center;"><i class="'. $luncheon_icon . ' '. $luncheon_icon_text . '"></i></td>
					</tr>';
		}

		mysqli_stmt_close($select_luncheon_statement);
	} else {
		exit("Luncheon statement preparation error.");
	}

	$data .='</tbody></table>';

	echo $data;
}

mysqli_close($dbc);