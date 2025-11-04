<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
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
	   	 	url: "ajax/spotlight/update_answer_display_order.php",
	    	data: dataString,
	   	 	cache: false,
	    	success: function(data){
	      	  	readAllspotlights();
			
				var toastTrigger = document.getElementById("sortable_answer_row")
				var toastLiveExample = document.getElementById("toast-spotlight-details-order")
				if (toastTrigger) {
		  			var toast = new bootstrap.Toast(toastLiveExample)
					toast.show()
		 	   	} 
			
	    	}
		});
	}
	</script>';
	
$data .='<form name="update_answers_form" id="update_answers_form">
	
	<table class="table mb-2">
		<thead class="table-light">
			<tr>
				<th scope="col">Add Nominees</th>
			</tr>
		</thead>
	</table>
	
	<div class="row g-2">
		<div class="col-md-8">
			<select class="selectpicker" id="spotlight_nominee" multiple name="spotlight_nominee[]" placeholder="Nominee Select" title="Please select at least one nominee" data-selected-text-format="count" data-actions-box="true" multiple>';
	
			if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
		
				$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
				$user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));

				if(checkRole('spotlight_admin')){
	   				$query = "SELECT * FROM users WHERE account_delete != '1' ORDER BY first_name ASC ";
	  		  	}

	   			if ($r = mysqli_query($dbc, $query)){
    				
					while ($row = mysqli_fetch_array($r)){

						$user = mysqli_real_escape_string($dbc, strip_tags($row['user']));
						$first_name = htmlspecialchars(strip_tags($row['first_name']));
						$last_name = htmlspecialchars(strip_tags($row['last_name']));

	          		  	$data .='<option value="'.$user.'">'.$first_name.' '. $last_name .'</option>';
	   				}
	 		   	}
	
   $data .='</select>
			<input type="hidden" id="add_spotlight_nominee_hidden_id" val="'.$inquiry_id.'"> 
		 </div>';
			
$data .='<div class="col-md-4">
			<button type="button" class="btn btn-pink w-100 shadow-sm" id="assign-spotlight-user" onclick="addSpotlightNominee('.$inquiry_id.');" style="height:47px;">
		  	 	<i class="fa-solid fa-check-to-slot"></i> Add Nominee
		  	</button>
		</div>
	</div> 
</form>';
		
		}

	echo $data;

}
?>	

<?php
	
$query_two = "SELECT MAX(response_id) AS response_id FROM spotlight_response";
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

<script> VirtualSelect.init({ ele: '#spotlight_nominee' }); </script>