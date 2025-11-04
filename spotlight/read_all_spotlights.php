<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

function safeTooltipTitle($value, $default = 'Not set') {
    if (empty($value) || is_null($value) || trim($value) === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars(strip_tags($value));
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
	function countspotlightRecords(){
		var count = $('.count-spotlight-item').length;
	    $('.count-spotlight-records').html(count);
	} countspotlightRecords();

	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';

	if (count != 0){
		$(".my_spotlight_count").html(count);
	} else {
		$(".my_spotlight_count").html(count_zero);
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
			
				 if ($(".chk-box-spotlight-select").filter(":checked").length < 1){
					 $(".hide_and_seek").attr("disabled",true);}
				 }
			 });
		 });
		 </script>
	
		 <script>
		 $("#all_spotlights_table").DataTable({
			 aLengthMenu: [
			 	[100, 200, -1],
				[100, 200, "All"]
			 ],
			 responsive: true,
			 	"columnDefs": [
				{ "orderable": false, "targets": [0, 1, 10, 12]}],
				"order": []
		 });
		 </script>';
	 
$data .='<script>
			$(document).ready(function(){
		 	$(".spotlight_drag_icon").mousedown(function(){
		 		$("#sortable_spotlight_row").sortable({
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

		 	$("tbody#sortable_spotlight_row tr").each(function() {
		 		selectedItem.push($(this).data("id"));
		 	});
	
		 	var dataString = "sort_order="+selectedItem;
	
		 	$.ajax({
		  	  	type: "GET",
		   	 	url: "ajax/spotlight/update_inquiry_display_order.php", 
		   	  	data: dataString,
		  	   	cache: false,
		   	  	success: function(data){
		     		readAllSpotlights();
					var toastTrigger = document.getElementById("sortable_spotlight_row")
					var toastLiveExample = document.getElementById("toast-spotlight-manager-order")
					if (toastTrigger) {
						var toast = new bootstrap.Toast(toastLiveExample);
						toast.show()
				   	} 
				}
			});
		 }
		 </script>';	 
		
$data .='<div class="table-responsive p-1">
			<table class="table table-sm" id="all_spotlights_table" width="100%">
				<thead class="bg-light">
					<tr>
						<th> 
							<div class="form-check form-switch">
					  			<input type="checkbox" class="form-check-input select-all-spotlights" id="select-all-spotlights">
					  	  		<label class="form-check-label" for="select-all-spotlights"></label>
							</div>
						</th>
						<th class="">Sort</th>
				        <th class="">Date</th>
						<th class="">Icon</th>
						<th class="">Title</th>
						<th class="text-center">Voting</th>
						<th class="text-center">Showcase</th>
						<th class="text-center">Assigned</th>
						<th class="text-center">Nominees</th>
						<th class="text-center">Votes</th>
						<th class="text-center" style="width: 125px;">Progress</th>
						<th>Status</th>
						<th class="text-center">Details</th>
					</tr>
				</thead>
				<tbody id="sortable_spotlight_row">';
				
           		$query = "SELECT * FROM spotlight_inquiry ORDER BY inquiry_display_order ASC";	
				
             	if ($result = mysqli_query($dbc, $query)) {
					confirmQuery($result);
                            
					while ($row = mysqli_fetch_array($result)) {
						
						$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
						$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author'] ?? 'Unknown author'));
						$inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date'] ?? 'No date'));
						$inquiry_opening = htmlspecialchars(strip_tags($row['inquiry_opening'] ?? ''));
						$inquiry_closing = htmlspecialchars(strip_tags($row['inquiry_closing'] ?? ''));
						$showcase_start_date = htmlspecialchars(strip_tags($row['showcase_start_date'] ?? ''));
						$showcase_end_date = htmlspecialchars(strip_tags($row['showcase_end_date'] ?? ''));
						$inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name'] ?? 'Untitled Spotlight'));

						$inquiry_name_full = $inquiry_name;
						$inquiry_name_truncated = strlen($inquiry_name_full) > 20 ? substr($inquiry_name_full, 0, 20) . '...' : $inquiry_name_full;
						
						$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image'] ?? ''));
						$inquiry_nominee_image = htmlspecialchars(strip_tags($row['inquiry_nominee_image'] ?? ''));
						$nominee_name = htmlspecialchars(strip_tags($row['nominee_name'] ?? ''));
						$inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info'] ?? ''));
						$inquiry_status = htmlspecialchars(strip_tags($row['inquiry_status'] ?? 'Unknown'));
						
						if ($inquiry_image == ''){
							$inquiry_image = 'media/links/default_spotlight_image.png';
						} else {
							$inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
						}
						
						if ($inquiry_nominee_image == ''){
							$inquiry_nominee_image = 'img/profile_pic/default_img/pizza_panda.jpg';
						} else {
							$inquiry_nominee_image = htmlspecialchars(strip_tags($row['inquiry_nominee_image']));
						}
						
						$query_spotlight_answers = "SELECT * FROM spotlight_response WHERE question_id = '$inquiry_id'";

						if ($spotlight_answers_results = mysqli_query($dbc, $query_spotlight_answers)){
		           		 	$spotlight_answers_count = mysqli_num_rows($spotlight_answers_results);
	   					} 
						 
						$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";

						if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
		           		 	$enrolled_users = mysqli_num_rows($enrollment_results);
	   					} 
						
						$query_nominee_count = "SELECT * FROM spotlight_nominee WHERE question_id = '$inquiry_id'";

						if ($nominee_results = mysqli_query($dbc, $query_nominee_count)){
		           		 	$nominee_count = mysqli_num_rows($nominee_results);
	   					} else {
							$nominee_count = 0;
						}
						
						$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'";

						if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
		           		 	$ballot_votes = mysqli_num_rows($ballot_results);
	   					} 
						
						if (empty($enrolled_users)) {
							$participation_rate = 0;
							$participation_rate = $participation_rate * 100;
							$percentage_rate = number_format($participation_rate, 2, '.', '');
						} else {
							$participation_rate = $ballot_votes / $enrolled_users;
							$participation_rate = $participation_rate * 100;
							$percentage_rate = number_format($participation_rate, 2, '.', '');
						}
						
						$safe_creation_date = safeTooltipTitle($inquiry_creation_date, 'Creation date not set');
						$safe_opening_date = safeTooltipTitle($inquiry_opening, 'Voting open date not set');
						$safe_closing_date = safeTooltipTitle($inquiry_closing, 'Voting close date not set');
						$safe_showcase_start_date = safeTooltipTitle($showcase_start_date, 'Showcase start date not set');
						$safe_showcase_end_date = safeTooltipTitle($showcase_end_date, 'Showcase end date not set');
						
						$safe_enrolled_tooltip = "Users enrolled to participate in voting";
						$safe_nominee_tooltip = "People who can be voted for in this spotlight";
						
						$voting_dates_display = '';
						if (!empty($inquiry_opening) || !empty($inquiry_closing)) {
							if (!empty($inquiry_opening)) {
								$voting_dates_display .= '<div class="small text-secondary"><i class="fa-solid fa-calendar-check me-1 text-mint"></i>' . 
									(!empty($inquiry_opening) ? $inquiry_opening : '<span class="text-hot">undefined</span>') . '</div>';
							} else {
								$voting_dates_display .= '<div class="small text-hot"><i class="fa-solid fa-calendar-check me-1"></i>undefined</div>';
							}
							
							if (!empty($inquiry_closing)) {
								$voting_dates_display .= '<div class="small text-secondary"><i class="fa-solid fa-calendar-xmark me-1"></i>' . 
									(!empty($inquiry_closing) ? $inquiry_closing : '<span class="text-hot">undefined</span>') . '</div>';
							} else {
								$voting_dates_display .= '<div class="small text-hot"><i class="fa-solid fa-calendar-xmark me-1"></i>undefined</div>';
							}
						} else {
							$voting_dates_display = '<div class="small text-hot"><i class="fa-solid fa-calendar-check me-1"></i>undefined</div>
								<div class="small text-hot"><i class="fa-solid fa-calendar-xmark me-1"></i>undefined</div>';
						}
						
						$showcase_dates_display = '';
						if (!empty($showcase_start_date) || !empty($showcase_end_date)) {
							if (!empty($showcase_start_date)) {
								$showcase_dates_display .= '<div class="small text-secondary"><i class="fa-solid fa-trophy me-1 text-warning"></i>' . 
									(!empty($showcase_start_date) ? $showcase_start_date : '<span class="text-hot">undefined</span>') . '</div>';
							} else {
								$showcase_dates_display .= '<div class="small text-hot"><i class="fa-solid fa-trophy me-1"></i>undefined</div>';
							}
							
							if (!empty($showcase_end_date)) {
								$showcase_dates_display .= '<div class="small text-secondary"><i class="fa-solid fa-flag-checkered me-1 text-dark"></i>' . 
									(!empty($showcase_end_date) ? $showcase_end_date : '<span class="text-hot">undefined</span>') . '</div>';
							} else {
								$showcase_dates_display .= '<div class="small"><i class="fa-solid fa-flag-checkered me-1"></i>undefined</div>';
							}
						} else {
							$showcase_dates_display = '<div class="small text-hot"><i class="fa-solid fa-trophy me-1"></i>undefined</div>
								<div class="small text-hot"><i class="fa-solid fa-flag-checkered me-1"></i>undefined</div>';
						}
						
						$data .='<tr class="count-spotlight-item" data-id="'.$row['inquiry_id'].'">
						   		  	<td class="align-middle">
										<div class="form-check form-switch">
											<input type="checkbox" class="form-check-input chk-box-spotlight-select" data-spotlight-id="'.$inquiry_id.'" data-emp-user="" data-emp-fname="">
							    			<label class="form-check-label" for="'.$inquiry_id.'"></label>
										</div>
									</td>
									<td class="align-middle spotlight_drag_icon text-secondary" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
							        <td class="align-middle">
										<i class="fa-solid fa-clock text-secondary ms-2" data-bs-toggle="tooltip" data-bs-title="'.$safe_creation_date.'"></i>
									</td>
									<td class="align-middle"><img src="' . $inquiry_image . '" class="profile-photo"></td>
									<td class="align-middle" title="'.$inquiry_name_full.'">'.$inquiry_name_truncated.'</td>
									<td class="align-middle text-center">
										'.$voting_dates_display.'
									</td>
									<td class="align-middle text-center">
										'.$showcase_dates_display.'
									</td>
									<td class="align-middle text-center">
										<span class="badge bg-primary" data-bs-toggle="tooltip" data-bs-title="'.$safe_enrolled_tooltip.'">'.$enrolled_users.'</span>
									</td>
									<td class="align-middle text-center">
										<span class="badge bg-success" data-bs-toggle="tooltip" data-bs-title="'.$safe_nominee_tooltip.'">'.$nominee_count.'</span>
									</td>
									<td class="align-middle text-center">'.$ballot_votes.'</td>
									<td class="align-middle" style="width:125px;">
										<div class="progress" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Spotlight Completion Rate '.$percentage_rate.'%" role="progressbar" aria-label="Example with label" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
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
									
									$data .='<td class="align-middle text-center">
												<button type="button" class="btn btn-light-gray btn-sm" onclick="readSpotlightDetails('.$inquiry_id.')"><i class="fa-solid fa-gear"></i></button>
											 </td>
										</tr>';
					}
				}
								
				$data .='</tbody>
   			 </table>
		</div>';

echo $data;

}

mysqli_close($dbc);

?>

<script>
 $(document).ready(function() {
	 
	var $audit_chkboxes = $(".chk-box-spotlight-select");
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

	$('.select-all-spotlights').on('click', function(e) {
 	 	 if ($(this).is(':checked',true)) {
			 
			 $(".chk-box-spotlight-select").prop('checked', true);
			 
			 var count = $(".chk-box-spotlight-select:checked").length;
 			 var count_zero = '0';

 			 if (count != 0){
 				 $(".my_spotlight_count").html(count);
 			 } else {
 				 $(".my_spotlight_count").html(count_zero);
 			 }
			
		 } else { 
			 
			 $(".chk-box-spotlight-select").prop('checked',false);
			 
			 var count = '0';
 			 var count_zero = '0';

 			 if (count != 0){
 				 $(".my_spotlight_count").html(count);
 			 } else {
 				 $(".my_spotlight_count").html(count_zero);
 			 }
			
		 }
	});

	$(".chk-box-spotlight-select").on('click', function(e) {
		
		var count = $(".chk-box-spotlight-select:checked").length;
		var count_zero = '0';

		if (count != 0){
			$(".my_spotlight_count").html(count);
		} else {
			$(".my_spotlight_count").html(count_zero);
		}
		
		if ($(this).is(':checked',true)) {
			$(".select-all-spotlights").prop("checked", false);
		} else {
			$(".select-all-spotlights").prop("checked", false);
			window.history.pushState({},'','spotlight_manager.php');
		}

		if ($(".chk-box-spotlight-select").not(':checked').length == 0) {
			$(".select-all-spotlights").prop("checked", true);

		}
	}); 
	
	$('.hidden_spotlight_select').prop("disabled", true);
	
	if ($('.chk-box-spotlight-select').is(':checked',true)) {
		$('.hidden_spotlight_select').prop("disabled", false);
	} else {
		$('.hidden_spotlight_select').prop("disabled", true);
	}

	$('#all_spotlights_table').on("change", ".chk-box-spotlight-select", function(event) {
		
		if ($('.chk-box-spotlight-select').is(':checked',true)) {
			$('.hidden_spotlight_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_select').prop("disabled", true);
		}
	});
	
	$('#all_spotlights_table').on("change", ".select-all-spotlights", function(event) {
		
		if ($('.select-all-spotlights').is(':checked',true)) {
			$('.hidden_spotlight_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_select').prop("disabled", true);
		}
	});
	
	$('[data-bs-toggle="tooltip"]').each(function() {
        var $element = $(this);
        var title = $element.attr('data-bs-title');
        
   	 	if (!title || title === 'null' || title.trim() === '') {
            $element.attr('data-bs-title', 'Information not available');
        }
    });
    
	try {
        $('[data-bs-toggle="tooltip"]').tooltip();
        $('[data-bs-toggle="popover"]').popover();
    } catch (error) {
     	$('[data-bs-toggle="tooltip"]').each(function() {
            if (!$(this).attr('data-bs-title') || $(this).attr('data-bs-title').trim() === '') {
                $(this).removeAttr('data-bs-toggle').removeAttr('data-bs-title');
            }
        });
    }
});
</script>