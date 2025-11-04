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
        function countSentMessages() {
            var count = $(".count-sent-items").length;
            $(".count-sent-messages").html(count);
        }
        countSentMessages();
    });
    </script>
    
    <script>
    $("#messages_sent_table").dataTable({
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
        function countUnreadMessages() {
            var count = $(".msg-count-item").length;
            if (count != 0) {
                $(".message_count").html(count);
            } else {
                $(".unread_msg_badge").css("display", "none");
            }
        }
        countUnreadMessages();
    });
    </script>';

    $data .= '<div class="table-responsive p-1">
        <table class="table table-sm" id="messages_sent_table">
        <thead class="bg-light">
            <tr>';

    if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
        $data .= '<th>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input select-all-sent" id="read_all_sent_msg_switch">
                <label class="form-check-label" for="read_all_sent_msg_switch"></label>
            </div>
        </th>';
    }

    $data .= '<th>From</th>
        <th>To</th>
        <th>Subject</th>
        <th>Date</th>
        <th>Priority</th>';

    if (checkRole('messages_send_read')) {
        $data .= '<th>Read</th>';
    }

    if (checkRole('messages_edit')) {
        $data .= '<th>Status</th>';
    }

    $data .= '</tr>
        </thead>
        <tbody id="items">';

	$first_name = $_SESSION['first_name'];
    $last_name = $_SESSION['last_name'];
    $full_name = $first_name . ' ' . $last_name;

	$query = "SELECT * FROM messages_sent WHERE sender = ? ORDER BY id DESC";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 's', $full_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $id = $row['id'];
        $message_profile_pic = $row['message_profile_pic'];
        $trash = $row['trash'];
        $date = $row['date'];
        $sender = $row['sender'];
        $recipient = $row['recipient'];
        $subject = $row['subject'];
        $message_read = $row['message_read'];
        $priority = $row['priority'];
        $active = $row['active'];

        if ($trash == 0) {
            $data .= '<tr class="count-sent-items ';

            if ($message_read == 1) {
                $data .= 'msg-count-item table-success text-white message_row"';
            }

            $data .= '">';

            if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
                $data .= '<td class="align-middle" style="width:3%;">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input chk-box-sent-msg" id="'.$id.'" data-message-sent-id="'.$id.'">
                        <label class="form-check-label" for="'.$id.'"></label>
                    </div>
                </td>';
            }

            if (empty($message_profile_pic)) {
                $data .= '<td style="width:6%;">
                    <img src="img/profile_pic/ghost_user/ghost_user_2.png" class="profile-photo" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Account removed">
                </td>';
            } else {
                $data .= '<td onclick="viewSentMessage(' .$id .')" style="cursor:pointer;width:25%;">
                    <img src="' . $message_profile_pic . '" class="profile-photo me-2"> '.$sender.'
                </td>';
            }

			$query_user = "SELECT first_name, last_name FROM users WHERE user = ?";
            $stmt_user = mysqli_prepare($dbc, $query_user);
            mysqli_stmt_bind_param($stmt_user, 's', $recipient);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);

            if ($row_user = mysqli_fetch_array($result_user, MYSQLI_ASSOC)) {
                $user_first_name = $row_user['first_name'];
                $user_last_name = $row_user['last_name'];
                $user_full_name = $user_first_name . ' ' . $user_last_name;
            } else {
                $user_full_name = 'Unknown';
            }

            $data .= '<td class="align-middle" onclick="viewSentMessage(' .$id .')" style="cursor:pointer;width:25%;">'. $user_full_name .'</td>
                <td class="align-middle" onclick="viewSentMessage(' .$id .')" style="cursor:pointer;">'. $subject . '</td>
                <td class="align-middle text-center" onclick="viewMessage(' .$id .')" style="cursor:pointer;width:5%;">
                <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'. $date . '">
                    <i class="fa-regular fa-clock me-3" style="color:#909090;"></i>
                </span>
                </td>
                <td class="align-middle text-center" onclick="viewSentMessage(' .$id .')" style="cursor:pointer; width:5%;">';

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
                    <i class="fa-solid fa-circle-exclamation text-hot me-3"></i>
                </span>';
            }

            $data .= '</td>';

            if (checkRole('messages_send_read')) {
                $data .= '<td class="align-middle text-center" onclick="viewSentMessage('.$id.')" style="cursor:pointer; width:5%;">';
                if ($message_read == 1) {
                    $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Unread">
                        <i class="fa-solid fa-envelope-circle-check text-success me-3"></i>
                    </span>';
                } else {
                    $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Read">
                        <i class="fa-solid fa-envelope-open-text me-3" style="color:#909090;"></i>
                    </span>';
                }
                $data .= '</td>';
            }

            if (checkRole('messages_edit')) {
                $data .= '<td class="align-middle text-center" onclick="viewSentMessage('.$id.')" style="cursor:pointer; width:5%;">';
                if ($active == 0) {
                    $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Active">
                        <i class="fa-solid fa-circle-check text-success me-3"></i>
                    </span>';
                } else {
                    $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Inactive">
                        <i class="fa-solid fa-circle-xmark me-3" style="color:#909090;"></i>
                    </span>';
                }
                $data .= '</td>';
            }

            $data .= '</tr>';
        }
    }

    $data .= '</tbody>
    </table>
    </div>';

    echo $data;

    mysqli_stmt_close($stmt);
    if (isset($stmt_user)) {
        mysqli_stmt_close($stmt_user);
    }
    mysqli_close($dbc);
}
?>

<script>
 $(document).ready(function() {
	 $('.select-all-sent').on('click', function(e) {
 	 	 if($(this).is(':checked',true)) {
			 $(".chk-box-sent-msg").prop('checked', true);
		 } else {
			 $(".chk-box-sent-msg").prop('checked',false);
		 }
	 });

	 $(".chk-box-sent-msg").on('click', function(e) {
		if($(this).is(':checked',true)) {
			$(".select-all-sent").prop("checked", false);
		} else {
			$(".select-all-sent").prop("checked", false);
		}

		if ($(".chk-box-sent-msg").not(':checked').length == 0) {
			$(".select-all-sent").prop("checked", true);
		}
	});
	
	$('.hide_n_seek_sent').prop("disabled", true);
	$('#messages_sent_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_n_seek_sent').prop("disabled", false);
		} else {
			if ($('.chk-box-sent-msg').filter(':checked').length < 1){
				$('.hide_n_seek_sent').attr('disabled', true);}
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