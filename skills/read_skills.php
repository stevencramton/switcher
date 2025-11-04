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
  	  	var count = $('.count-audit-item').length;
        $('.count-audit-records').html(count);
	} countAuditRecords();
});
</script>

<script>
$(document).ready(function(){
	var count = $(".chk-box-delete-skill:checked").length;
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
    $(".skill_sort_icon").mousedown(function(){
        $( "#sortable_skill_row" ).sortable({
            update: function( event, ui ) {
                updateSkillDisplayOrder();
            }
        });
    });
});
</script>

<script>
function updateSkillDisplayOrder() {
	var selectedItem = new Array();
	$("tbody#sortable_skill_row tr").each(function() {
        selectedItem.push($(this).data("id"));
    });
  	var dataString = "sort_order="+selectedItem;
    
    $.ajax({
          type: "GET",
          url: "ajax/skills/update_skill_order.php",
          data: dataString,
          cache: false,
          success: function(data){
            readSkills();
            
            var toastTrigger = document.getElementById("sortable_skill_row")
            var toastLiveExample = document.getElementById("toast-cat-order")
            if (toastTrigger) {
                var toast = new bootstrap.Toast(toastLiveExample)
                toast.show()
            }
            
          }
    });
}
</script>

<script>
$(document).ready(function(){
	$(".hide_and_seek").prop("disabled", true);
 	$("input:checkbox").click(function() {
		if ($(this).is(":checked")) {
			$(".hide_and_seek").prop("disabled", false);
   		} else {
     	   	if ($(".chk-box-delete-skill").filter(":checked").length < 1){
          	  	$(".hide_and_seek").attr("disabled",true);
			}
   		}
	});
});
</script>
    
<script>
$("#read_skills_table").DataTable({
	aLengthMenu: [
  		[100, 200, -1],
    	[100, 200, "All"]
 	],
 	responsive: true,
		"columnDefs": [
 	   		{ "orderable": false, "targets": [0, 1, 8]}],
       	 	"order": []
});
</script>';

$data .='<div class="table-responsive p-1">
            <table class="table table-sm" id="read_skills_table" width="100%">
                <thead class="bg-light">
                    <th> 
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input select-all-delete-audit" id="select-deleted-audit">
                            <label class="form-check-label" for="select-deleted-audit"></label>
                        </div>
                    </th>
                    <th class="">Sort</th>
                    <th>Date</th>
                    <th>Creator</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Objective</th>
                    <th>Resource</th>
                    <th class="">View</th>
                </thead>
                <tbody class="bg-white" id="sortable_skill_row">';
                            
                $query = "SELECT * FROM skills ORDER BY skill_display_order ASC";
                
                if ($result = mysqli_query($dbc, $query)) {
                    confirmQuery($result);
                            
                    while ($row = mysqli_fetch_assoc($result)) {
                        
                        $skill_id = htmlspecialchars($row['skill_id'], ENT_QUOTES, 'UTF-8');
                        $skill_creation_date = htmlspecialchars($row['skill_creation_date'], ENT_QUOTES, 'UTF-8');   
                        $skill_created_by = htmlspecialchars($row['skill_created_by'], ENT_QUOTES, 'UTF-8');   
                        $skill_category = htmlspecialchars($row['skill_category'], ENT_QUOTES, 'UTF-8');   
                        $skill_name = htmlspecialchars($row['skill_name'], ENT_QUOTES, 'UTF-8');   
                        $skill_objective = htmlspecialchars($row['skill_objective'], ENT_QUOTES, 'UTF-8');   
                        $skill_resource = htmlspecialchars($row['skill_resource'], ENT_QUOTES, 'UTF-8');   
                   	 	$skill_objective = (strlen($skill_objective) > 30) ? substr($skill_objective, 0, 30).'...' : $skill_objective;
                        
                        $data.='<tr class="count-audit-item" data-id="'.$skill_id.'">
                                  <td class="align-middle" style="width:4%;">
                                    <div class="form-check">
                                        <input type="checkbox" class="custom-delete form-check-input chk-box-delete-skill" data-skill-id="'.$skill_id.'">
                                        <label class="form-check-label" for="'.$skill_id.'"></label>
                                    </div>
                                </td>
                                <td class="skill_sort_icon align-middle" style="width:3%">
                                    <span class="text-secondary"><i class="fa-solid fa-sort"></i></span>
                                </td>
                                <td class="align-middle"><small>'.$skill_creation_date.'</small></td>
                                <td class="align-middle">'.$skill_created_by.'</td>
                                <td class="align-middle">'.$skill_category.'</td>
                                <td class="align-middle">'.$skill_name.'</td>
                                <td class="align-middle">'.$skill_objective.'</td>
                                <td class="align-middle">'.$skill_resource.'</td>
                                <td class="align-middle" style="width:5%">
                                    <button type="button" class="btn btn-light-gray btn-sm" onclick="viewSkillRecord('.$skill_id.')"><i class="fa-solid fa-eye"></i></button>
                                </td>';
                            }
                        }
                            $data .='</tr>
                        </tbody>
                    </table>
                </div>';

echo $data;

}

mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	var $audit_chkboxes = $(".chk-box-delete-skill");
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
			 $(".chk-box-delete-skill").prop('checked', true);
			 
			var count = $(".chk-box-delete-skill:checked").length;
			var count_zero = '0';

			if (count != 0){
				$(".my_audit_count").html(count);
			} else {
				$(".my_audit_count").html(count_zero);
			}
			
		 } else {
			 $(".chk-box-delete-skill").prop('checked',false);
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

	$(".chk-box-delete-skill").on('click', function(e) {
		var count = $(".chk-box-delete-skill:checked").length;
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

		if ($(".chk-box-delete-skill").not(':checked').length == 0) {
			$(".select-all-delete-audit").prop("checked", true);

		}
	});
	
	$('.hide_and_seek').prop("disabled", true);
	
	$('#deleted_user_table').on("click", 'input:checkbox', function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek').prop("disabled", false);
		} else {
			if ($('.chk-box-delete-skill').filter(':checked').length < 1){
				$('.hide_and_seek').attr('disabled', true);}
			}
	});
});
</script>