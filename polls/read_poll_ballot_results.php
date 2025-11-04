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

<script>
$(document).ready(function(){
	function countPollBallotRecords(){
		var count = $('.count-poll-ballot-item').length;
	    $('.count-poll-ballot-records').html(count);
	} countPollBallotRecords();
});
</script>

<script>
$(document).ready(function(){
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_poll_ballot_count").html(count);
	} else {
		$(".my_poll_ballot_count").html(count_zero);
	}
});	
</script>

<?php

if (isset($_SESSION['id'])) {

	$data ='<script>
			 $(document).ready(function(){
				 $(".hidden_poll_ballot_select").prop("disabled", true);
				 $("input:checkbox").click(function() {
					 if ($(this).is(":checked")) {
						 $(".hidden_poll_ballot_select").prop("disabled", false);
					 } else {
					 if ($(".chk-box-poll-ballot-select").filter(":checked").length < 1){
						 $(".hidden_poll_ballot_select").attr("disabled",true);}
					 }
				  });
			 });
			 </script>
	
			 <script>
			 $("#poll_ballot_table").DataTable({
				
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

    $data .= '<div class="table-responsive p-1">
                <table class="table" id="poll_ballot_table" width="100%">
                    <thead class="bg-light">
                        <tr>
                            <th>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input select-all-poll-ballots" id="select-all-poll-ballots">
                                    <label class="form-check-label" for="select-all-poll-ballots"></label>
                                </div>
                            </th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>User</th>
                            <th>Poll Name</th>
                            <th>Poll Answer</th>
                        </tr>
                    </thead>
                    <tbody>';

    if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {

        $inquiry_id = $_POST['inquiry_id'];
        
        $query = "SELECT * FROM poll_ballot 
                  JOIN users ON poll_ballot.ballot_user = users.user 
                  JOIN poll_inquiry ON poll_ballot.question_id = poll_inquiry.inquiry_id 
                  JOIN poll_response ON poll_ballot.answer_id = poll_response.response_id 
                  WHERE poll_ballot.question_id = ?";

        if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $inquiry_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
					$profile_pic = htmlspecialchars(strip_tags($row['profile_pic']));
                    $first_name = htmlspecialchars(strip_tags($row['first_name']));
                    $last_name = htmlspecialchars(strip_tags($row['last_name']));
					$full_name = $first_name . ' ' . $last_name;

                    $ballot_id = htmlspecialchars(strip_tags($row['ballot_id']));
                    $ballot_user = htmlspecialchars(strip_tags($row['ballot_user']));
                    $question_id = htmlspecialchars(strip_tags($row['question_id']));
                    $poll_name = htmlspecialchars(strip_tags($row['inquiry_name']));
                    $response_answer = htmlspecialchars(strip_tags($row['response_answer']));

                    $data .= '<tr class="count-poll-ballot-item ballot_row" data-id="' . $row['ballot_id'] . '">
                                <td class="align-middle" style="width:5%;">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input chk-box-poll-ballot-select" data-ballot-id="' . $ballot_id . '">
                                        <label class="form-check-label" for="' . $ballot_id . '"></label>
                                    </div>
                                </td>
                                <td class="align-middle" style="width:5%;"><img src="' . $profile_pic . '" class="profile-photo"></td>
                                <td class="align-middle">' . $full_name . '</td>
                                <td class="align-middle">' . $ballot_user . '</td>
                                <td class="align-middle">' . $poll_name . '</td>
                                <td class="align-middle">' . $response_answer . '</td>
                              </tr>';
                }
            }

            mysqli_stmt_close($stmt);
        } else {
            exit();
        }

    } else {
		$data .= '<tr><td colspan="6">No inquiry ID provided.</td></tr>';
    }

    $data .= '</tbody>
            </table>
        </div>';

    echo $data;
}

mysqli_close($dbc);
?>

<script>
 $(document).ready(function() {
	var $ballot_chkboxes = $(".chk-box-poll-ballot-select");
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

	$('.select-all-poll-ballots').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".chk-box-poll-ballot-select").prop('checked', true);
			var count = $(".chk-box-poll-ballot-select:checked").length;
 			var count_zero = '0';

 			if (count != 0){
 				$(".my_poll_ballot_count").html(count);
 			} else {
 				$(".my_poll_ballot_count").html(count_zero);
 			}
		 } else { 
			 $(".chk-box-poll-ballot-select").prop('checked',false);
			 var count = '0';
 			 var count_zero = '0';

 			 if (count != 0){
 				 $(".my_poll_ballot_count").html(count);
 			 } else {
 				 $(".my_poll_ballot_count").html(count_zero);
 			 }
		 }
	});

	$(".chk-box-poll-ballot-select").on('click', function(e) {
		var count = $(".chk-box-poll-ballot-select:checked").length;
		var count_zero = '0';

		if (count != 0){
			$(".my_poll_ballot_count").html(count);
		} else {
			$(".my_poll_ballot_count").html(count_zero);
		}
		
		if ($(this).is(':checked',true)) {
			$(".select-all-poll-ballots").prop("checked", false);
		} else {
			$(".select-all-poll-ballots").prop("checked", false);
		}

		if ($(".chk-box-poll-ballot-select").not(':checked').length == 0) {
			$(".select-all-poll-ballots").prop("checked", true);

		}
	}); 
	
	$('.hidden_poll_ballot_select').prop("disabled", true);
	
	if ($('.chk-box-poll-ballot-select').is(':checked',true)) {
		$('.hidden_poll_ballot_select').prop("disabled", false);
	} else {
		$('.hidden_poll_ballot_select').prop("disabled", true);
	}

	$('#poll_ballot_table').on("change", ".chk-box-poll-ballot-select", function(event) {
		if ($('.chk-box-poll-ballot-select').is(':checked',true)) {
			$('.hidden_poll_ballot_select').prop("disabled", false);
		} else {
			$('.hidden_poll_ballot_select').prop("disabled", true);
		}
	});
	
	$('#poll_ballot_table').on("change", ".select-all-poll-ballots", function(event) {
		if ($('.select-all-poll-ballots').is(':checked',true)) {
			$('.hidden_poll_ballot_select').prop("disabled", false);
		} else {
			$('.hidden_poll_ballot_select').prop("disabled", true);
		}
	});
});
</script>