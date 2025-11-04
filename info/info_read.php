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

<style>
.custom-table {
	background-color: #ffeef6;
	border: 1px solid #fbadd1 !important;
}
.search-category {
	display: none;
}
</style>	
	
<script>
$(document).ready(function(){
	function countGroupItems(){
		var count = $('.count-group-item').length;
	    $('.count-group').html(count);
	} countGroupItems();
});
</script>

<script>
	$(document).ready(function() {
	    var lastChecked = null;
		$('.group-chk-box').on('click', function(e) {
	     	if (lastChecked && e.shiftKey) {
	            var start = $('.group-chk-box').index(this);
	            var end = $('.group-chk-box').index(lastChecked);
            	var checkboxes = $('.group-chk-box').slice(Math.min(start, end), Math.max(start, end) + 1);
	            checkboxes.prop('checked', lastChecked.checked);
	        }
			$("#select_count").html($("input.group-chk-box:checked").length + " ");
			lastChecked = this;
	    });
		$('.select-all-group').on('click', function() {
	        if ($(this).is(':checked', true)) {
	            $(".group-chk-box").prop('checked', true);
	        } else {
	            $(".group-chk-box").prop('checked', false);
	        }
	     	$("#select_count").html($("input.group-chk-box:checked").length + " ");
	    });
		$(".group-chk-box").on('click', function() {
	  	  	$("#select_count").html($("input.group-chk-box:checked").length + " ");
			if ($(".group-chk-box").not(':checked').length > 0) {
	            $(".select-all-group").prop("checked", false);
	        }
			if ($(".group-chk-box").not(':checked').length == 0) {
	            $(".select-all-group").prop("checked", true);
	        }
	    });
		$('input.manage:checkbox').click(function() {
	        if ($(this).is(':checked')) {
	            $('#search-category').addClass('search-category');
	            $('#custom-table').addClass('custom-table');
	            $('#table-color').addClass('table-color');
	            $('.hide_and_seek_group').fadeIn();
	        } else {
	            if ($('.group-chk-box').filter(':checked').length < 1) {
	                $('#search-category').removeClass('search-category');
	                $('#table-color').removeClass('table-color');
	                $('#custom-table').removeClass('custom-table');
	                $('.hide_and_seek_group').hide();
	            }
	        }
	    });
	});
</script>

<?php

if (isset($_SESSION['id'])) {

    $data = '<script>
$(document).ready(function(){
    $(".info_drag_icon").mousedown(function(){
        $( "#sortable_info_row" ).sortable({
			containment: "parent", 
            update: function( event, ui ) {
                updateInfoDisplayOrder();
            }
        });
    });
});
</script>

<script>
function updateInfoDisplayOrder() {
	var selectedItem = [];
	$("tbody#sortable_info_row tr").each(function() {
        selectedItem.push($(this).data("id"));
    });
  	var dataString = "sort_order=" + selectedItem.join(",");
  	$.ajax({
        type: "GET",
        url: "ajax/info/info_update_order.php",
        data: dataString,
        cache: false,
        success: function(data){
            readInfo();
           	var toastTrigger = document.getElementById("sortable_info_row");
            var toastLiveExample = document.getElementById("toast-info-order");
            if (toastTrigger) {
                var toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }
        }
    });
}
</script>';

    $data .= '<table class="table table-sm table-hover mb-0">
        <thead class="dark-gray table-light">
            <tr>
                <th class="text-center">
                    <div class="form-check form-switch ms-2">
                        <input type="checkbox" class="form-check-input select-all-group manage" id="select-all-group" onclick="searchTog();">
                        <label class="form-check-label" for="select-all-group"></label>
                    </div>
                </th>
                <th class="text-center">Sort</th>
                <th class="text-center">Icon</th>
                <th>Title</th>
                <th class="text-center">Open</th>
                <th class="text-center">Close</th>
                <th class="text-center">Status</th>
                <th class="text-center">Details</th>
            </tr>
        </thead>
        <tbody class="bg-white" id="sortable_info_row">';

    $query = "
        SELECT i.*, COUNT(ic.username) AS read_count
        FROM info i
        LEFT JOIN info_confirm ic ON i.info_id = ic.info_id
        GROUP BY i.info_id
        ORDER BY i.info_display_order ASC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
     	date_default_timezone_set("America/New_York");
        $current_time = new DateTime();
        
		if (mysqli_num_rows($result) > 0) {
		            while ($row = mysqli_fetch_assoc($result)) {
		                $info_id = htmlspecialchars(strip_tags($row['info_id'] ?? ''));
		                $info_icon = htmlspecialchars(strip_tags($row['info_icon'] ?? ''));
		                $info_title = htmlspecialchars(strip_tags($row['info_title'] ?? ''));
		                $info_status = htmlspecialchars(strip_tags($row['info_status'] ?? ''));
						$info_open = !empty($row['info_publish']) ? new DateTime($row['info_publish']) : null;
		                $info_close = !empty($row['info_expire']) ? new DateTime($row['info_expire']) : null;
						$status_time = '';

		              	if ($info_status === '0') {
							$status_time = '<span class="badge bg-edit w-100"><i class="fa-regular fa-circle-pause"></i> Paused </span>';
		                } else if ($info_close && $current_time > $info_close) {
		                    $status_time = '<span class="badge bg-hot w-100"><i class="fa-solid fa-triangle-exclamation"></i> Expired </span>';
		                } else {
		                    if ($info_open) {
		                        if ($current_time >= $info_open && (!$info_close || $current_time <= $info_close)) {
		                            $status_time = '<span class="badge bg-mint w-100"><i class="far fa-check-circle"></i> Active </span>';
		                        } elseif ($current_time < $info_open) {
		                            $status_time = '<span class="badge bg-cool-ice w-100"><i class="fa-solid fa-hourglass-half"></i> Pending </span>';
		                        }
		                    } else {
		                        if ($info_close) {
		                    		$status_time = ($current_time > $info_close) ? '<span class="badge bg-hot w-100"><i class="fa-solid fa-triangle-exclamation"></i> Expired </span>' : '<span class="badge bg-mint w-100"><i class="far fa-check-circle"></i> Active </span>';
		                        } else {
		                            $status_time = '<span class="badge bg-mint w-100"><i class="far fa-check-circle"></i> Active </span>';
		                        }
		                    }
		                }

		                $publish_badge = '';
		                $expire_badge = '';

		                if ($info_open) {
		                    $formatted_publish = $info_open->format('m-d-Y g:i A');
		                    if ($info_close && $current_time > $info_close) {
		                        $publish_badge = '<span class="badge bg-light p-2 text-dark shadow-sm w-100">' . $formatted_publish . '</span>';
		                    } else {
		                        $publish_badge = '<span class="badge bg-light p-2 text-dark shadow-sm w-100">' . $formatted_publish . '</span>';
		                    }
		                } else {
		                    $publish_badge = '<span class="badge bg-light p-2 text-dark shadow-sm w-100">Available</span>';
		                }

		          	  	if ($info_close) {
		                    $formatted_expire = $info_close->format('m-d-Y g:i A');
		                    $expire_badge = ($current_time > $info_close) ? 
		                        '<span class="badge bg-light p-2 text-dark shadow-sm w-100">' . $formatted_expire . '</span>' :
		                        '<span class="badge bg-light p-2 text-dark shadow-sm w-100">' . $formatted_expire . '</span>';
		                } else {
		                    $expire_badge = '<span class="badge bg-light p-2 text-dark shadow-sm w-100">Unlimited</span>';
		                }

		                $read_count = $row['read_count'] ?? 0;

		                $data .= '<tr class="count-group-item bg-white" data-id="' . $row['info_id'] . '">
		                    <td class="align-middle text-center" style="width:3%;">
		                        <div class="form-check form-switch mt-2 ms-2">
		                            <input type="checkbox" class="form-check-input group-chk-box manage" id="' . $info_id . '" data-display-info-id="' . $info_id . '">
		                            <label class="form-check-label" for="' . $info_id . '"></label>
		                        </div>
		                    </td>
		                    <td class="info_drag_icon align-middle text-center" style="cursor:move; width:3%;">
		                        <i class="fas fa-bars"></i>
		                    </td>
		                    <td class="text-center align-middle" style="cursor:pointer; width:3%">
		                        <span onclick="editInfo(' . $info_id . ')" style="cursor:pointer">
		                            <i class="text-dark ' . $info_icon . '" style="font-size:16px;"></i>
		                        </span>
		                    </td> 
		                    <td class="title_name align-middle" onclick="editInfo(' . $info_id . ')" style="cursor:pointer">
		                        <span class="text-dark">' . $info_title . '</span>
		                    </td>
		                    <td class="align-middle" style="width:20%">' . $publish_badge . '</td>
		                    <td class="align-middle" style="width:20%">' . $expire_badge . '</td>
		                    <td class="align-middle" style="width:10%">' . $status_time . '</td>
		                    <td class="align-middle text-center" style="width:12%">
		                        <div class="btn-group btn-group-sm" role="group">
		                            <button type="button" class="btn btn-light-gray btn-sm">
		                                <i class="fa-solid ' . ($info_status == 1 ? 'fa-eye text-purple' : 'fa-eye-slash text-pink') . '"></i>
		                            </button>
		                            <button type="button" class="btn btn-light-gray btn-sm">
		                                <span class="text-dark">' . $read_count . '</span>
		                            </button>
		                            <button type="button" class="btn btn-light-gray btn-sm" onclick="readInfoDetails(' . $info_id . ')">
		                                <i class="fa-solid fa-circle-info"></i>
		                            </button>
		                          	<button type="button" class="btn btn-light-gray btn-sm" onclick="editInfo(' . $info_id . ')">
		                                <i class="fa-solid fa-pen-to-square"></i>
		                            </button>
								</div>
		                    </td>';

		                $data .= '</tr>';
		            }
		        } else {
		            $data .= '<tr><td colspan="8" class="text-center p-3">
		                <svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
		                    <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2)" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
		                    <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
		                </svg>
		                <p class="one success">Empty!</p>
		                <p class="complete mb-3">Info not found!</p>
		            </td></tr>';
		        }
				mysqli_stmt_close($stmt);
		    }

		    $data .= '</tbody></table>';
			echo $data;
			mysqli_close($dbc);
		}