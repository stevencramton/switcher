<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')){
	header("Location:index.php?msg1");
	exit();
}

if (isset($_SESSION['id'])) {
	$session = session_id();
    $time = time();
    $inactive_time_in_seconds = 600;
    $inactive_time = $time - $inactive_time_in_seconds;
    $time_out_in_seconds = 3600;
    $time_out = $time - $time_out_in_seconds;

    if (!isset($_SESSION['temp_session_token'])) {
		$switch_id = strip_tags($_SESSION['switch_id']);
        $first = strip_tags($_SESSION['first_name']);
        $last = strip_tags($_SESSION['last_name']);
        $user = strip_tags($_SESSION['user']);

        $query = "SELECT * FROM logged_in WHERE switch_id = ?";
        $stmt = $dbc->prepare($query);
        $stmt->bind_param("s", $switch_id);
        $stmt->execute();
        $send_query = $stmt->get_result();
        confirmQuery($send_query);
        $count = $send_query->num_rows;

        if ($count == null) {
            $insert_query = "INSERT INTO logged_in (switch_id, username, first_name, last_name, session, time) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $dbc->prepare($insert_query);
            $stmt->bind_param("sssssi", $switch_id, $user, $first, $last, $session, $time);
            $stmt->execute();
        } else {
            $update_query = "UPDATE logged_in SET time = ?, session = ? WHERE switch_id = ?";
            $stmt = $dbc->prepare($update_query);
            $stmt->bind_param("iss", $time, $session, $switch_id);
            $stmt->execute();
        }
    }

	$data = '<table class="table table-striped">';

	$data .= '<thead>
	            <tr>
	             	<th scope="col" class="text-center">Profile</th>
	                <th scope="col">Name</th>
	           	 	<th scope="col" class="text-center">Status</th>
	            </tr>
	          </thead>
	          <tbody>';
			  
			  $users_online_query = "SELECT logged_in.*, users.profile_pic, users.display_title
			                         FROM logged_in 
			                         JOIN users ON logged_in.switch_id = users.switch_id 
			                         WHERE logged_in.time > ?";
									 
			  $stmt = $dbc->prepare($users_online_query);
			  $stmt->bind_param("i", $time_out);
			  $stmt->execute();
			  $users_online_result = $stmt->get_result();
			  confirmQuery($users_online_result);

			  $count = 1;
			  while ($users_online_row = $users_online_result->fetch_assoc()) {

			   	  if ($users_online_row['time'] > $inactive_time) {
			          $status_icon = '<i class="fa fa-circle user_status_icon"></i>';
			          $status_class = '';
			      } else {
			          $status_icon = '<i class="fa fa-circle user_status_inactive_icon"></i>';
			          $status_class = 'text-muted';
			      }

			  	  $data .= '<tr class="' . $status_class . '">
			         		  <td class="text-center" style="width:15%;">
			                      <img src="' . $users_online_row['profile_pic'] . '" alt="twbs" width="41" height="41" class="rounded-circle">
			                  </td>
			                  <td>
							     <div>' . htmlspecialchars($users_online_row['first_name']) . ' ' . htmlspecialchars($users_online_row['last_name']) . '</div>
							  	 <div>' . htmlspecialchars($users_online_row['display_title']) . '</div>
							  </td>
			               	  <td class="align-middle text-center">' . $status_icon . '</td>
			                </tr>';
			  }

    $data .= '</tbody></table>';
	echo $data;
}

mysqli_close($dbc);
?>