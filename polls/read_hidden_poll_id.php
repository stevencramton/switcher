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

if (isset($_SESSION['id'])) {
	$query = "SELECT * FROM poll_inquiry ORDER BY inquiry_id DESC LIMIT 1";
	if ($stmt = mysqli_prepare($dbc, $query)) {
   	 	mysqli_stmt_execute($stmt);
    	$result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $inquiry_id = htmlspecialchars(strip_tags($row['inquiry_id']));
                $inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
                
                $data = '<input type="hidden" class="form-control" id="create_inquiry_poll_question_id" value="'.$inquiry_id.'">';
            }
        } else {
          	$data = '';
        }
     	mysqli_stmt_close($stmt);
    } else {
       	exit('Error preparing the SQL statement.');
    }
	echo $data;
}
mysqli_close($dbc);