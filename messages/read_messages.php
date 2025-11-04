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

if (!isset($_SESSION['id'])) {
    header("Location:../../index.php?msg1");
    exit();
} else {
	
    $data = '<script>
                $(document).ready(function(){
                    function countInboxRecords(){
                        var count = $(".count-inbox-item").length;
                        $(".count-inbox-records").html(count);
                    }
                    countInboxRecords();
                });
            </script>
            <script>
                $("#messages_table").dataTable({
                    "autoWidth": false,
                    aLengthMenu: [
                        [100, 200, -1],
                        [100, 200, "All"]
                    ],
                    "columnDefs": [
                        { "orderable": false, "targets": 0 }
                    ],
                    "order": []
                });
            </script>
            <script>
                $(document).ready(function(){
                    function countUnreadMessages(){
                        var count = $(".msg-count-item").length;
                        if (count != 0){
                            $(".message_count").html(count);
                        } else {
                            $(".unread_msg_badge").css("display", "none");
                        }
                    }
                    countUnreadMessages();
                });
            </script>';

    $user = $_SESSION['user'];

    $data .= '<div class="table-responsive p-1">
                <table class="table table-sm" id="messages_table">
                    <thead class="bg-light">
                        <tr>';

    if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
        $data .= '<th>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input select-all" id="read_all_msg_switch">
                        <label class="form-check-label" for="read_all_msg_switch"></label>
                    </div>
                  </th>';
    }

    $data .= '<th>From</th>
              <th>Subject</th>
              <th>Date</th>
              <th>Priority</th>
              <th>Status</th>
            </tr>
        </thead>
        <tbody id="">';

	$query = "SELECT * FROM messages WHERE recipient = ? AND active = 0 ORDER BY id DESC";
    if ($stmt = mysqli_prepare($dbc, $query)) {
  	  	mysqli_stmt_bind_param($stmt, 's', $user);
		mysqli_stmt_execute($stmt);

		$result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $id = $row['id'];
            $message_profile_pic = $row['message_profile_pic'];
            $trash = $row['trash'];
            $date = $row['date'];
            $sender = $row['sender'];
            $subject = htmlentities($row['subject']);
            $message_read = $row['message_read'];
            $priority = $row['priority'];

            if ($trash == 0) {
                $data .= '<tr class="count-inbox-item ';

                if ($message_read == 1) {
                    $data .= 'msg-count-item table-success text-white message_row"';
                } else {
                    $data .= '"';
                }

                $data .= '">';

                if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
                    $data .= '<td class="align-middle" style="width:3%;">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input chk-box-msg" id="' . $id . '" data-message-id="' . $id . '">
                                    <label class="form-check-label" for="' . $id . '"></label>
                                </div>
                              </td>';
                }

                $data .= '<td class="align-middle" onclick="viewMessage(' . $id . ')" style="cursor:pointer;width:30%;">
                            <img src="' . $message_profile_pic . '" class="profile-photo me-1"> ' . $sender . '
                          </td>
                          <td class="align-middle" onclick="viewMessage(' . $id . ')" style="cursor:pointer;">' . $subject . '</td>
                          <td class="align-middle text-center" onclick="viewMessage(' . $id . ')" style="cursor:pointer;width:5%;">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="' . $date . '">
                                <i class="fa-regular fa-clock me-3" style="color:#909090;"></i>
                            </span>
                          </td>
                          <td class="align-middle text-center" onclick="viewMessage(' . $id . ')" style="cursor:pointer;width:5%;">';

            if ($priority == "Normal") {
                $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Normal">
                            <i class="fa-solid fa-flag text-success me-3"></i>
                          </span>';
            }

            if ($priority == "Medium") {
                $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Medium">
                            <i class="fa-solid fa-bolt text-warning me-3"></i>
                          </span>';
            }

            if ($priority == "High") {
                $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="High">
                            <i class="fa-solid fa-circle-exclamation bg-hot-o me-3"></i>
                          </span>';
            }

            $data .= '</td>
                      <td class="align-middle text-center" onclick="viewSentMessage(' . $id . ')" style="cursor:pointer; width:5%;">';

            if ($message_read == 1) {
                $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Unread">
                            <i class="fa-solid fa-envelope-circle-check text-success me-3"></i>
                          </span>';
            } else {
                $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Read">
                            <i class="fa-solid fa-envelope-open-text me-3" style="color:#909090;"></i>
                          </span>';
            }

            $data .= '</td></tr>';
        } 
	
	}
	
	mysqli_stmt_close($stmt);
	
    } 

    $data .= '</tbody>
            </table>
          </div>';

    echo $data;
}

mysqli_close($dbc);
?>

<script>
 $(document).ready(function() {
	 $('.select-all').on('click', function(e) {
 	 	 if($(this).is(':checked',true)) {
			 $(".chk-box-msg").prop('checked', true);
		 } else {
			 $(".chk-box-msg").prop('checked',false);
		 }
	});
	$(".chk-box-msg").on('click', function(e) {
		if($(this).is(':checked',true)) {
			$(".select-all").prop("checked", false);
		} else {
			$(".select-all").prop("checked", false);
		}

		if ($(".chk-box-msg").not(':checked').length == 0) {
			$(".select-all").prop("checked", true);
		}
	});
	$('.hide_n_seek').prop("disabled", true);
	$('#messages_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_n_seek').prop("disabled", false);
		} else {
			if ($('.chk-box-msg').filter(':checked').length < 1){
				$('.hide_n_seek').attr('disabled', true);}
			}
	});
});
</script>

<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>