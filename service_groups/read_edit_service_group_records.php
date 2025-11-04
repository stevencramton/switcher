<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}
?>

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
	$('.select-all-group').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".group-chk-box").prop('checked', true);
		}
		else {
			$(".group-chk-box").prop('checked',false);
		}
		$("#select_count").html($("input.group-chk-box:checked").length+" ");
	});
	$(".group-chk-box").on('click', function(e) {
		$("#select_count").html($("input.group-chk-box:checked").length+" ");

		if ($(this).is(':checked',true)) {
			$(".select-all-group").prop("checked", false);
		}
		else {
			$(".select-all-group").prop("checked", false);
		}
		if ($(".group-chk-box").not(':checked').length == 0) {
			$(".select-all-group").prop("checked", true);
		}

	});

	$('.hide_and_seek_group').prop("disabled", true);
	$('input.manage:checkbox').click(function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek_group').prop("disabled", false);
		} else {
			if ($('.group-chk-box').filter(':checked').length < 1){
				$('.hide_and_seek_group').attr('disabled',true);}
			}
		});
});
</script>

<script>
$(document).ready(function(){
	$(".group_drag_icon").mousedown(function(){
		$( "#sortable_group_row" ).sortable({
			update: function( event, ui ) {
				updateDisplayRowOrder();
			}
		});
	});
});
</script>

<script>
function updateDisplayRowOrder() {
	var selectedItem = [];
	
	$("tbody#sortable_group_row tr").each(function() {
		selectedItem.push($(this).data("id"));
	});
	
	var dataString = {
	    sort_order: selectedItem.join(",")
	};
	
	$.ajax({
		type: "POST",
		url: "ajax/service_groups/update_group_order.php",
		data: dataString,
		cache: false,
		success: function(data) {
	   	 	readEditServiceGroupRecords();
			readServiceGroupRecords();
	  	  	readServiceGroupSelectRecords();
		},
		error: function(xhr, status, error) {
			console.error("Error updating group order:", error);
		}
	});
}
</script>

<style>
.nevo1 { display:none; }
</style>

<?php

if (isset($_SESSION['id'])) {
    
    $data = '<table class="table table-bordered table-hover">
    <thead class="table-secondary">
        <tr>
            <th style="width:36px;" class="sorting_disabled" rowspan="1" colspan="1" aria-label="">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input select-all-group manage" id="select-all-group">
                    <label class="form-check-label" for="select-all-group"></label>
                </div>
            </th>
            <th>Service Groups <span class="badge bg-secondary count-group"></span></th>
            <th>Order</th>
            <th>Sort</th>
        </tr>
    </thead>
    <tbody id="sortable_group_row">';

    $query = "SELECT * FROM service_groups ORDER BY group_display_order ASC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
				$group_id = htmlspecialchars(strip_tags($row['group_id'] ?? ''));
				$group_name = htmlspecialchars(strip_tags($row['group_name'] ?? ''));
				$group_display_order = htmlspecialchars(strip_tags($row['group_display_order'] ?? ''));
                
                $data .= '<tr class="count-group-item" data-id="' . $group_id . '">
                <td style="width:3%;">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input group-chk-box manage" id="' . $group_id . '" data-serve-id="' . $group_id . '">
                        <label class="form-check-label" for="' . $group_id . '"></label>
                    </div>
                </td>
                <td>' . $group_name . '</td>
                <td>' . $group_display_order . '</td>
                <td class="group_drag_icon align-middle grab" width="3%">
                    <span class="btn btn-sm btn-light btn-outline handler ui-sortable-handle">
                        <i class="fas fa-arrows-alt"></i>
                    </span>
                </td>
                </tr>';
            }

            $data .= '</tbody></table>';
        } else {
            $data .= '</tbody></table>';

         	$data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 0px auto 0 !important;">
            <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2)" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
            <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="one success">Records empty!</p>
            <p class="complete">Service Groups not found!</p>';
        }

        mysqli_stmt_close($stmt);
    } else {
        exit();
    }

    echo $data;
}

mysqli_close($dbc);
?>