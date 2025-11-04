<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
 	 header("Location:../../index.php?msg1");
}
?>

<script>
$(document).ready(function(){
	function countspotlightBallotRecords(){
		var count = $('.count-spotlight-ballot-item').length;
	    $('.count-spotlight-ballot-records').html(count);
	} countspotlightBallotRecords();

	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_spotlight_ballot_count").html(count);
	} else {
		$(".my_spotlight_ballot_count").html(count_zero);
	}
});	
</script>

<?php

if (isset($_SESSION['id'])) {

$data ='<script>
		$(document).ready(function(){
			$(".hidden_spotlight_ballot_select").prop("disabled", true);
			$("input:checkbox").click(function() {
				if ($(this).is(":checked")) {
					$(".hidden_spotlight_ballot_select").prop("disabled", false);
				} else {
				if ($(".chk-box-spotlight-ballot-select").filter(":checked").length < 1){
					$(".hidden_spotlight_ballot_select").attr("disabled",true);}
				}
			 });
		 });
		 
		 $("#spotlight_ballot_table").DataTable({
			 aLengthMenu: [
			 	[100, 200, -1],
				[100, 200, "All"]
			 ], 
			 responsive: true,
			 	"columnDefs": [
				{ "orderable": false, "targets": [0]}],
				"order": []
		 });
		 </script>';	 
		
$data .='<div class="table-responsive p-1">
			<table class="table" id="spotlight_ballot_table" width="100%">
				<thead class="bg-light">
					<tr>
						<th> 
							<div class="form-check form-switch">
					  			<input type="checkbox" class="form-check-input select-all-spotlight-ballots" id="select-all-spotlight-ballots">
					  	  		<label class="form-check-label" for="select-all-spotlight-ballots"></label>
							</div>
						</th>
						<th>Photo</th>
						<th>Name</th>
						<th>User</th>
						<th>Spotlight</th>
						<th>Answer</th>
					</tr>
				</thead>
				<tbody>';
				
				if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
				    $inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id'] ?? ''));
				} else {
				    $inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id'] ?? ''));
				}
				
				$query = "SELECT * FROM spotlight_ballot 
				          JOIN users ON spotlight_ballot.ballot_user = users.user 
				          JOIN spotlight_inquiry ON spotlight_ballot.question_id = spotlight_inquiry.inquiry_id 
				          JOIN spotlight_nominee ON spotlight_ballot.answer_id = spotlight_nominee.assignment_id 
				          WHERE spotlight_ballot.question_id = '$inquiry_id'";
				
				if (!$result = mysqli_query($dbc, $query)) {
					exit();
				}
			
				if (mysqli_num_rows($result) > 0) {
			  
					while ($row = mysqli_fetch_assoc($result)) {
						
						$profile_pic = htmlspecialchars(strip_tags($row['profile_pic']));
						$first_name = htmlspecialchars(strip_tags($row['first_name']));
						$last_name = htmlspecialchars(strip_tags($row['last_name']));
						$full_name = $first_name . ' ' . $last_name;
						$ballot_id = htmlspecialchars(strip_tags($row['ballot_id']));
						$ballot_user = htmlspecialchars(strip_tags($row['ballot_user']));
						$question_id = htmlspecialchars(strip_tags($row['question_id']));
						$spotlight_name = htmlspecialchars(strip_tags($row['inquiry_name']));
						$assignment_user = htmlspecialchars(strip_tags($row['assignment_user']));

						$query_user = "SELECT first_name, last_name FROM users WHERE user = '$assignment_user'";
						$result_user = mysqli_query($dbc, $query_user);

						if ($result_user && mysqli_num_rows($result_user) > 0) {
						    $user_row = mysqli_fetch_assoc($result_user);
						    $assignment_first_name = htmlspecialchars($user_row['first_name']);
						    $assignment_last_name = htmlspecialchars($user_row['last_name']);
							
							$assignment_full_name = $assignment_first_name . ' ' . $assignment_last_name;
						} else {
						 	$assignment_first_name = 'Unknown';
						    $assignment_last_name = 'User';
							$assignment_full_name = $assignment_first_name . ' ' . $assignment_last_name;
						}
				
						$data .='<tr class="count-spotlight-ballot-item ballot_row" data-id="'.$row['ballot_id'].'">
						   		  	<td class="align-middle" style="width:5%;">
										<div class="form-check form-switch">
											<input type="checkbox" class="form-check-input chk-box-spotlight-ballot-select" data-ballot-id="'.$ballot_id.'">
							    			<label class="form-check-label" for="'.$ballot_id.'"></label>
										</div> 
									</td>
									
									<td class="align-middle" style="width:5%;"><img src="' . $profile_pic . '" class="profile-photo"></td>
									<td class="align-middle">'.$full_name.'</td> 
									<td class="align-middle">'.$ballot_user.'</td> 
									<td class="align-middle">'.$spotlight_name.'</td>
									<td class="align-middle">'.$assignment_full_name.'</td>
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
	var $ballot_chkboxes = $(".chk-box-spotlight-ballot-select");
	var lastChecked = null;

 	$ballot_chkboxes.click(function(e) {
		if (!lastChecked) {
 			lastChecked = this;
 			return;
 		}

 		if (e.shiftKey) {
			var start = $ballot_chkboxes.index(this);
 	    	var end = $ballot_chkboxes.index(lastChecked);
			$ballot_chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop("checked", lastChecked.checked);
 		}

 		lastChecked = this;
	});

	$('.select-all-spotlight-ballots').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".chk-box-spotlight-ballot-select").prop('checked', true);
			var count = $(".chk-box-spotlight-ballot-select:checked").length;
			var count_zero = '0';

			if (count != 0){
				$(".my_spotlight_ballot_count").html(count);
			} else {
				$(".my_spotlight_ballot_count").html(count_zero);
			}
		} else { 
			$(".chk-box-spotlight-ballot-select").prop('checked',false);
			var count = '0';
 			var count_zero = '0';
			if (count != 0){
				$(".my_spotlight_ballot_count").html(count);
			} else {
 				$(".my_spotlight_ballot_count").html(count_zero);
			}
		}
	});

	$(".chk-box-spotlight-ballot-select").on('click', function(e) {
		var count = $(".chk-box-spotlight-ballot-select:checked").length;
		var count_zero = '0';

		if (count != 0){
			$(".my_spotlight_ballot_count").html(count);
		} else {
			$(".my_spotlight_ballot_count").html(count_zero);
		}
		
		if ($(this).is(':checked',true)) {
			$(".select-all-spotlight-ballots").prop("checked", false);
		} else {
			$(".select-all-spotlight-ballots").prop("checked", false); 
		}

		if ($(".chk-box-spotlight-ballot-select").not(':checked').length == 0) {
			$(".select-all-spotlight-ballots").prop("checked", true);
		}
	}); 
	
	$('.hidden_spotlight_ballot_select').prop("disabled", true);
	
	if ($('.chk-box-spotlight-ballot-select').is(':checked',true)) {
		$('.hidden_spotlight_ballot_select').prop("disabled", false);
	} else {
		$('.hidden_spotlight_ballot_select').prop("disabled", true);
	}

	$('#spotlight_ballot_table').on("change", ".chk-box-spotlight-ballot-select", function(event) {
		if ($('.chk-box-spotlight-ballot-select').is(':checked',true)) {
			$('.hidden_spotlight_ballot_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_ballot_select').prop("disabled", true);
		}
	});
	
	$('#spotlight_ballot_table').on("change", ".select-all-spotlight-ballots", function(event) {
		if ($('.select-all-spotlight-ballots').is(':checked',true)) {
			$('.hidden_spotlight_ballot_select').prop("disabled", false);
		} else {
			$('.hidden_spotlight_ballot_select').prop("disabled", true);
		}
	});
});
</script>