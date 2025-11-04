<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
	header("Location:../../index.php?msg1");
	exit();
}
?>

<?php

$data ='<div class="row mb-2">
			<div class="col-md-8">
				<select class="selectpicker" id="spotlight_user_assignment" multiple name="spotlight_user_assignment[]" placeholder="User Select" title="Please select at least one user" data-selected-text-format="count" data-actions-box="true" multiple>';
	
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
				<input type="hidden" id="assign_spotlight_enrollment_hidden_id" val="'.$inquiry_id.'">
			</div>';
			
   $data .='<div class="col-md-4">	
				<button type="button" class="btn btn-light-gray btn-lg w-100 shadow-sm" id="assign-spotlight-user" onclick="assignspotlightUser('.$inquiry_id.');">
					<i class="fa-solid fa-user-plus"></i> Assign
				</button>
			</div>
	</div>';
	
				}
		
	echo $data;

mysqli_close($dbc);
			
?>	

<script>
VirtualSelect.init({  
	ele: '#spotlight_user_assignment'
});
</script>