<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('kudos_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {

$data = '<script>
$("#user_kudos_table").dataTable( {
	"autoWidth": false,
 	aLengthMenu: [
 		[100, 200, -1],
		[100, 200, "All"]
 	],
	
	"columnDefs": [{ "orderable": false, "targets": 0 }],
	"order": []
});
</script>

<script>
$(document).ready(function(){
 	function countUserKudos(){
    	var count = $(".user-kudos-count").length;
     	if (count != 0){
        	$(".kudos_count").html(count);
      	} else {
        	$(".unread_kudos_badge").css("display", "none");
      	}
	} countUserKudos();
});
</script>

<div class="table-responsive">
	<table class="table" id="user_kudos_table">
		<thead class="table-light"> 
			<tr>
				<th>
		    		<div class="form-check form-switch">
		       			<input type="checkbox" class="form-check-input select-all" id="switch2">
		      	  		<label class="form-check-label" for="switch2"></label>
		     		</div>
				</th>
				<th>Date</th>
				<th>From</th>
				<th>Teamwork</th>
				<th>Knowledge</th>
				<th>Service</th>
				<th>Personal</th>
				<th>Public</th>
			</tr>
		</thead>
	
		<tbody id="items">';
		
		$user = strip_tags($_SESSION['user']);
		
		$query = "SELECT * FROM kudos WHERE recipient = ? ORDER BY id DESC";
		if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_bind_param($stmt, 's', $user);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_array($result)) {
				$id = strip_tags($row['id'] ?? '');
				$read_flag = strip_tags($row['read_flag'] ?? '');
				$kudos_date = strip_tags($row['kudos_date'] ?? '');
				$kudos_time = strip_tags($row['kudos_time'] ?? '');
				$teamwork = strip_tags($row['teamwork'] ?? '');
				$knowledge = strip_tags($row['knowledge'] ?? '');
				$service = strip_tags($row['service'] ?? '');
                $notes_public = htmlspecialchars(strip_tags($row['notes_public']));
                $notes_personal = htmlspecialchars(strip_tags($row['notes_personal']));

                $data .= '<tr ';

                if ($read_flag == 1) {
                    $data .= 'class="table-dark-forest"';
                }

                $data .= '>
                    <td class="align-middle">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input user-kudos-box" id="' . $id . '" data-kudos-read-id="' . $id . '">
                            <label class="form-check-label" for="' . $id . '"></label>
                        </div>
                    </td>
                    <td class="align-middle" style="width:15%;"><small>' . $kudos_date . ' ' . $kudos_time . '</small></td>';

                $from = strip_tags($row['kudos_from']);
                $from_query = "SELECT * FROM users WHERE user = ?";
                if ($from_stmt = mysqli_prepare($dbc, $from_query)) {
                    mysqli_stmt_bind_param($from_stmt, 's', $from);
                    mysqli_stmt_execute($from_stmt);
                    $from_result = mysqli_stmt_get_result($from_stmt);
                    $from_row = mysqli_fetch_array($from_result);
					$from_pic = htmlspecialchars(strip_tags($from_row['profile_pic'] ?? ''));
                    $first_name = strip_tags($from_row['first_name'] ?? '');
                    $last_name = strip_tags($from_row['last_name'] ?? '');
					$from_name = $first_name . ' ' . $last_name;

                    if (empty($from_pic)) {
                        $data .= '<td class="align-middle" style="width:18%;">
                                    <img src="img/profile_pic/ghost_user/ghost_user_2.png" class="profile-photo me-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Account removed"> Deleted user
                                </td>';
                    } else {
                        $data .= '<td class="align-middle" style="width:18%;">
                                    <img src="' . $from_pic . '" class="profile-photo me-1"> ' . $from_name . '
                                </td>';
                    }
                }

                if ($teamwork == 0) {
                    $data .= '<td class="align-middle" width=""><span class="text-center">N/A</span></td>';
                } else {
                    $data .= '<td class="align-middle" width="">';
                    $data .= generateKudosStars($teamwork);
                    $data .= '<span class="ms-1">' . $teamwork . '</span></td>';
                }

                if ($knowledge == 0) {
                    $data .= '<td class="align-middle" width=""><span class="text-center">N/A</span></td>';
                } else {
                    $data .= '<td class="align-middle" width="">';
                    $data .= generateKudosStars($knowledge);
                    $data .= '<span class="ms-1">' . $knowledge . '</span></td>';
                }

                if ($service == 0) {
                    $data .= '<td class="align-middle" width=""><span class="text-center">N/A</span></td>';
                } else {
                    $data .= '<td class="align-middle" width="">';
                    $data .= generateKudosStars($service);
                    $data .= '<span class="ms-1">' . $service . '</span></td>';
                }
				
				$data .= '<td class="align-middle" style="width:18%;">';

				if (empty($notes_personal)) {
				 	$data .= '';
				} else {
				 	$data .= '<button class="btn btn-outline-orange-dark btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#personal-notes-' . htmlspecialchars($id) . '" aria-expanded="false" aria-controls="public-notes-' . htmlspecialchars($id) . '"><i class="fa-brands fa-readme"></i> Read Notes</button>';
				    $data .= '<div class="collapse mt-2" id="personal-notes-' . htmlspecialchars($id) . '">' . nl2br($notes_personal) . '</div>';
				}

				$data .= '</td>';
					
				$data .= '<td class="align-middle" style="width:18%;">';

				if (empty($notes_public)) {
				 	$data .= '';
				} else {
				 	$data .= '<button class="btn btn-outline-warning btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#public-notes-' . htmlspecialchars($id) . '" aria-expanded="false" aria-controls="public-notes-' . htmlspecialchars($id) . '"><i class="fa-brands fa-readme"></i> Read Notes</button>';
				    $data .= '<div class="collapse mt-2" id="public-notes-' . htmlspecialchars($id) . '">' . nl2br($notes_public) . '</div>';
				}

				$data .= '</td></tr>';
			}
        }

        $data .= '</tbody></table></div>';
		echo $data;
	}
	mysqli_close($dbc);
?>

<script>
 $(document).ready(function() {
	 $('.select-all').on('click', function(e) {
 	 	 if ($(this).is(':checked',true)) {
			 $(".user-kudos-box").prop('checked', true);
		 } else {
			 $(".user-kudos-box").prop('checked',false);
		 }
		 toggleButtonGroup();
	});

	$(".user-kudos-box").on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".select-all").prop("checked", false);
		} else {
			$(".select-all").prop("checked", false);
		}
		if ($(".user-kudos-box").not(':checked').length == 0) {
			$(".select-all").prop("checked", true);
		}
		toggleButtonGroup();
	});
	function toggleButtonGroup() {
        if ($('.user-kudos-box:checked').length > 0) {
            $('#user-kudos-btn-group').fadeIn();
        } else {
            $('#user-kudos-btn-group').fadeOut();
        }
    }
	$('.user_kudos_hide_n_seek').prop("disabled", true);
	$('#user_kudos_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.user_kudos_hide_n_seek').prop("disabled", false);
		} else {
			if ($('.user-kudos-box').filter(':checked').length < 1){
				$('.user_kudos_hide_n_seek').attr('disabled', true);}
			}
	});
	
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>