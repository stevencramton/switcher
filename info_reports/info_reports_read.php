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
?>

<?php
if (isset($_SESSION['id'])) {
	$data = '<script>
	$(document).ready(function() {
		function updateInfoCount() {
			let count = $(".chk-box-delete-info-report:checked").length;
			let count_zero = "0";
			$(".my_info_count").html(count != 0 ? count : count_zero);
			$(".hide_and_seek_info").prop("disabled", count === 0);
		}

		let $alert_chkboxes = $(".chk-box-delete-info-report");
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
			updateInfoCount();
		});

		$(".select-all-delete-info-report").on("click", function() {
			let isChecked = $(this).is(":checked");
			$alert_chkboxes.prop("checked", isChecked);
			updateInfoCount();
		});

		$alert_chkboxes.on("click", function() {
			updateInfoCount();
			let allChecked = $(".chk-box-delete-info-report").length === $(".chk-box-delete-info-report:checked").length;
			$(".select-all-delete-info-report").prop("checked", allChecked);
		});

		$("#info_table").DataTable({
			aLengthMenu: [
				[100, 200, -1],
				[100, 200, "All"]
			],
			responsive: true,
			columnDefs: [
				{ orderable: false, targets: [0] }
			],
			order: []
		});

		updateInfoCount();
	});
	</script>';
    
	$data .= '<div class="table-responsive p-1">
		<table class="table table-sm" id="info_table" width="100%">
        <thead class="dark-gray table-light">
            <tr>
        		<th>
            		<div class="form-check">
                		<input type="checkbox" class="form-check-input select-all-delete-info-report" id="select-deleted-info-report">
                		<label class="form-check-label" for="select-deleted-info-report"></label>
           			</div>
        		</th>
       	 		<th>Title</th>
           	 	<th>Created By</th>
                <th>Creation Date</th>
                <th class="text-center">Read</th>
 			</tr>
        </thead>
        <tbody class="bg-white">';

		$query = "
		    SELECT ic.info_id, 
		           COUNT(ic.username) AS read_count,
		           MAX(ic.info_title_confirm) AS info_title,
		           MAX(ic.info_message_confirm) AS info_message,
		           MAX(ic.info_created_by_confirm) AS created_by,
		           MAX(ic.info_date_created_confirm) AS date_created
		    FROM info_confirm ic
		    WHERE ic.info_date_confirm IS NOT NULL -- Ensure we are considering valid records
		    GROUP BY ic.info_id
		    ORDER BY MAX(ic.info_date_created_confirm) DESC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
			$info_id = htmlspecialchars(strip_tags($row['info_id']));
            $info_title = htmlspecialchars(strip_tags($row['info_title']));
          	$created_by = htmlspecialchars(strip_tags($row['created_by']));
            $date_created = htmlspecialchars(strip_tags($row['date_created']));
            $read_count = $row['read_count'];

            $data .= '<tr class="count-info-item bg-white" data-id="' . $info_id . '">
			    <td class="align-middle" style="width:2%;">
			        <div class="form-check">
			            <input type="checkbox" class="custom-delete form-check-input chk-box-delete-info-report" data-info-report-id="'.$info_id.'">
			            <label class="form-check-label" for="'.$info_id.'"></label>
			        </div>
			    </td>
       		 	<td class="title_name align-middle" style="width:25%;">
                    <span>'.$info_title.'</span>
                </td>
        		<td class="align-middle" style="width:20%"><small>'.$created_by.'</small></td>
                <td class="align-middle" style="width:20%"><small>'.$date_created.'</small></td>
                <td class="align-middle text-center" style="width:7%">
                    '.$read_count.'
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