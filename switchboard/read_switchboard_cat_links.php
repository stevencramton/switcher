<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_view')) {
    header("Location:../../index.php?msg1");
	exit();
}

if(isset($_SESSION['id'])){
	$data = '<ul><li><a href="switchboard.php"><span>All Contacts</span></a></li>';
	$query = "SELECT * FROM switchboard_categories ORDER BY switchboard_cat_display_order";
    
  	if($stmt = mysqli_prepare($dbc, $query)){
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		
		while($row = mysqli_fetch_array($result)){
			$switchboard_cat_id = htmlspecialchars(strip_tags($row['switchboard_cat_id']));
			$switchboard_cat_name = htmlspecialchars(strip_tags($row['switchboard_cat_name']));
			
			$data .= '<li>
         	   	<a href="switchboard.php?cat_id=' . $switchboard_cat_id . '"><span>' . $switchboard_cat_name . '</span></a>
       	 		</li>';
		}
		mysqli_stmt_close($stmt);
  	}
	$data .= '</ul>';
	echo $data;
}
mysqli_close($dbc);