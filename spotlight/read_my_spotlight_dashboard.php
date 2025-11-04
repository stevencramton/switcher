<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
	header("Location:../../index.php?msg1");
	exit();
}
?>
 
<?php

if (isset($_SESSION['id'])) {

$data ='<div class="col-lg-12 mx-auto">
			<div class="row gx-3 stat-cards">';

		    $spotlight_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
				
			// Count ALL enrollments where spotlight actually exists
			$query_my_spotlight_enrollment_count = "SELECT DISTINCT sa.spotlight_id FROM spotlight_assignment sa 
			                                         JOIN spotlight_inquiry si ON sa.spotlight_id = si.inquiry_id 
			                                         WHERE sa.assignment_user = '$spotlight_user'";

			if ($my_enrollment_results = mysqli_query($dbc, $query_my_spotlight_enrollment_count)){
       		 	$my_spotlight_enrollments = mysqli_num_rows($my_enrollment_results);
			} else {
				$my_spotlight_enrollments = 0;
			}
			
			// Count completed spotlights (where user has voted)
			$query_my_spotlight_completed_count = "SELECT DISTINCT sb.question_id FROM spotlight_ballot sb
			                                       JOIN spotlight_inquiry si ON sb.question_id = si.inquiry_id
			                                       WHERE sb.ballot_user = '$spotlight_user'";

			if ($my_completed_results = mysqli_query($dbc, $query_my_spotlight_completed_count)){
       		 	$my_completed = mysqli_num_rows($my_completed_results);
			} else {
				$my_completed = 0;
			}
			
			$not_started = max(0, $my_spotlight_enrollments - $my_completed);
			
			// To match display logic: assignment_read = 1 shows warning badge (appears unread)
			$query_my_spotlight_status = "SELECT * FROM spotlight_assignment 
			                             WHERE assignment_user = '$spotlight_user' 
			                             AND assignment_read = '1'"; 
			
			if ($my_status_results = mysqli_query($dbc, $query_my_spotlight_status)){
       		 	$my_spotlight_status = mysqli_num_rows($my_status_results);
			} else {
				$my_spotlight_status = 0;
			}
			
			$data .='<div class="col-md-6 col-xl-3 mb-3">
 	 					<article class="stat-cards-item border shadow-sm mb-3">
 		 					<div class="stat-cards-icon warning">
 			 					<i class="fa-solid fa-bell fa-xl"></i>
 		 					</div>
 							<div class="stat-cards-info">
 			 					<p class="stat-cards-info__num">'.$my_spotlight_status.'</p>
 			 					<p class="stat-cards-info__title">New spotlights</p>
 		 					</div>
 	 					</article>
  					 </div>
					 <div class="col-md-6 col-xl-3 mb-3">
			  			<article class="stat-cards-item border shadow-sm mb-3">
			  		  		<div class="stat-cards-icon primary">
			  		  			<i class="fa-solid fa-user-plus fa-xl"></i>
			  		  		</div>
			  		  	  	<div class="stat-cards-info">
			  		  			<p class="stat-cards-info__num">'.$my_spotlight_enrollments.'</p>
			  					<p class="stat-cards-info__title">My enrollments</p>
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
			  					<p class="stat-cards-info__title">Not started</p>
			  				</div>
			  			</article>
					</div>
 					<div class="col-md-6 col-xl-3 mb-3">
		  				<article class="stat-cards-item border shadow-sm mb-3">
		  					<div class="stat-cards-icon success">
		  						<i class="fa-solid fa-user-check fa-xl"></i>
		  					</div>
		  					<div class="stat-cards-info">
		  						<p class="stat-cards-info__num">'.$my_completed.'</p>
		  						<p class="stat-cards-info__title">My completed</p>
		  					</div>
		  				</article>
				 	 </div>';
		
	$data .='</div>
		</div>';

echo $data;

}

mysqli_close($dbc);