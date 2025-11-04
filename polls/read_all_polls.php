<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}
?>

<style>
.ui-state-highlights {
	background: #f8f9fa !important;
	height: 50px;	
}
</style>

<script>
$(document).ready(function(){
	function countPollRecords(){
		var count = $('.count-poll-item').length;
	    $('.count-poll-records').html(count);
	} countPollRecords();
});
</script>

<script>
$(document).ready(function(){
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_poll_count").html(count);
	} else {
		$(".my_poll_count").html(count_zero);
	}					
});	
</script>

<?php

if (isset($_SESSION['id'])) {

	$data ='<script>
			 $(document).ready(function(){
				 $(".hide_and_seek").prop("disabled", true);
				 $("input:checkbox").click(function() {
					 if ($(this).is(":checked")) {
						 $(".hide_and_seek").prop("disabled", false);
					 } else {
						 if ($(".chk-box-poll-select").filter(":checked").length < 1){
							 $(".hide_and_seek").attr("disabled",true);
						 }
					 }
				 });
			 });
			 </script>
	
			 <script>
			 $("#all_polls_table").DataTable({
				 aLengthMenu: [
				 	[100, 200, -1],
					[100, 200, "All"]
				 ],
				 responsive: true,
				 	"columnDefs": [
					{ "orderable": false, "targets": [0, 1, 9, 11]}],
					"order": []
			 });
			 </script>';
	 
	$data .='<script>
			 $(document).ready(function(){
			 	$(".poll_drag_icon").mousedown(function(){
			 		$("#sortable_poll_row").sortable({
						axis: "y",
						helper: function(e, tr)
						  {
						    var $originals = tr.children();
						    var $helper = tr.clone();
						    $helper.children().each(function(index)
						    {
						      $(this).width($originals.eq(index).width());
							  $(this).css("background-color", "white");
						    });
						    return $helper;
						  },
					
						placeholder: "ui-state-highlights",
						update: function(event, ui) {
			 				updateDisplayRowOrder();
			 			}
			 		});
			 	});
			 });
			 </script>

			 <script>
			 function updateDisplayRowOrder() {
				 var selectedItem = new Array();
				 $("tbody#sortable_poll_row tr").each(function() {
			 		 selectedItem.push($(this).data("id"));
				 });
	
			 	 var dataString = "sort_order="+selectedItem;
	
			 	 $.ajax({
					 type: "GET",
					 url: "ajax/polls/update_inquiry_display_order.php",
					 data: dataString,
			 		 cache: false,
					 success: function(data){
		     			 readAllPolls();
						 var toastTrigger = document.getElementById("sortable_poll_row")
						 var toastLiveExample = document.getElementById("toast-poll-manager-order")
						 if (toastTrigger) {
							 var toast = new bootstrap.Toast(toastLiveExample);
							 toast.show()
						 } 
					 }
				});
			 }
			 </script>';

$data .='<div class="table-responsive p-1">
			<table class="table table-sm" id="all_polls_table" width="100%">
				<thead class="bg-light">
					<tr>
						<th> 
							<div class="form-check form-switch">
					  			<input type="checkbox" class="form-check-input select-all-polls" id="select-all-polls">
					  	  		<label class="form-check-label" for="select-all-polls"></label>
							</div>
						</th>
						<th class="">Sort</th>
				        <th class="">Date</th>
						<th class="">Image</th>
						<th>Poll</th>
						<th>Question</th>
						<th class="text-center">Answers</th>
						<th class="text-center">Assigned</th>
						<th class="text-center">Votes</th>
						<th class="text-center" style="width: 125px;">Progress</th>
						<th>Status</th>
						<th class="text-center">Details</th>
					</tr>
				</thead>
				<tbody id="sortable_poll_row">';

				$query = "SELECT * FROM poll_inquiry ORDER BY inquiry_display_order ASC";
				$result = mysqli_query($dbc, $query);
				confirmQuery($result);

				while ($row = mysqli_fetch_array($result)) {
					$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
					$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author']));
					$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date']));
					$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
					$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
					$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
					$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info']));
					$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status']));

					if ($inquiry_image == ''){
						$inquiry_image = 'media/links/default_poll_image.png';
					} else {
						$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
					}

					$query_poll_answers = "SELECT * FROM poll_response WHERE question_id = ?";
					$stmt_poll_answers = mysqli_prepare($dbc, $query_poll_answers);
					mysqli_stmt_bind_param($stmt_poll_answers, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_poll_answers);
					$poll_answers_results = mysqli_stmt_get_result($stmt_poll_answers);
					$poll_answers_count = mysqli_num_rows($poll_answers_results);
					
					$query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";
					$stmt_enrollment_count = mysqli_prepare($dbc, $query_enrollment_count);
					mysqli_stmt_bind_param($stmt_enrollment_count, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_enrollment_count);
					$enrollment_results = mysqli_stmt_get_result($stmt_enrollment_count);
					$enrolled_users = mysqli_num_rows($enrollment_results);
					
					$query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";
					$stmt_ballot_count = mysqli_prepare($dbc, $query_ballot_count);
					mysqli_stmt_bind_param($stmt_ballot_count, 'i', $inquiry_id);
					mysqli_stmt_execute($stmt_ballot_count);
					$ballot_results = mysqli_stmt_get_result($stmt_ballot_count);
					$ballot_votes = mysqli_num_rows($ballot_results);

					if (empty($enrolled_users)) {
						$participation_rate = 0;
					} else {
						$participation_rate = ($ballot_votes / $enrolled_users) * 100;
					}
					$percentage_rate = number_format($participation_rate, 2, '.', '');

					$data .='<tr class="count-poll-item" data-id="'.$row['inquiry_id'].'">
						   		  	<td class="align-middle" style="width:5%;">
										<div class="form-check form-switch">
											<input type="checkbox" class="form-check-input chk-box-poll-select" data-poll-id="'.$inquiry_id.'" data-emp-user="" data-emp-fname="">
							    			<label class="form-check-label" for="'.$inquiry_id.'"></label>
										</div>
									</td>
									<td class="align-middle poll_drag_icon text-secondary text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
							        <td class="align-middle text-center" style="width:6%;">
										<i class="fa-solid fa-clock text-secondary ms-2" data-bs-toggle="tooltip" data-bs-title="'.$inquiry_creation_date.'" data-bs-content=""></i>
									</td>
									<td class="align-middle text-center" style="width:6%;"><img src="' . $inquiry_image . '" class="profile-photo"></td>
									<td class="align-middle">'.$inquiry_name.'</td>
									<td class="align-middle">'.$inquiry_question.'</td>
									<td class="align-middle text-center" style="width:7%;">'.$poll_answers_count.'</td>
									<td class="align-middle text-center" style="width:7%;">'.$enrolled_users.'</td>
									<td class="align-middle text-center" style="width:7%;">'.$ballot_votes.'</td>
									<td class="align-middle" style="width:125px;">
										<div class="progress" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Poll Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
									  		<div class="progress-bar progress-bar-striped bg-info" style="width: '.$percentage_rate.'%"></div>
										</div>
									</td>';
								
		                        	if($inquiry_status == "Active"){
									
		                    			$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-mint w-100" style="font-size:14px"><i class="far fa-check-circle"></i> Active </span></td>';
							
		                        	} else if($inquiry_status == "Paused"){
									
		                    			$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-hot w-100" style="font-size:14px"><i class="fa-regular fa-circle-pause"></i> Paused </span></td>';
									
									} else if($inquiry_status == "Closed"){
									
		                    			$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-concrete w-100" style="font-size:14px"><i class="fa-solid fa-check-to-slot"></i> Closed </span></td>';
									
									} else {}
									
									$data .='<td class="align-middle text-center" style="width:5%">
												<button type="button" class="btn btn-light-gray btn-sm" onclick="readPollDetails('.$inquiry_id.')"><i class="fa-solid fa-gear"></i></button>
											 </td>';
				}

				$data .='</tr>
					</tbody>
   			 </table>
		</div>';

echo $data;

}

mysqli_close($dbc);

?>

<script>
$(document).ready(function() {
	var $audit_chkboxes = $(".chk-box-poll-select");
 	var lastChecked = null;

 	$audit_chkboxes.click(function(e) {
		if (!lastChecked) {
 			lastChecked = this;
 			return;
 		}
		if (e.shiftKey) {
			var start = $audit_chkboxes.index(this);
 	    	var end = $audit_chkboxes.index(lastChecked);
			$audit_chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop("checked", lastChecked.checked);
 		}
		lastChecked = this;
	});

	$('.select-all-polls').on('click', function(e) {
 	 	 if ($(this).is(':checked',true)) {
			 $(".chk-box-poll-select").prop('checked', true);
			 var count = $(".chk-box-poll-select:checked").length;
 			 var count_zero = '0';
			 if (count != 0){
 				 $(".my_poll_count").html(count);
 			 } else {
 				 $(".my_poll_count").html(count_zero);
 			 }
		 } else { 
			 $(".chk-box-poll-select").prop('checked',false);
			 var count = '0';
			 var count_zero = '0';
			 if (count != 0){
 				 $(".my_poll_count").html(count);
 			 } else {
 				 $(".my_poll_count").html(count_zero);
 			 }
		 }
	});

	$(".chk-box-poll-select").on('click', function(e) {
		var count = $(".chk-box-poll-select:checked").length;
		var count_zero = '0';
		if (count != 0){
			$(".my_poll_count").html(count);
		} else {
			$(".my_poll_count").html(count_zero);
		}
		if ($(this).is(':checked',true)) {
			$(".select-all-polls").prop("checked", false);
		} else {
			$(".select-all-polls").prop("checked", false);
			window.history.pushState({},'','poll_manager.php');
		}
		if ($(".chk-box-poll-select").not(':checked').length == 0) {
			$(".select-all-polls").prop("checked", true);
		}
	}); 
	
	$('.hidden_poll_select').prop("disabled", true);
	if ($('.chk-box-poll-select').is(':checked',true)) {
		$('.hidden_poll_select').prop("disabled", false);
	} else {
		$('.hidden_poll_select').prop("disabled", true);
	}

	$('#all_polls_table').on("change", ".chk-box-poll-select", function(event) {
		if ($('.chk-box-poll-select').is(':checked',true)) {
			$('.hidden_poll_select').prop("disabled", false);
		} else {
			$('.hidden_poll_select').prop("disabled", true);
		}
	});
	
	$('#all_polls_table').on("change", ".select-all-polls", function(event) {
		if ($('.select-all-polls').is(':checked',true)) {
			$('.hidden_poll_select').prop("disabled", false);
		} else {
			$('.hidden_poll_select').prop("disabled", true);
		}
	});
});
</script>

<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>