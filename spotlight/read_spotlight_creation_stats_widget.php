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
	$query = "SELECT * FROM spotlight_inquiry ";	
			
	if (!$result = mysqli_query($dbc, $query)) {
		exit();
	}
			
	$total_spotlight_count = "SELECT * FROM spotlight_inquiry ";

	if ($total_spotlight_results = mysqli_query($dbc, $total_spotlight_count)){
		$total_spotlights = mysqli_num_rows($total_spotlight_results);
	} 
			
	$total_active_spotlight_count = "SELECT * FROM spotlight_inquiry WHERE inquiry_status = 'Active'";

	if ($total_active_spotlight_results = mysqli_query($dbc, $total_active_spotlight_count)){
		$total_active_spotlights = mysqli_num_rows($total_active_spotlight_results);
	} 
			
	$total_paused_spotlight_count = "SELECT * FROM spotlight_inquiry WHERE inquiry_status = 'Paused'";

	if ($total_paused_spotlight_results = mysqli_query($dbc, $total_paused_spotlight_count)){
		$total_paused_spotlights = mysqli_num_rows($total_paused_spotlight_results);
	} 
			
	$total_closed_spotlight_count = "SELECT * FROM spotlight_inquiry WHERE inquiry_status = 'Closed'";

	if ($total_closed_spotlight_results = mysqli_query($dbc, $total_closed_spotlight_count)){
  	  $total_closed_spotlights = mysqli_num_rows($total_closed_spotlight_results);
	} 
			
	$total_enrolled_count = "SELECT * FROM spotlight_assignment";

	if ($total_enrolled_results = mysqli_query($dbc, $total_enrolled_count)){
 	   $total_enrolled_users = mysqli_num_rows($total_enrolled_results);
	} 
				
	$data ='<ul class="list-group mb-3">
				<li class="list-group-item d-flex justify-content-between lh-sm">
					<div>
						<h6 class="my-0">Total spotlights</h6>
						<small class="text-body-secondary">All spotlights</small>
					</div>
					<span class="text-body-secondary"><span class="badge bg-cool-ice">'.$total_spotlights.'</span></span>
				</li>
			
				<li class="list-group-item d-flex justify-content-between lh-sm">
					<div>
						<h6 class="my-0">Active</h6>
						<small class="text-body-secondary">Active spotlights</small>
					</div>
					<span class="text-body-secondary"><span class="badge bg-cool-ice">'.$total_active_spotlights.'</span></span>
				</li>
				
				<li class="list-group-item d-flex justify-content-between lh-sm">
					<div>
						<h6 class="my-0">Paused</h6>
						<small class="text-body-secondary">Paused spotlights</small>
					</div>
					<span class="text-body-secondary"><span class="badge bg-cool-ice">'.$total_paused_spotlights.'</span></span>
				</li>
				
				<li class="list-group-item d-flex justify-content-between lh-sm">
					<div>
						<h6 class="my-0">Closed</h6>
						<small class="text-body-secondary">Closed spotlights</small>
					</div>
					<span class="text-body-secondary"><span class="badge bg-cool-ice">'.$total_closed_spotlights.'</span></span>
				</li>
				
				<li class="list-group-item d-flex justify-content-between lh-sm">
					<div>
						<h6 class="my-0">All Enrollments</h6>
						<small class="text-body-secondary">Total Enrollments</small>
					</div>
					<span class="text-body-secondary"><span class="badge bg-cool-ice">'.$total_enrolled_users.'</small></span>
				</li>
			</ul>';
				
	echo $data;
}	

mysqli_close($dbc);
			
?>