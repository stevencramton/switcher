<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_voter')){
	header("Location:../../index.php?msg1");
	exit();
}

if (isset($_SESSION['id'])) {

$data ='<ul class="list-group mb-2">';

 	$spotlight_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
	
	$query_my_spotlight_count = "SELECT DISTINCT spotlight_id FROM spotlight_assignment WHERE assignment_user = '$spotlight_user'";

	if ($my_spotlight_results = mysqli_query($dbc, $query_my_spotlight_count)){
	 	$my_spotlight_count = mysqli_num_rows($my_spotlight_results);
	} else {
		$my_spotlight_count = 0;
	}
	
	$query_my_spotlight_active = "SELECT DISTINCT sa.spotlight_id FROM spotlight_assignment sa 
	                              JOIN spotlight_inquiry si ON sa.spotlight_id = si.inquiry_id 
	                              WHERE sa.assignment_user = '$spotlight_user' 
	                              AND si.inquiry_status = 'Active'"; 

	if ($my_active_results = mysqli_query($dbc, $query_my_spotlight_active)){
 		$my_spotlight_active = mysqli_num_rows($my_active_results);
	} else {
		$my_spotlight_active = 0;
	}
	
	$query_my_spotlight_paused = "SELECT DISTINCT sa.spotlight_id FROM spotlight_assignment sa 
	                              JOIN spotlight_inquiry si ON sa.spotlight_id = si.inquiry_id 
	                              WHERE sa.assignment_user = '$spotlight_user' 
	                              AND si.inquiry_status = 'Paused'"; 

	if ($my_paused_results = mysqli_query($dbc, $query_my_spotlight_paused)){
 		$my_spotlight_paused = mysqli_num_rows($my_paused_results);
	} else {
		$my_spotlight_paused = 0;
	}
	
	$query_my_spotlight_closed = "SELECT DISTINCT sa.spotlight_id FROM spotlight_assignment sa 
	                              JOIN spotlight_inquiry si ON sa.spotlight_id = si.inquiry_id 
	                              WHERE sa.assignment_user = '$spotlight_user' 
	                              AND si.inquiry_status = 'Closed'"; 

	if ($my_closed_results = mysqli_query($dbc, $query_my_spotlight_closed)){
 		$my_spotlight_closed = mysqli_num_rows($my_closed_results);
	} else {
		$my_spotlight_closed = 0;
	}
	
	$data .='<li class="list-group-item d-flex justify-content-between lh-sm">
				<div>
					<h6 class="my-0">Total</h6>
					<small class="text-body-secondary">Spotlights</small>
				</div>
				<div>
					<span class="badge bg-cool-ice">'.$my_spotlight_count.'</span>
				</div>
			 </li>
			 <li class="list-group-item d-flex justify-content-between lh-sm">
			 	<div>
					<h6 class="my-0">Active</h6>
					<small class="text-body-secondary">Spotlights</small>
				</div>
				<div>
					<span class="badge bg-cool-ice">'.$my_spotlight_active.'</span>
				</div>
			 </li>
			 <li class="list-group-item d-flex justify-content-between lh-sm">
			 	<div>
					<h6 class="my-0">Paused</h6>
					<small class="text-body-secondary">Spotlights</small>
				</div>
				<div>
					<span class="badge bg-cool-ice">'.$my_spotlight_paused.'</span>
				</div>
			 </li>
			 <li class="list-group-item d-flex justify-content-between lh-sm">
				<div>
					<h6 class="my-0">Closed</h6>
					<small class="text-body-secondary">Spotlights</small>
				</div>
				<div>
					<span class="badge bg-cool-ice">'.$my_spotlight_closed.'</span>
				</div>
			 </li>';
		
	$data .='</ul>';

echo $data; 

}

mysqli_close($dbc);

?>