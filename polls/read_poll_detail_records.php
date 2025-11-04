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
	width:100%;
}
</style>

<?php

if (isset($_SESSION['id'])) {

	$data ='<script>
    $(document).ready(function(){
        $(".response_key_checkbox").change(function() {
            var isChecked = $(this).is(":checked");
            var responseID = $(this).data("response-id");
			var value = isChecked ? 1 : 0;
            $("#response_key_"+responseID).val(value);
			$(".response_key_checkbox").not(this).prop("checked", false);
            $(".response_key_checkbox").not(this).closest("tr").find("input[name^=\'response_key\']").val(0);
        });

        $(document).on("change", ".response_key_checkbox", function() {
            var isChecked = $(this).is(":checked");
            var responseID = $(this).data("response-id");
			if (isChecked) {
                $(".response_key_checkbox").not(this).prop("checked", false);
                $(".response_key_checkbox").not(this).closest("tr").find("input[name^=\'response_key\']").val(0);
            }
        });
    });
    </script>
	
	<script>
	$(document).ready(function(){
		$(".answer_move_icon").mousedown(function(){
	 		$("#sortable_answer_row").sortable({
				axis: "y",
				helper: function(e, tr)
				  {
				    var $originals = tr.children();
				    var $helper = tr.clone();
				    
					$helper.children().each(function(index){
						$(this).width($originals.eq(index).outerWidth());
					  	$(this).css("background-color", "white");
				    });
					
				    return $helper;
				  },
				
				placeholder: "ui-state-highlights",
				update: function(event, ui) {
	 				updateDocAnswerDisplayOrder();
	 			}
	 		});
		});
	});
	</script>

	<script>
	function updateDocAnswerDisplayOrder() {
		var selectedItem = new Array();
  	  	$("tbody#sortable_answer_row tr").each(function() {
			selectedItem.push($(this).data("id"));
		});
  		var dataString = "sort_order="+selectedItem;
  	
		$.ajax({
	    	type: "GET",
	   	 	url: "ajax/polls/update_answer_display_order.php",
	    	data: dataString,
	   	 	cache: false,
	    	success: function(data){
	      	  	readAllPolls();
				var toastTrigger = document.getElementById("sortable_answer_row")
				var toastLiveExample = document.getElementById("toast-poll-details-order")
				if (toastTrigger) {
		  			var toast = new bootstrap.Toast(toastLiveExample)
					toast.show()
		 	   	} 
			}
		});
	}
	</script>';
	
$data .='<form name="update_answers_form" id="update_answers_form">
	<div class="table-responsive">
		<table class="table mb-3" id="add_new_answer_row" width="100%">
			<thead class="table-light">
				<tr>
					<th>Sort</th>
					<th>Answer</th>
					<th>Info</th>
					<th scope="col" class="text-center">Votes</th>
					<th scope="col" class="">Key</th>
					<th scope="col" class="text-center">Delete</th>
				</tr>
			</thead>
			<tbody id="sortable_answer_row">';
			
			if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
  			  	$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
				$query = "SELECT * FROM poll_response WHERE question_id = '$inquiry_id' ORDER BY response_display_order ASC";	
		
			} else {
				$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
				$query = "SELECT * FROM poll_response WHERE question_id = '$inquiry_id' ORDER BY response_display_order ASC";	
			}

			if (!$result = mysqli_query($dbc, $query)) {
				exit();
			}
			
			if (mysqli_num_rows($result) > 0) {
			  
				while ($row = mysqli_fetch_assoc($result)) {
					$response_id = mysqli_real_escape_string($dbc, strip_tags($row['response_id']));
					$response_key = htmlspecialchars(strip_tags($row['response_key']));
					$response_answer = htmlspecialchars(strip_tags($row['response_answer']));
					$response_info = htmlspecialchars(strip_tags($row['response_info']));
					
					$query_ballot_answer_count = "SELECT * FROM poll_ballot WHERE answer_id = '$response_id'";

					if ($ballot_answer_results = mysqli_query($dbc, $query_ballot_answer_count)){
	           		 	$ballot_answer_votes = mysqli_num_rows($ballot_answer_results);
   					} 
					
					$data .='<tr class="remove_row" id="tab_'.$response_id.'" data-id="'.$row['response_id'].'">
						<td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
						<td class="align-middle"> 
							<input type="hidden" class="form-control" name="question_id[]" value="'.$inquiry_id.'">
							<input type="hidden" class="form-control" name="response_type[]" value="single_select">
							<input type="text" class="form-control" name="response_answer[]" id="" value="'.$response_answer.'">
						</td>
						<td class="align-middle">
							<input type="text" class="form-control" name="response_info[]" id="" value="'.$response_info.'">
						</td>
						<td class="align-middle text-center">'.$ballot_answer_votes.'</td>';
						
						$data .='<td class="align-middle" style="width:5%"> 
									<div class="form-check form-switch">
										<input type="checkbox" class="form-check-input response_key_checkbox" data-response-id="'.$response_id.'" id="response_key_checkbox_'.$response_id.'"'.($response_key == 1 ? ' checked' : '').'>
										<input type="hidden" name="response_key[]" id="response_key_'.$response_id.'" value="'.$response_key.'">
										<label class="form-check-label" for="response_key_checkbox_'.$response_id.'"></label>
									</div>
						</td>';
						
						$data .='<td class="align-middle text-center" style="width:5%">
							<button type="button" class="btn bg-hot btn-sm btn-remove-row" onclick="deletePollResponse('.$response_id.');">
								<i class="fa-solid fa-circle-xmark"></i>
							</button>
						</td>
					</tr>
					
					<input type="hidden" class="form-control" name="response_id[]" value="'.$response_id.'">';  

				} 

			} else {
				
				$data .='<tr class="" data-id="">
							<td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
							<td class="align-middle">
								<input type="hidden" class="form-control" name="question_id[]" value="'.$inquiry_id.'">
								<input type="text" class="form-control" name="response_answer[]" id="" value="">
							</td>
							<td class="align-middle">
								<input type="text" class="form-control" name="response_info[]" id="" value="">
							</td>
   							<td class="align-middle text-center">0</td>
      						<td class="align-middle text-center" style="width:5%">
								<button type="button" class="btn btn-light-gray btn-sm" disabled><i class="fa-solid fa-circle-xmark"></i></button>
							</td>
					 	 </tr>
						 <input type="hidden" class="form-control" name="response_id[]" value="">';
			}
		
			$data .='</tbody>
			</table>
		</div>
	
		<div class="row g-2"> 
			<div class="col-md-8">
				<button type="button" class="btn btn-orange btn-lg w-100 shadow-sm" name="submit" id="update-poll-answers">
					<i class="fa-solid fa-cloud"></i> Update
				</button>
			</div>
			<div class="col-md-4">
				<button type="button" class="btn btn-light-gray btn-lg w-100 shadow-sm" name="add-new-poll-answer" id="add-new-poll-answer"><i class="fa-solid fa-circle-plus"></i> Row</button>
			</div>
		</div>
		
	</form>';

	echo $data;

	}

?>	

<?php
	
$query_two = "SELECT MAX(response_id) AS response_id FROM poll_response";
$result_two = mysqli_query($dbc, $query_two);

if (mysqli_num_rows($result_two) > 0) {
    $row = mysqli_fetch_assoc($result_two);
    $highest_response_id = $row["response_id"];
} else {
	$highest_response_id = 0;
}

$new_response_id = $highest_response_id + 1;
mysqli_close($dbc);	
?>

<script>
$(document).ready(function(){   
	
	var i = 1;  

	$('#add-new-poll-answer').click(function(){ 
		$("#sortable_answer_row").sortable({
			axis: "y",
			helper: function(e, tr)
			  {
			    var $originals = tr.children();
			    var $helper = tr.clone();
			    
				$helper.children().each(function(index){
					$(this).width($originals.eq(index).width());
				  	$(this).css("background-color", "white");
			    });
				
			    return $helper;
			  },
			
			placeholder: "ui-state-highlights",
			update: function(event, ui) {
 				updateDocAnswerDisplayOrder();
 			}
 		});
						
		i++;
					
		var newRow = '<tr id="row' + i + '" class="dynamic-added-row">' +
			'<td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>' +
			'<td class="align-middle">' +
			'<input type="hidden" class="form-control" name="question_id[]" value="<?php echo $inquiry_id; ?>">' +
			'<input type="hidden" class="form-control" name="response_id[]" value="<?php echo $new_response_id; ?>">' +
			'<input type="hidden" class="form-control" name="response_type[]" value="single_select">' +
			'<input type="text" name="response_answer[]" placeholder="" class="form-control" required></td>' +
			'<td class="align-middle">' +
			'<input type="text" name="response_info[]" placeholder="" class="form-control" required></td>' +
			'<td class="align-middle text-center">0</td>' +
			'<td class="align-middle text-center">' +
			'<div class="form-check form-switch">' +
			'<input type="checkbox" class="form-check-input response_key_checkbox" id="response_key_checkbox_' + i + '" data-response-id="' + i + '">' +
			'<input type="hidden" name="response_key[]" id="response_key_' + i + '" value="">' +
			'<label class="form-check-label" for="response_key_checkbox_' + i + '"></label>' +
			'</div>' +
			'</td>' +
			'<td class="align-middle text-center" style="width:5%">' +
			'<button type="button" name="remove" id="' + i + '" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button>' + 
			'</td>' +
			'</tr>';

		$('#add_new_answer_row').append(newRow);
	
	});
	
	$('#update-poll-answers').click(function(){   
		$("#update-poll-answers").prop("disabled", true);
		$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');         
		$(".response_key_checkbox").each(function() {
			var responseId = $(this).data("response-id");
			var isChecked = $(this).is(":checked");
			$("#response_key_" + responseId).val(isChecked ? "1" : "0");
		});
		
		var formData = $('#update_answers_form').serialize();
			
		$.ajax({  
			method: "POST",
			url: "ajax/polls/update_poll_answers.php",
			data: formData,
			success: function(data) {
				i = 1;
				$('.dynamic-added-row').remove();
				$('#update_answers_form')[0].reset();
				$("#update-poll-answers").prop("disabled", false);
				$("#update-poll-answers").html('<i class="fa-solid fa-cloud"></i> Update');
				
				$.post("ajax/polls/read_poll_detail_records.php", {
					inquiry_id: <?php echo $inquiry_id; ?>
				}, 
					function (data, status) {
						$(".view_poll_details_content").html(data);
					}
				);
				
				var toastTrigger = document.getElementById("update_answers_form")
				var toastLiveExample = document.getElementById("toast-poll-answer-updated")
				if (toastTrigger) {
	  				var toast = new bootstrap.Toast(toastLiveExample)
					toast.show()
	 	   		}	
			}  
		});  
	});	
});
</script>