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

if (isset($_SESSION['id'])) {
	
	$data ='<div class="shadow-sm border rounded p-3 bg-white mb-3">';
	$data .='<form name="create_spotlight_form" id="create_spotlight_form">';
	$query = "SELECT * FROM spotlight_inquiry ORDER BY inquiry_id DESC LIMIT 1";

	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
		
	if (mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id'] ?? ''));
			$inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question'] ?? ''));
		} 
					
		$data .='<table class="table mb-2">
						<thead class="table-light">
							<tr>
								<th scope="col">Add Nominees</th>
							</tr>
						</thead>
					</table>
	
					<div class="row g-2">
						<div class="col-md-8"> 
							<select class="selectpicker" id="spotlight_nominees" multiple name="spotlight_nominees[]" placeholder="Nominee Select" title="Please select at least one nominee" data-selected-text-format="count" data-actions-box="true" multiple>';
	
							$user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));

								if(checkRole('spotlight_admin')){
					   				$query = "SELECT * FROM users WHERE account_delete != '1' ORDER BY first_name ASC ";
					  		  	}

					   			if ($r = mysqli_query($dbc, $query)){
    								while ($row = mysqli_fetch_array($r)){
										$user = mysqli_real_escape_string($dbc, strip_tags($row['user'] ?? ''));
										$first_name = htmlspecialchars(strip_tags($row['first_name'] ?? ''));
										$last_name = htmlspecialchars(strip_tags($row['last_name'] ?? ''));
										$data .='<option value="'.$user.'">'.$first_name.' '. $last_name .'</option>';
					   				}
					 		   	}
	
				   $data .='</select>
							<input type="hidden" id="add_spotlight_nominee_one_hidden_id" val="'.$inquiry_id.'"> 
						 </div>';
			
				$data .='<div class="col-md-4">
							 <button type="button" class="btn btn-purple-haze w-100 shadow-sm" onclick="addSpotlightNomineeCreate('.$inquiry_id.');" style="height:56px;margin-top:1px;">
							 	<i class="fa-solid fa-user-plus"></i> Add Nominees
							 </button>
						</div>
					</div>';
		
				} else {
        
					$data .='<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
  				  		<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  						<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
						</svg>
						<p class="one success">Records empty!</p>
						<p class="complete">Records not found!</p>';
				}
	
				$data .='</form> </div> 
					<div class="shadow-sm border rounded bg-white w-100 p-3 mb-3 mt-3">
						<div class="row gx-3">
							<div class="col-md-6">
								<button type="button" class="btn btn-hot w-100" onclick="cancelSpotlightDetails();">
									<i class="fa-regular fa-circle-xmark"></i> Close
								</button>
							</div>
							<div class="col-md-6">
								<a href="spotlight_admin.php" class="btn btn-secondary w-100"> <i class="fa-solid fa-up-right-from-square"></i> Visit Spotlight Admin</a>
							</div>
						</div>
					</div> 
					<div class="" style="height:250px;"></div>';

	echo $data;

}

mysqli_close($dbc);

?>

<script>
VirtualSelect.init({  
	ele: '#spotlight_nominees'
});
</script>