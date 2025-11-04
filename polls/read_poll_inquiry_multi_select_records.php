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
		$(".answer_move_icon").mousedown(function(){
	 		$("#sortable_create_multi_answer_row").sortable({
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
	</script>';

    $data .= '<form name="create_multi_answers_form" id="create_multi_answers_form">';

    $query = "SELECT * FROM poll_inquiry ORDER BY inquiry_id DESC LIMIT 1";

    if ($stmt = mysqli_prepare($dbc, $query)) {
      	mysqli_stmt_execute($stmt);
      	$result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {
				$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
                $inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
			}

            $data .= '<div class="table-responsive">
                        <table class="table mb-3" id="add_new_multi_answer_row">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Sort</th>
                                    <th scope="col">Answer</th>
                                    <th scope="col">Info</th>
                                    <th scope="col" class="text-center">Votes</th>
                                    <th scope="col">Edit</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white" id="sortable_create_multi_answer_row">
                                <tr> 
                                    <td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
                                    <td class="align-middle">
                                        <input type="hidden" class="form-control" name="question_id[]" value="' . htmlspecialchars($inquiry_id) . '">
                                        <input type="text" class="form-control" name="response_answer[]" id="" value="">
                                    </td>
                                    <td class="align-middle">
                                        <input type="text" class="form-control" name="response_info[]" id="" value="">
                                    </td>
                                    <td class="align-middle text-center">0</td>
                                    <td class="align-middle text-center" style="width:5%">
                                        <button type="button" name="remove" id="" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row g-2"> 
                        <div class="col-md-8">
                            <button type="button" class="btn btn-primary btn-lg w-100 shadow-sm" name="submit" id="create-poll-multi-answers"><i class="fa-regular fa-circle-check"></i> Create Answers</button>
                        </div> 
                        <div class="col-md-4">
                         	<button type="button" class="btn btn-light-gray btn-lg w-100 shadow-sm" name="add-poll-multi-answer" id="add-poll-multi-answer"><i class="fa-solid fa-circle-plus"></i> Row</button>
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

        mysqli_stmt_close($stmt);
    } else {
        $data .= '<p class="error">Database query error!</p>';
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

	$('#add-poll-multi-answer').click(function(){  
		$("#sortable_create_multi_answer_row").sortable({
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
					
		$('#add_new_multi_answer_row').append('<tr id="row'+i+'" class="dynamic-added-row"><td class="answer_move_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td><td class="align-middle"><input type="hidden" class="form-control" name="question_id[]" value="<?php echo $inquiry_id; ?>"><input type="text" name="response_answer[]" placeholder="" class="form-control" required></td><td class="align-middle"><input type="text" name="response_info[]" placeholder="" class="form-control" required></td><td class="align-middle text-center">0</td><td class="align-middle text-center" style="width:5%"><button type="button" name="remove" id="'+i+'" class="btn bg-hot btn-sm btn_remove"><i class="fa-solid fa-circle-xmark"></i></button></td></tr>');
						
	});
	
	$('#create-poll-multi-answers').click(function(){
		
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
			})

		} else {
			$("#create-poll-multi-answers").prop("disabled", true);
			$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
		
			$.ajax({ 
				method:"POST",
				url: "ajax/polls/create_poll_multi_answers.php",
				data: $('#create_multi_answers_form').serialize(), 
				type:'json',
				success:function(data){
					i = 1;
					$('.dynamic-added-row').remove();
					$('#create_multi_answers_form')[0].reset();
					$("#create-poll-multi-answers").prop("disabled", false);
					$("#create-poll-multi-answers").html('<i class="fa-solid fa-cloud"></i> Save');
					$("#create_respons_tab").fadeOut("fast", function(){
						$("#create_respons_tab").removeClass("active show");
						$("#create_inquiry_tab").fadeIn('fast', function(){
							$("#create_inquiry_tab").addClass("active show");
						});
					});
				
					var toastTrigger = document.getElementById("create_inquiry_poll_question_id")
					var toastLiveExample = document.getElementById("toast-create-poll-answers")
			
					if (toastTrigger) {
		  				var toast = new bootstrap.Toast(toastLiveExample)
						toast.show()
		 	   		} 
				}
			});
		}
	});
});
</script>