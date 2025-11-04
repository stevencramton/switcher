<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {
	$data = '';
	$user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));

    $data .='<div class="table-responsive p-1">
                <table class="table" id="messages_table">
                <thead class="bg-light">
                    <tr>
                        <th>From</th>
                         <th>Subject</th>
                    </tr>
                </thead>
          	  	<tbody id="">';

    $query = "SELECT sender, subject, trash FROM messages WHERE recipient = ? AND message_force = 1 ORDER BY id DESC";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $sender, $subject, $trash);
        mysqli_stmt_store_result($stmt);
		confirmQuery($stmt);

        while (mysqli_stmt_fetch($stmt)) {
			$sender = mysqli_real_escape_string($dbc, strip_tags($sender));
            $subject = htmlentities($subject);

            if ($trash == 0) {
				$data .='<tr class="">';
				$data .='<td>'. $sender .'</td>
                         <td>'. $subject . '</td>';
				$data .='</tr>';
			}
        }

        mysqli_stmt_close($stmt);
    }

    $data .='</tbody>
            </table>
        </div>
        <a href="messages.php" class="shadow-sm btn btn-lg btn-primary w-100">View messages!</a>';

    echo $data;
}

mysqli_close($dbc);