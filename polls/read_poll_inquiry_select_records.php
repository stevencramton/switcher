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

    $data = '<script>
 	$(document).ready(function(){
        $(".answer_move_icon").mousedown(function(){
            $("#sortable_create_answer_row").sortable({
                axis: "y",
                helper: function(e, tr) {
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
    </script>';

    $data .= '<form name="create_answers_form" id="create_answers_form">';

    $query = "SELECT inquiry_id, inquiry_question FROM poll_inquiry ORDER BY inquiry_id DESC LIMIT 1";
    if ($stmt = mysqli_prepare($dbc, $query)) {
      	mysqli_stmt_execute($stmt);
      	mysqli_stmt_bind_result($stmt, $inquiry_id, $inquiry_question);
      	
		if (mysqli_stmt_fetch($stmt)) {
            $inquiry_id = htmlspecialchars(strip_tags($inquiry_id));
            $inquiry_question = htmlspecialchars(strip_tags($inquiry_question));

            $data .= '<div class="table-responsive">
                <table class="table mb-3" id="add_new_answer_row">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Sort</th>
                            <th scope="col">Answer</th>
                            <th scope="col">Info</th>
                            <th scope="col" class="text-center">Votes</th>
                            <th scope="col" class="text-center">Key</th>
                            <th scope="col" class="text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white" id="sortable_create_answer_row">
                        <tr> 
                            <td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
                            <td class="align-middle">
                                <input type="hidden" class="form-control" name="question_id[]" value="' . $inquiry_id . '">
                                <input type="hidden" class="form-control" name="response_type[]" value="single_select">
                                <input type="text" class="form-control" name="response_answer[]" id="" value="">
                            </td>
                            <td class="align-middle">
                                <input type="text" class="form-control" name="response_info[]" id="" value="">
                            </td>
                            <td class="align-middle text-center">0</td>
                            <td class="align-middle text-center"> 
                                <input type="checkbox" class="form-check-input flex-shrink-0 mb-1 response_key_check" name="response_key[]" value="1" style="font-size: 1.375em;">
                            </td>
                            <td class="align-middle text-center" style="width:5%">
                                <button type="button" name="remove" id="" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="row g-2"> 
                <div class="col-md-8">
                    <button type="button" class="btn btn-primary btn-lg w-100 shadow-sm" name="submit" id="create-poll-answers"><i class="fa-regular fa-circle-check"></i> Create Answers</button>
                </div> 
                <div class="col-md-4">
                    <button type="button" class="btn btn-light-gray btn-lg w-100 shadow-sm" name="add-poll-answer" id="add-poll-answer"><i class="fa-solid fa-circle-plus"></i> Row</button>
                </div>
            </div>';
        } else {
            $data .= '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
            <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
            <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="one success">Records empty!</p>
            <p class="complete">Service Groups not found!</p><script>$("#service_product").prop("disabled",true);</script>';
        }
        // Close the statement
        mysqli_stmt_close($stmt);
    }

    $data .= '</form>';

    echo $data;

}

mysqli_close($dbc);

?>

<script>
$(document).on('click', '.btn_remove', function(){  
	var button_id = $(this).attr("id");
	$('#row'+button_id+'').addClass('bg-hot-o');
	$('#row'+button_id+'').fadeOut(1000, function() {
		$('#row'+button_id+'').remove();
	});
});  
</script>

<script>
$(document).ready(function(){ 
	var i = 1;

	$('#add-poll-answer').click(function(){  
		$("#sortable_create_answer_row").sortable({
			axis: "y",
			helper: function(e, tr) {
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
	    	'<input type="hidden" class="form-control" name="question_id[' + i + ']" value="<?php echo $inquiry_id; ?>">' +
	    	'<input type="hidden" class="form-control" name="response_type[' + i + ']" value="single_select">' +
	    	'<input type="text" name="response_answer[' + i + ']" placeholder="" class="form-control" required>' +
	    	'</td>' +
	    	'<td class="align-middle">' +
	    	'<input type="text" name="response_info[' + i + ']" placeholder="" class="form-control" required>' +
	    	'</td>' +
	    	'<td class="align-middle text-center">0</td>' +
	    	'<td class="align-middle text-center">' +
	    	'<input type="checkbox" class="form-check-input flex-shrink-0 mb-1 response_key_check" name="response_key[' + i + ']" value="1" style="font-size: 1.375em;">' +
	    	'</td>' +
	    	'<td class="align-middle text-center" style="width:5%">' +
	    	'<button type="button" name="remove" id="' + i + '" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button>' +
	    	'</td>' +
	    	'</tr>';
			
			$('#add_new_answer_row').append(newRow);
	
		});
	
		$('#create-poll-answers').click(function() {
		
	   	 	var response_key = [];

			$('input[name="response_key[]"]').each(function() {
		  	  	var value = $(this).is(":checked") ? 1 : 0;
		  	  	response_key.push(value);
			});
	   
	  	  	var response_answer_text = [];

	  	  	$("input[name='response_answer[]']").each(function() {
	    		var value = $(this).val();
	    		if (value) {
	      		  	response_answer_text.push(value);
	    		}
	  	  	});

	  	  	if (response_answer_text.length === 0) {
	    		swal.fire({
	      		  icon: 'error',
	      		  title: 'Error...',
	      		  text: 'Please enter an answer',
	    		});
	    		return;
	  	  	}

	  	  	$.ajax({
	    		method: 'POST',
	    		url: 'ajax/polls/create_poll_answers.php',
	    		data: $('#create_answers_form').serialize(),
	    		type: 'json',
	    		success: function(data) {
	     			i = 1;
	      			$('.dynamic-added-row').remove();
	      		  	$('#create_answers_form')[0].reset();

	      		  	swal.fire({
	        			icon: 'success',
	        			title: 'Success!',
	        			text: 'Your answers have been saved',
	     		   	});

	      		 	$("#create_respons_tab").fadeOut("fast", function() {
	        			$("#create_respons_tab").removeClass("active show");
	        			$("#create_inquiry_tab").fadeIn('fast', function() {
	          			  	$("#create_inquiry_tab").addClass("active show");
	        			});
	      		  	});
				}
	 	   	});
		});
	});
</script>

<script>
$(document).ready(function(){
	$('#add_new_answer_row').on('change', 'input[type="checkbox"]', function() {
		var checkboxes = $('#add_new_answer_row input[type="checkbox"]');
 	   	checkboxes.not(this).prop('checked', false);
  	});
});
</script>