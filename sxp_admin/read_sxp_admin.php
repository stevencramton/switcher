<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}
?>

<script>
$(document).ready(function(){
	function countAuditRecords(){
		var count = $('.count-spx-item').length;
	    $('.count-audit-records').html(count);
	} countAuditRecords();
});
</script>

<script>
$(document).ready(function(){
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_audit_count").html(count);
	} else {
		$(".my_audit_count").html(count_zero);
	}					
});	
</script>

<?php

if (isset($_SESSION['id'])) {
    $data = '<script>
	$(document).ready(function(){
		$(".hide_and_seek").prop("disabled", true);
    	$("input:checkbox").click(function() {
    		if ($(this).is(":checked")) {
           	 	$(".hide_and_seek").prop("disabled", false);
        	} else {
    			if ($(".chk-box-delete-audit").filter(":checked").length < 1){
               	 	$(".hide_and_seek").attr("disabled",true);
				}
        	}
   		 });
	});
	</script>
            
	<script>
    $("#sxp_table").dataTable( {
  	  	aLengthMenu: [
            [100, 200, -1],
            [100, 200, "All"]
        ],  
    });
    </script>

    <div class="table-responsive p-1">
    	<table class="table table-sm table-hover table-striped" id="sxp_table">
            <thead class="bg-light">
                <th> 
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input select-all-delete-audit" id="select-deleted-audit">
                        <label class="form-check-label" for="select-deleted-audit"></label>
                    </div>
                </th>
              	<th>Photo</th>
                <th>Name</th>
                <th>User</th>
                <th><small>SXP</small></th>
                <th>View</th>
			</thead>
            <tbody id="">';

	$query = "SELECT id, first_name, last_name, user, xp FROM user_xp WHERE user != 'infotech' AND user != 'jguilmet'";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $user, $xp);
            
            while (mysqli_stmt_fetch($stmt)) {
                $sxp_id = htmlspecialchars($id);
                $first_name = htmlspecialchars($first_name);
                $last_name = htmlspecialchars($last_name);
                $user = htmlspecialchars($user);
                $sxp = htmlspecialchars($xp);

                $data .= '<tr class="count-spx-item" id="">
                            <td class="align-middle" style="width:4%;">
                                <div class="form-check">
                                    <input type="checkbox" class="custom-delete form-check-input chk-box-delete-audit" data-sxp-id="'.$sxp_id.'" data-emp-user="" data-emp-fname="">
                                    <label class="form-check-label" for="'.$sxp_id.'"></label>
                                </div>
                            </td>
                            <td class="align-middle" style="width:5%;">
                                <img src="img/profile_pic/avatar.png" class="profile-photo">
                            </td>
                            <td class="align-middle text-secondary fw-bold" style="width:20%;">'.$first_name. ' '.$last_name.'</td>
                            <td class="align-middle text-secondary fw-bold" style="width:20%;">'.$user.'</td>
                            <td class="align-middle">
                                <span class="badge bg-audit-primary-ghost shadow-sm">
                                     '.$sxp.'
                                </span>
                            </td>
                            <td class="align-middle text-center" style="width:5%">
                                <button type="button" class="btn btn-light-gray btn-sm" onclick="readSXPDetails('.$sxp_id.')"><i class="fa-solid fa-eye"></i></button>
                            </td>
                        </tr>';
            }
            mysqli_stmt_close($stmt);
        } else {
            echo 'Query Execution Failed.';
        }
    } else {
        echo 'Statement Preparation Failed.';
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
	var $audit_chkboxes = $(".chk-box-delete-audit");
	var lastChecked = null;

	$audit_chkboxes.click(function(e) {
		if (!lastChecked) {
			lastChecked = this;
			return;
		}
		if (e.shiftKey) {
			var start = $audit_chkboxes.index(this);
	    	var end = $audit_chkboxes.index(lastChecked);
			$audit_chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop("checked", lastChecked.checked);
		}
		lastChecked = this;
	});
	
	$('.select-all-delete-audit').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".chk-box-delete-audit").prop('checked', true);
			var count = $(".chk-box-delete-audit:checked").length;
			var count_zero = '0';

			if (count != 0){
				$(".my_audit_count").html(count);
			} else {
				$(".my_audit_count").html(count_zero);
			}
		 } else {
			 $(".chk-box-delete-audit").prop('checked',false);
			 $(".hide_and_seek").attr("disabled", true);
			 var count = '0';
			 var count_zero = '0';
			 if (count != 0){
				 $(".my_audit_count").html(count);
			 } else {
				 $(".my_audit_count").html(count_zero);
			 }
		 }
	 });

	 $(".chk-box-delete-audit").on('click', function(e) {
		var count = $(".chk-box-delete-audit:checked").length;
		var count_zero = '0';
		if (count != 0){
			$(".my_audit_count").html(count);
		} else {
			$(".my_audit_count").html(count_zero);
		}
		if ($(this).is(':checked',true)) {
			$(".select-all-delete-audit").prop("checked", false);
		} else {
			$(".select-all-delete-audit").prop("checked", false);
		}
		if ($(".chk-box-delete-audit").not(':checked').length == 0) {
			$(".select-all-delete-audit").prop("checked", true);
		}
	});
	
	$('.hide_and_seek').prop("disabled", true);
	
	$('#deleted_user_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek').prop("disabled", false);
		} else {
			if ($('.chk-box-delete-audit').filter(':checked').length < 1){
				$('.hide_and_seek').attr('disabled', true);
			}
		}
	});
});
</script>