<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('message_admin_records')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])) {
    header("Location:../../index.php?msg1");
    exit();
} else {
	$data = '
        <script>
            $(document).ready(function(){
                function countAllSentMessages(){
                    var count_all = $(".count-all-sent-items").length;
                    $(".count-all-sent-messages").html(count_all);
                }
                countAllSentMessages();
            });
        </script>
        
        <script>
            $("#all_sent_messages_table").dataTable({
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

    $data .= '<div class="table-responsive p-1">
        <table class="table table-sm" id="all_sent_messages_table">
        <thead class="bg-light">
            <tr>';

    if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
        $data .= '<th>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input select-all" id="switch1">
                <label class="form-check-label" for="switch1"></label>
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

	$query = "
        SELECT DISTINCT id, message_profile_pic, recipient, full_name, message, date, sender, subject, message_force, message_read, priority, trash, active
        FROM (
            SELECT m.id, m.message_profile_pic, m.recipient, m.full_name, m.message, m.date, m.sender, m.subject, m.message_force, m.message_read, m.priority, m.trash, m.active
            FROM messages m
            LEFT JOIN messages_sent ms ON m.id = ms.id
            UNION ALL
            SELECT ms.id, ms.message_profile_pic, ms.recipient, ms.full_name, ms.message, ms.date, ms.sender, ms.subject, ms.message_force, ms.message_read, ms.priority, ms.trash, ms.active
            FROM messages_sent ms
            LEFT JOIN messages m ON ms.id = m.id
        ) AS result
        WHERE trash = 0
        ORDER BY id DESC;
    ";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $id = htmlspecialchars($row['id']);
                    $message_profile_pic = htmlspecialchars($row['message_profile_pic']);
                    $trash = htmlspecialchars($row['trash']);
                    $date = htmlspecialchars($row['date']);
                    $sender = htmlspecialchars($row['sender']);
                    $recipient = htmlspecialchars($row['recipient']);
                    $subject = htmlentities($row['subject']);
                    $message_read = htmlspecialchars($row['message_read']);
                    $priority = htmlspecialchars($row['priority']);
                    $active = htmlspecialchars($row['active']);

                    if ($trash == 0) {
                        $data .= '<tr class="count-all-sent-items ';

                        if ($message_read == 1) {
                            $data .= 'msg-count-item table-success message_row"';
                        } else {
                            $data .= '"';
                        }

                        $data .= '">';

                        if (checkRole('messages_edit') || checkRole('message_move_to_trash')) {
                            $data .= '<td class="align-middle" style="width:3%;">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input chk-box-msg-all-sent" id="'.$id.'" data-message-id="'.$id.'">
                                    <label class="form-check-label" for="'.$id.'"></label>
                                </div>
                            </td>';
                        }

                        if (empty($message_profile_pic)) {
                            $data .= '<td style="width:6%;">
                                <img src="img/profile_pic/ghost_user/ghost_user_2.png" class="profile-photo" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Account removed">
                            </td>';
                        } else {
                            $data .= '<td class="align-middle" onclick="viewSentMessage(' .$id .')" style="cursor:pointer;width:25%;">
                                <img src="' . $message_profile_pic . '" class="profile-photo me-2"> '.$sender.' 
                            </td>';
                        }

						$query_user_full = "SELECT first_name, last_name FROM users WHERE user = ?";
                        if ($stmt_user = mysqli_prepare($dbc, $query_user_full)) {
                            mysqli_stmt_bind_param($stmt_user, 's', $recipient);
                            if (mysqli_stmt_execute($stmt_user)) {
                                $result_user_full = mysqli_stmt_get_result($stmt_user);
                                if ($result_user_full) {
                                    if ($row_user = mysqli_fetch_assoc($result_user_full)) {
                                        $user_first_name = htmlentities($row_user['first_name']);
                                        $user_last_name = htmlentities($row_user['last_name']);
                                        $user_full_name = $user_first_name . ' ' . $user_last_name;
                                    }
                                }
                            }
                            mysqli_stmt_close($stmt_user);
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
                                    <i class="fa-solid fa-circle-xmark text-pink me-3"></i>
                                </span>';
                            }

                            $data .= '</td>';
                        }

                        $data .= '</tr>';
                    }
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            die('Error.');
        }
    } else {
        die('Error.');
    }

    $data .= '</tbody>
        </table></div></div>
    </div>
</div>
</div>';

    echo $data;
}

mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	$('.select-all').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".chk-box-msg-all-sent").prop('checked', true);
		} else {
			$(".chk-box-msg-all-sent").prop('checked',false);
		}
	});

	$(".chk-box-msg-all-sent").on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".select-all").prop("checked", false);
		} else {
			$(".select-all").prop("checked", false);
		}
		if ($(".chk-box-msg-all-sent").not(':checked').length == 0) {
			$(".select-all").prop("checked", true);
		}
	});
	$('.hide_and_seek_all_sent').prop("disabled", true);
	$('#all_sent_messages_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek_all_sent').prop("disabled", false);
		} else {
			if ($('.chk-box-msg-all-sent').filter(':checked').length < 1){
				$('.hide_and_seek_all_sent').attr('disabled', true);
			}
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