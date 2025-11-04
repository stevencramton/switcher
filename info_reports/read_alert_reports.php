<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("Forbidden");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id'])) {

$data = '<script>
$(document).ready(function() {
	function updateAlertReportCount() {
		let count = $(".chk-box-delete-alert-report:checked").length;
		let count_zero = "0";
		$(".my_alert_count").html(count != 0 ? count : count_zero);
		$(".hide_and_seek").prop("disabled", count === 0);
	}

	let $alert_chkboxes = $(".chk-box-delete-alert-report");
	let lastChecked = null;

	$alert_chkboxes.on("click", function(e) {
		if (!lastChecked) {
			lastChecked = this;
			return;
		}
		if (e.shiftKey) {
			let start = $alert_chkboxes.index(this);
			let end = $alert_chkboxes.index(lastChecked);
			$alert_chkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).prop("checked", lastChecked.checked);
		}
		lastChecked = this;
		updateAlertReportCount();
	});

	$(".select-all-delete-alert-report").on("click", function() {
		let isChecked = $(this).is(":checked");
		$alert_chkboxes.prop("checked", isChecked);
		updateAlertReportCount();
	});
	$alert_chkboxes.on("click", function() {
		updateAlertReportCount();
		let allChecked = $(".chk-box-delete-alert-report").length === $(".chk-box-delete-alert-report:checked").length;
		$(".select-all-delete-alert-report").prop("checked", allChecked);
	});
	$("#alert_report_table").DataTable({
		aLengthMenu: [
			[100, 200, -1],
			[100, 200, "All"]
		],
		responsive: true,
		columnDefs: [
			{ orderable: false, targets: [0, 1, 7] }
		],
		order: []
	});
	updateAlertReportCount();
});
</script>';

		$data .= '<div class="table-responsive p-1">
		             <table class="table table-sm" id="alert_report_table" width="100%">
		                 <thead class="table-light">
		                     <tr>
		                         <th>
		                             <div class="form-check">
		                                 <input type="checkbox" class="form-check-input select-all-delete-alert-report" id="select-deleted-alert-report">
		                                 <label class="form-check-label" for="select-deleted-alert-report"></label>
		                             </div>
		                         </th>
		                         <th>Photo</th>
		                         <th>User</th>
								 <th>Title</th>
								 <th>Subtitle</th>
				  			   	 <th>Date</th>
		                         <th>Time</th>
		                         <th>Details</th>
		                     </tr>
		                 </thead>
		                 <tbody>';

		 $query = "SELECT ac.*, u.profile_pic FROM alerts_confirm ac JOIN users u ON ac.username = u.user ORDER BY ac.alert_confirm_id DESC";
		 $stmt = mysqli_prepare($dbc, $query);

		 if ($stmt) {
		     mysqli_stmt_execute($stmt);
		     $result = mysqli_stmt_get_result($stmt);

		     while ($row = mysqli_fetch_assoc($result)) {
		         
				 $alert_id = htmlspecialchars($row['alert_id']);
		         $alert_confirm_id = htmlspecialchars($row['alert_confirm_id']);
		         $user_profile_pic = htmlspecialchars($row['profile_pic']);
		         $username = htmlspecialchars($row['username']);
				 $alert_title_confirmed = htmlspecialchars($row['alert_title_confirmed']);
				 $alert_subtitle_confirmed = htmlspecialchars($row['alert_subtitle_confirmed']);
				 $alert_date_confirmed = htmlspecialchars($row['alert_date_confirmed']);
		         $alert_time_confirmed = htmlspecialchars($row['alert_time_confirmed']);
				 
				 $data .= '<tr class="count-alert-item" id="alert_report_table_row">
		                     <td class="align-middle" style="width:4%;">
		                         <div class="form-check">
		                             <input type="checkbox" class="custom-delete form-check-input chk-box-delete-alert-report" data-alert-report-id="' . $alert_confirm_id . '">
		                             <label class="form-check-label" for="' . $alert_confirm_id  . '"></label>
		                         </div>
		                     </td>
		                     <td class="align-middle" style="width:8%;"><img src="' . $user_profile_pic . '" class="profile-photo"></td>
		                     <td class="align-middle" style="width:12%;">' . $username . '</td>
							 <td class="align-middle">' . $alert_title_confirmed . '</td>
		                     <td class="align-middle">' . $alert_subtitle_confirmed . '</td>
							 <td class="align-middle">' . $alert_date_confirmed . '</td>
		                     <td class="align-middle">' . $alert_time_confirmed . '</td>
		                     <td class="align-middle text-center" style="width:5%">
		                         <button type="button" class="btn btn-light-gray btn-sm" onclick="readAlertDetails('.$alert_confirm_id.')">
		                             <i class="fa-solid fa-eye"></i>
		                         </button>
		                     </td>
		                   </tr>';
		     }

			 mysqli_stmt_close($stmt);
		 } else {
			 echo 'Error preparing statement.';
		 }

		 $data .= '</tbody>
		         </table>
		     </div>';

		 echo $data;
	 }
mysqli_close($dbc);