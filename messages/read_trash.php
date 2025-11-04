<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('messages_view_trash')){
	header("Location:../../index.php?msg1");
	exit();
}

?>

<script>
$(document).ready(function(){
	function countTrashMessages(){
		var count_trash = $(".count-trash-items").length;
		$(".count-trash-messages").html(count_trash);
	} countTrashMessages();
});
</script>

<?php

if (isset($_SESSION['id'])) {

    $data = '<script>
$(document).ready(function(){
    $(".hide_and_seek_trash").prop("disabled", true);
 	$("input:checkbox").click(function() {
        if ($(this).is(":checked")) {
            $(".hide_and_seek_trash").prop("disabled", false);
        } else {
            if ($(".chk-box-trash").filter(":checked").length < 1){
                $(".hide_and_seek_trash").attr("disabled", true);
            }
        }
    });
});
</script>

<script>
$("#trash_table").dataTable({
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
</script>';

	$user = $_SESSION['user'];
	$query = "SELECT * FROM messages WHERE recipient = ? AND trash = 1 ORDER BY id DESC";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $msg_user_first = htmlspecialchars($_SESSION['first_name']);
    $msg_user_last = htmlspecialchars($_SESSION['last_name']);
    $msg_user_full = $msg_user_first . ' ' . $msg_user_last;

    $data .= '<div class="table-responsive p-1">
                <table class="table table-sm" id="trash_table">
                    <thead class="bg-light">
                        <tr>';
                
                if (checkRole('messages_restore') || checkRole('messages_delete')) {
                    $data .= '<th>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input select-all-trash" id="read_all_trash_msg_switch">
                            <label class="form-check-label" for="read_all_trash_msg_switch"></label>
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
                
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $id = htmlspecialchars($row['id']);
        $message_profile_pic = htmlspecialchars($row['message_profile_pic']);
        $date = htmlspecialchars($row['date']);
        $full_name = htmlspecialchars($row['full_name']);
        $sender = htmlspecialchars($row['sender']);
        $subject = htmlspecialchars($row['subject']);
        $message_read = htmlspecialchars($row['message_read']);
        $priority = htmlspecialchars($row['priority']);

        $data .= '<tr class="count-trash-items ';

        if ($message_read == 1) {
            $data .= 'table-danger message_row';
        }

        $data .= '">';
        
        if (checkRole('messages_restore') || checkRole('messages_delete')) {
            $data .= '<td class="align-middle" style="width:3%;">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input chk-box-trash" data-message-id="'.$id.'">
                    <label class="form-check-label" for="'.$id.'"></label>
                </div>
            </td>';
        }

        $data .= '<td class="align-middle" onclick="GetMessageDetails('.$id.')" style="cursor:pointer;width:30%;">
                    <img src="' . $message_profile_pic . '" class="profile-photo me-1"> '. $sender .'
                  </td>
                  <td class="align-middle" onclick="GetMessageDetails('.$id.')" style="cursor:pointer;">'. $subject .'</td>
                  <td class="align-middle text-center" onclick="GetMessageDetails(' .$id .')" style="cursor:pointer;width:5%;">
                    <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="'. $date . '">
                        <i class="fa-regular fa-clock me-3" style="color:#909090;"></i>
                    </span>
                  </td>
                  <td class="align-middle text-center" onclick="GetMessageDetails('.$id.')" style="cursor:pointer;width:5%;">';

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
                  <td class="align-middle text-center" onclick="GetMessageDetails('.$id.')" style="cursor:pointer; width:5%;">';

        if ($message_read == 1) {
            $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Unread">
                        <i class="fa-solid fa-envelope-circle-check text-danger me-3"></i>
                     </span>';
        } else {
            $data .= '<span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip" data-bs-title="Read">
                        <i class="fa-solid fa-envelope-open-text me-3" style="color:#909090;"></i>
                     </span>';
        }
        
        $data .= '</td>
                  </tr>';
    }
    
    $data .= '</tbody>
                </table>
              </div>';

    echo $data;

    mysqli_stmt_close($stmt);
}

mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	$('.select-all-trash').on('click', function(e) {
 	 	 if($(this).is(':checked',true)) {
			 $(".chk-box-trash").prop('checked', true);
		 } else {
			 $(".chk-box-trash").prop('checked',false);
		 }
	});

	$(".chk-box-trash").on('click', function(e) {
		if($(this).is(':checked',true)) {
			$(".select-all-trash").prop("checked", false);
		} else {
			$(".select-all-trash").prop("checked", false);
		}

		if ($(".chk-box-trash").not(':checked').length == 0) {
			$(".select-all-trash").prop("checked", true);

		}
	});
	
	$('.hide_and_seek_trash').prop("disabled", true);
	$('#trash_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek_trash').prop("disabled", false);
		} else {
			if ($('.chk-box-trash').filter(':checked').length < 1){
				$('.hide_and_seek_trash').attr('disabled', true);}
			}
	});

});
</script>
