<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('info_admin')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {
	$data = '<script>
	$(document).ready(function() {
		function updateInfoReportCount() {
			let count = $(".chk-box-delete-confirm-report:checked").length;
			let count_zero = "0";
			$(".my_confirm_count").html(count != 0 ? count : count_zero);
			$(".hide_and_seek").prop("disabled", count === 0);
		}

		let $alert_chkboxes = $(".chk-box-delete-confirm-report");
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
			updateInfoReportCount();
		});

		$(".select-all-delete-confirm-report").on("click", function() {
			let isChecked = $(this).is(":checked");
			$alert_chkboxes.prop("checked", isChecked);
			updateInfoReportCount();
		});

		$alert_chkboxes.on("click", function() {
			updateInfoReportCount();
			let allChecked = $(".chk-box-delete-confirm-report").length === $(".chk-box-delete-confirm-report:checked").length;
			$(".select-all-delete-confirm-report").prop("checked", allChecked);
		});

		$("#info_confirm_table").DataTable({
			aLengthMenu: [
				[100, 200, -1],
				[100, 200, "All"]
			],
			responsive: true,
			columnDefs: [
				{ orderable: false, targets: [0, 1, 7, 8] }
			],
			order: []
		});

		updateInfoReportCount();
	});
	</script>';

    $data .= '<div class="table-responsive p-1">
		<table class="table table-sm" id="info_confirm_table" width="100%">
        <thead class="dark-gray table-light">
            <tr>
            	<th>
                	<div class="form-check">
                    	<input type="checkbox" class="form-check-input select-all-delete-confirm-report" id="select-deleted-confirm-report">
                    	<label class="form-check-label" for="select-deleted-confirm-report"></label>
               		</div>
            	</th>
				<th class="">Photo</th>
				<th class="">Name</th>
                <th class="">User</th>
				<th class="text-center">Icon</th>
                <th>Title</th>
                <th>Subtitle</th>
                <th class="text-center">Posted</th>
				<th class="text-center">Read</th>
            </tr>
        </thead>
        <tbody class="bg-white">';

		$query = "
		    SELECT ic.*, i.info_icon, i.info_title, i.info_subtitle, i.info_date, u.profile_pic, u.first_name, u.last_name
		    FROM info_confirm ic
		    JOIN info i ON ic.info_id = i.info_id
		    JOIN users u ON ic.switch_id = u.switch_id
		    ORDER BY ic.info_confirm_id ASC";
			
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $info_id = htmlspecialchars(strip_tags($row['info_id']));
			$info_confirm_id = htmlspecialchars(strip_tags($row['info_confirm_id']));
            $info_icon = htmlspecialchars(strip_tags($row['info_icon']));
            $info_title = htmlspecialchars(strip_tags($row['info_title']));
            $info_subtitle = htmlspecialchars(strip_tags($row['info_subtitle']));
			$username = htmlspecialchars(strip_tags($row['username']));
			$info_date_confirm = htmlspecialchars(strip_tags($row['info_date_confirm']));
			$info_time_confirm = htmlspecialchars(strip_tags($row['info_time_confirm']));
            $info_date = htmlspecialchars(strip_tags($row['info_date']));
			$user_profile_pic = htmlspecialchars($row['profile_pic']);
			$user_first_name = htmlspecialchars($row['first_name']);
			$user_last_name = htmlspecialchars($row['last_name']);
			$user_full_name = $user_first_name .' '.$user_last_name;

			$data .= '<tr class="count-confirm-item bg-white" data-id="'.$info_id.'">
			    <td class="align-middle" style="width:2%;">
			        <div class="form-check">
			            <input type="checkbox" class="custom-delete form-check-input chk-box-delete-confirm-report" data-confirm-report-id="'.$info_confirm_id.'">
			            <label class="form-check-label" for="'.$info_confirm_id.'"></label>
			        </div>
			    </td>
    			<td class="align-middle" style="width:5%">
			        <img src="'.$user_profile_pic.'" class="profile-photo">
			    </td>
				<td class="align-middle" style="width:15%">
			        '.$user_full_name.' 
			    </td>
				<td class="align-middle" style="width:10%">
			        '.$username.' 
			    </td>
				<td class="text-center align-middle" style="cursor:pointer; width:4%">
			        <span>
			            <i class="'.$info_icon.'" style="font-size:16px;"></i>
			        </span>
			    </td>
				<td class="title_name align-middle" onclick="editInfo('.$info_id.')" style="cursor:pointer">
			        <span>'.$info_title.'</span>
			    </td>
				<td class="title_name align-middle" onclick="editInfo('.$info_id.')" style="cursor:pointer">
			        <span>'.$info_subtitle.'</span>
			    </td>
				<td class="align-middle text-center" style="width:10%">
			        <i class="fa-solid fa-clock"
						data-bs-toggle="tooltip" data-bs-placement="top"
						data-bs-custom-class="custom-tooltip"
				        data-bs-title="'.$info_date.'">
					</i>
				</td>
				<td class="align-middle text-center" style="width:10%">
					<i class="fa-solid fa-book-open-reader"
						data-bs-toggle="tooltip" data-bs-placement="top"
						data-bs-custom-class="custom-tooltip"
				        data-bs-title="'.$info_date_confirm.' '.$info_time_confirm.'">
					</i>
				</td>
			</tr>';
            
        }

        mysqli_stmt_close($stmt);
    }

    $data .= '</tbody></table></div>';

    echo $data;

    mysqli_close($dbc);
}
?>

<script>
$(document).ready(function() {
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});
});
</script>