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

	$data ='<div class="row stat-cards">';

	if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== ""){
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
	} else {
		$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
		$query = "SELECT * FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
	}

	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}

	if (mysqli_num_rows($result) > 0) {

		while ($row = mysqli_fetch_assoc($result)) {
			$inquiry_id = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_id']));
			$inquiry_status = mysqli_real_escape_string($dbc, strip_tags($row['inquiry_status']));	
			$query_enrollment_count = "SELECT * FROM spotlight_assignment WHERE spotlight_id = '$inquiry_id'";

			if ($enrollment_results = mysqli_query($dbc, $query_enrollment_count)){
       		 	$enrolled_users = mysqli_num_rows($enrollment_results);
			} 
			
			$query_ballot_count = "SELECT * FROM spotlight_ballot WHERE question_id = '$inquiry_id'";

			if ($ballot_results = mysqli_query($dbc, $query_ballot_count)){
       		 	$ballot_votes = mysqli_num_rows($ballot_results);
			} 
			
			$not_started = $enrolled_users - $ballot_votes;

			$data .='<div class="col-md-6 col-xl-3 mb-3">
						<article class="stat-cards-item border shadow-sm">
							<div class="stat-cards-icon primary">
    							<i class="fa-solid fa-users fa-xl"></i>
 	   	 					</div>
  	  						<div class="stat-cards-info">
       	 						<p class="stat-cards-info__num">'.$enrolled_users.'</p>
       	 						<p class="stat-cards-info__title">Total enrollments</p>
   	 						</div>
 						</article>
					</div>
					<div class="col-md-6 col-xl-3 mb-3">
						<article class="stat-cards-item border shadow-sm">
      	  					<div class="stat-cards-icon warning">
           	 					<i class="fa-solid fa-user-check fa-xl"></i>
       	 					</div>
       	 					<div class="stat-cards-info">
          	  					<p class="stat-cards-info__num">'.$ballot_votes.'</p>
        						<p class="stat-cards-info__title">Total completed</p>
      	  					</div>
    					</article>
  					</div>
					<div class="col-md-6 col-xl-3 mb-3">
     					<article class="stat-cards-item border shadow-sm mb-3">
    						<div class="stat-cards-icon danger">
        						<i class="fa-solid fa-hourglass fa-xl"></i>
      	  					</div>
       	 					<div class="stat-cards-info">
    							<p class="stat-cards-info__num">'.$not_started.'</p>
      		 					<p class="stat-cards-info__title">Total not started</p>
 		   					</div>
						</article>
					</div>
  					<div class="col-md-6 col-xl-3 mb-3">
						<article class="stat-cards-item border shadow-sm mb-3">';
		
    					if($inquiry_status == "Active"){
		
							$data .='<div class="stat-cards-icon success">
     		   				 			<i class="fa-regular fa-circle-check fa-xl"></i>
   									 </div>
      	  							 <div class="stat-cards-info">
      		  						 	<p class="stat-cards-info__num">Active</p>
          	 							<p class="stat-cards-info__title">Spotlight status</p>
   		 							 </div>';

    					} else if($inquiry_status == "Paused"){
		
							$data .='<div class="stat-cards-icon danger">
     		   				 			<i class="fa-regular fa-circle-pause fa-xl"></i>
									 </div>
      	  							 <div class="stat-cards-info">
      		  						 	<p class="stat-cards-info__num">Paused</p>
          	 						 	<p class="stat-cards-info__title">Spotlight status</p>
   		 							 </div>';
		
						} else if($inquiry_status == "Closed"){
		
							$data .='<div class="stat-cards-icon bg-light-gray">
     		  				  			<i class="fa-solid fa-check-to-slot fa-xl"></i>
   									 </div>
      	  							 <div class="stat-cards-info">
      		  						 	<p class="stat-cards-info__num">Closed</p>
          	 							<p class="stat-cards-info__title">Spotlight status</p>
   		 							 </div>';
		
						} else if($inquiry_status == "Completed"){
		
							$data .='<div class="stat-cards-icon primary">
     		   				 			<i class="fa-solid fa-check-to-slot fa-xl"></i>
   									 </div>
      	  							 <div class="stat-cards-info">
      		  						 	<p class="stat-cards-info__num">Completed</p>
          	 							<p class="stat-cards-info__title">Spotlight status</p>
   		 							 </div>';
		
						} else {}
		
							$data .='</article>
								</div>';

		}
	}

	$data .='</div>';

echo $data;

}

mysqli_close($dbc);

?>