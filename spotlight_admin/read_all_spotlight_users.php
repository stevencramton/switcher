<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}
?>

<script>
$(document).ready(function(){
	function countSpotlightUserRecords(){
		var count = $('.count-spotlight-user-item').length;
	    $('.count-spotlight-user-records').html(count);
	} countSpotlightUserRecords();
});
</script>

<?php

if (isset($_SESSION['id'])) {
	if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
		$data = '';
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));

$data .='<script>
		 $(document).ready(function(){
			 $(".hide_and_seek").prop("disabled", true); 
			 $("input:checkbox").click(function() {
				 if ($(this).is(":checked")) {
					 $(".hide_and_seek").prop("disabled", false);
				 } else {
				 if ($(".chk-box-spotlight-user-select").filter(":checked").length < 1){
					 $(".hide_and_seek").attr("disabled",true);}
				 }
			 });
		 });
		 </script>
	
		 <script>
		 $("#all_spotlight_users_table").DataTable({
			 aLengthMenu: [
			 	[100, 200, -1],
				[100, 200, "All"]
			 ],
			 responsive: true,
			 	"columnDefs": [
				{ "orderable": false, "targets": [0, 1]}],
				"order": []
		 });
		 </script>';

$data .='<div class="row">
			<div class="col-sm-4 mb-3">
				<select class="form-select hidden_spotlight_user_select" id="spotlight-user-action-select">
					<option value="">Select an Action</option>
					<option value="enroll_spotlight_user">Enroll</option>
					<option value="remove_spotlight_user">Remove</option>
				</select>
			</div>
			<div class="col-sm-6 mb-3">
				<button type="button" class="btn btn-outline-secondary hidden_spotlight_user_select me-1" onclick="applyspotlightUserAction('.$inquiry_id.');">
					Apply <small>( <span class="my_spotlight_users_count">0</span> )</small>
				</button>
			</div>
		</div>';
			
$data .='<div class="table-responsive p-1">
			<table class="table table-sm" id="all_spotlight_users_table" width="100%">
				<thead class="bg-light">
					<th> 
						<div class="form-check form-switch">
					  		<input type="checkbox" class="form-check-input select-all-spotlight-users" id="select-all-spotlight-users">
					  	  	<label class="form-check-label" for="select-all-spotlight-users"></label>
						</div>
					</th>
					<th>Photo</th>
					<th>Name</th>
					<th>Username</th>
					<th>Enrolled</th>
					<th>Progress</th>
					<th class="text-center">Spotlight Status</th>
				</thead>
				<tbody id="sortable_spotlight_row">';
				
				$query = "SELECT id, switch_id, first_name, last_name, account_locked, user, profile_pic FROM users WHERE account_delete = 0";
            
				if ($result = mysqli_query($dbc, $query)) {
	        		confirmQuery($result);
	        		
					while ($row = mysqli_fetch_array($result)) {
						
					$id = htmlspecialchars(strip_tags($row['id']));
					$switch_id = htmlspecialchars(strip_tags($row['switch_id']));
					$first_name = htmlspecialchars(strip_tags($row['first_name']));
					$last_name = htmlspecialchars(strip_tags($row['last_name']));
					$status = htmlspecialchars(strip_tags($row['account_locked']));
					$user = htmlspecialchars(strip_tags($row['user']));
					$profile_pic = htmlspecialchars(strip_tags($row['profile_pic']));
					
					if ($status >= 1) {
						$status = '<i class="fas fa-lock" style="color:#b54398;"></i>';
					} else {
						$status = '<i class="fas fa-check-circle text-success"></i>';
					}
				
	                $data .='<tr id="user_info">
			   		  	<td class="align-middle" style="width:5%;">
							<div class="form-check form-switch">
								<input type="checkbox" class="form-check-input chk-box-spotlight-user-select" data-spotlight-user-id="'.$inquiry_id.'" data-emp-user="'.$user.'" data-emp-fname="">
				    			<label class="form-check-label" for=""></label>
							</div>
						</td>
						
						<td class="align-middle" style="width:5%;"><img src="' . $profile_pic . '" class="profile-photo"></td>
						<td class="align-middle" style="width:16%;">'. $first_name . ' ' . $last_name . '</td>
						<td class="align-middle" style="width:12%;">'. $user . '</td>';
						
					$data .='<td class="align-middle" style="width:8%;">';
						 
					if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_two = "SELECT * FROM spotlight_assignment WHERE assignment_status = 'Yes' AND spotlight_id = '$inquiry_id' AND assignment_user = '$user'"; 
	
					} else {
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_two = "SELECT * FROM spotlight_assignment WHERE assignment_status = 'Yes' AND spotlight_id = '$inquiry_id' AND assignment_user = '$user'"; 
					}
			
					if (!$result_two = mysqli_query($dbc, $query_two)) {
						exit();
					}
			
					if (mysqli_num_rows($result_two) > 0) {
		  
						while ($row_two = mysqli_fetch_assoc($result_two)) {
					
							$spotlight_name = htmlspecialchars(strip_tags($row_two['spotlight_name']));
							$assignment_status = htmlspecialchars(strip_tags($row_two['assignment_status']));
							
							if($assignment_status == 'Yes'){
								
								 $data .='<span class="badge bg-cool-ice w-100" style="font-size:14px"><i class="far fa-check-circle"></i> '.$assignment_status.'</span>';
							} 
						 
						}
						 
					} else {
						
						$data .='<span class="badge bg-light-gray w-100" style="font-size:14px"> No</span>';
												
					}
					 
					$data .='</td>';
						
					$data .='<td class="align-middle" style="width:15%;">';
						 
					if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_three = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id' AND ballot_user = '$user'"; 
					} else { 
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_three = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id' AND ballot_user = '$user'"; 
					} 
			
					if (!$result_three = mysqli_query($dbc, $query_three)) {
						exit();
					}
			
					if (mysqli_num_rows($result_three) > 0) {
		  
						$data .='<div class="progress" role="progressbar" aria-label="Example with label" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
									<div class="progress-bar bg-orange" style="width: 100%">100%</div>
								</div>
								<!-- <span class="badge bg-light-gray w-100" style="font-size:14px"> Completed</span> -->';
					} else {
						
						$data .='<div class="progress position-relative">
     					   			<div class="progress-bar progress-bar-success" role="progressbar" aria- valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width:0%; color:gray;">
    									<div class="justify-content-center d-flex position-absolute w-100">0%</div> 
  									</div>
								 </div>
								 <!-- <span class="badge bg-light-gray w-100" style="font-size:14px"> Not Started</span> -->';
					}
					 
					$data .='</td>';
						
					if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_four = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'"; 
					} else { 
						$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
						$query_four = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'"; 
					}
					
					if ($result_four = mysqli_query($dbc, $query_four)) {
						confirmQuery($result_four);
                            
						while ($row_four = mysqli_fetch_array($result_four)) {
					
							$inquiry_status = mysqli_real_escape_string($dbc, strip_tags($row_four['inquiry_status']));	
					
							if($inquiry_status == "Active"){
					
            					$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-mint w-100" style="font-size:14px"><i class="far fa-check-circle"></i> Active </span></td>';
			
                			} else if($inquiry_status == "Paused"){
					
            					$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-hot w-100" style="font-size:14px"><i class="fa-regular fa-circle-pause"></i> Paused </span></td>';
					
							} else if($inquiry_status == "Closed"){
					
            					$data .='<td class="align-middle" style="width:10%;"><span class="badge bg-concrete w-100" style="font-size:14px"><i class="fa-solid fa-folder-closed"></i> Closed </span></td>';
					
							} else {}
						
						}
					}
						
					$data .='</tr> <!-- End #user_info table row -->';
						  
					}
	     	   }
			   
			   $data .='</tbody>
                    </table>
				</div>';
				
				echo $data;
				
			}
}

mysqli_close($dbc);

?>

<script>
$(document).ready(function() {
	var $audit_chkboxes = $(".chk-box-spotlight-user-select");
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

	$('.select-all-spotlight-users').on('click', function(e) {
 	 	 if ($(this).is(':checked',true)) {
			 $(".chk-box-spotlight-user-select").prop('checked', true);
			 var count = $(".chk-box-spotlight-user-select:checked").length;
 			 var count_zero = '0';

 			 if (count != 0){
 				 $(".my_spotlight_users_count").html(count);
 			 } else {
 				 $(".my_spotlight_users_count").html(count_zero);
 			 }
		 } else { 
			 $(".chk-box-spotlight-user-select").prop('checked',false);
			 var count = '0';
			 var count_zero = '0';

 			 if (count != 0){
 				 $(".my_spotlight_users_count").html(count);
 			 } else {
 				 $(".my_spotlight_users_count").html(count_zero);
 			 }
		 }
	});

	$(".chk-box-spotlight-user-select").on('click', function(e) {
		var count = $(".chk-box-spotlight-user-select:checked").length;
		var count_zero = '0';

		if (count != 0){
			$(".my_spotlight_users_count").html(count);
		} else {
			$(".my_spotlight_users_count").html(count_zero);
		}
		
		if ($(this).is(':checked',true)) {
			$(".select-all-spotlight-users").prop("checked", false);
		} else {
			$(".select-all-spotlight-users").prop("checked", false);
			window.history.pushState({},'','spotlight_manager.php');
		}

		if ($(".chk-box-spotlight-user-select").not(':checked').length == 0) {
			$(".select-all-spotlight-users").prop("checked", true);
		}
	}); 
	
	$('.hidden_spotlight_user_select').prop("disabled", true);
	
	if ($('.chk-box-spotlight-user-select').is(':checked',true)) {
		$('.hidden_spotlight_user_select').prop("disabled", false);
	} else {
		$('.hidden_spotlight_user_select').prop("disabled", true);
	}

	$('#all_spotlight_users_table').on("change", ".chk-box-spotlight-user-select", function(event) {
		if ($('.chk-box-spotlight-user-select').is(':checked',true)) {
			$('.hidden_spotlight_user_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_user_select').prop("disabled", true);
		}
	});
	
	$('#all_spotlight_users_table').on("change", ".select-all-spotlight-users", function(event) {
		if ($('.select-all-spotlight-users').is(':checked',true)) {
			$('.hidden_spotlight_user_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_user_select').prop("disabled", true);
		}
	});	
});
</script>

<script>
$(document).ready(function(){
	$("#inquiry_spotlight_image_link_edit").change(function () {
		$('#inquiry_spotlight_image_preview_edit').attr('src', $('#inquiry_spotlight_image_link_edit').val());
	}) 
						
	$(".reset_spotlight_edit_link_image").click(function() {
		$('#inquiry_spotlight_image_link_edit').val('');
		$('#inquiry_spotlight_image_preview_edit').attr('src', 'media/links/default_spotlight_image.png');
	});
});
</script>