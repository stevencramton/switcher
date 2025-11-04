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

<style>
.custom-table {
	background-color: #ffeef6;
	border: 1px solid #fbadd1 !important;
}
	
.search-category {
	display: none;
}
</style>	
	
<script>
$(document).ready(function(){
	function countGroupItems(){
		var count = $('.count-group-item').length;
	    $('.count-group').html(count);
	} countGroupItems();
});
</script>

<script>
$(document).ready(function() {
	$('.select-all-group').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".group-chk-box").prop('checked', true);
		} else {
			$(".group-chk-box").prop('checked',false);
		}
		$("#select_count").html($("input.group-chk-box:checked").length+" ");
	});
	$(".group-chk-box").on('click', function(e) {
		$("#select_count").html($("input.group-chk-box:checked").length+" ");
		if ($(this).is(':checked',true)) {
			$(".select-all-group").prop("checked", false);
		} else {
			$(".select-all-group").prop("checked", false);
		}
		if ($(".group-chk-box").not(':checked').length == 0) {
			$(".select-all-group").prop("checked", true);
		}
	});
	$('input.manage:checkbox').click(function() {
		if ($(this).is(':checked')) {
				$('#search-category').addClass('search-category');
				$('#custom-table').addClass('custom-table');
				$('#table-color').addClass('table-color');
				$('.hide_and_seek_group').fadeIn();
		
		} else {
			if ($('.group-chk-box').filter(':checked').length < 1){
					$('#search-category').removeClass('search-category');
					$('#table-color').removeClass('table-color');
					$('#custom-table').removeClass('custom-table');
					$('.hide_and_seek_group').hide();
				}
			}	
		});
});
</script>

<?php 
$data ='<script>
$(document).ready(function(){
	$(".cat_drag_icon").mousedown(function(){
		$( "#sortable_cat_row" ).sortable({
			update: function( event, ui ) {
				updateDisplayRowOrder();
			}
		});
	});
});
</script>

<script>
function updateDisplayRowOrder() {
	var selectedItem = new Array();
	$("tbody#sortable_cat_row tr").each(function() {
		selectedItem.push($(this).data("id"));
	});
	var dataString = "sort_order="+selectedItem;
	
	$.ajax({
 	  	type: "GET",
  	 	url: "ajax/skills/update_skill_category_order.php",
  	  	data: dataString,
 	   	cache: false,
  	  	success: function(data){
    		readSkillCategories();
			
			var toastTrigger = document.getElementById("sortable_cat_row")
			var toastLiveExample = document.getElementById("toast-cat-order")
			if (toastTrigger) {
	  			var toast = new bootstrap.Toast(toastLiveExample)
				toast.show()
	 	   	}
  	  	}
	});
}
</script>

<table class="table table-bordered table-hover">
	<thead class="dark-gray">
		<tr>
			<th class="text-center">
				<div class="form-check form-switch ms-2">
					<input type="checkbox" class="form-check-input select-all-group manage" id="select-all-group" onclick="searchTog();">
					<label class="form-check-label" for="select-all-group"></label>
				</div>
			</th>
      		<th class="text-center">Sort</th>
      		<th class="text-center">Icon</th>
      		<th>Category <small>(click to edit)</small></th>
		</tr>
  	</thead>
  	<tbody class="bg-white" id="sortable_cat_row">';

$query = "SELECT * FROM skill_categories ORDER BY cat_skill_display_order ASC";

if ($select_categories = mysqli_prepare($dbc, $query)){
	mysqli_stmt_execute($select_categories);
	$result = mysqli_stmt_get_result($select_categories);

	while ($row = mysqli_fetch_assoc($result)) {
		$cat_skill_id = htmlspecialchars(strip_tags($row['cat_skill_id']));
		$cat_skill_title = htmlspecialchars(strip_tags($row['cat_skill_title']));
		$cat_skill_color = htmlspecialchars(strip_tags($row['cat_skill_color']));
		$cat_skill_icon = htmlspecialchars(strip_tags($row['cat_skill_icon']));
		
		$data .= '<tr class="count-group-item bg-white" data-id="'.$cat_skill_id.'">
				  <td class="align-middle text-center" style="width:3%;">
					  <div class="form-check form-switch mt-2 ms-2">
						  <input type="checkbox" class="form-check-input group-chk-box manage" id="'.$cat_skill_id.'" data-cat-skill-id="'.$cat_skill_id.'" onclick="searchTog();">
						  <label class="form-check-label" for="'.$cat_skill_id.'"></label>
					  </div>
				  </td>
				  <td class="cat_drag_icon align-middle text-center" style="cursor:move; width:3%;"><i class="fas fa-bars"></i></td>
				  
				  <td class="text-center" style="width:3%">
					  <button type="button" class="btn cat_edit_color" onclick="editSkillCategory('.$cat_skill_id.')">
						  <i class="'.$cat_skill_icon.'" style="font-size:16px; color:'.$cat_skill_color.'"></i>
					  </button>
				  </td>
				  <td class="cat_title align-middle" onclick="editSkillCategory('.$cat_skill_id.')"  style="cursor:pointer">'.$cat_skill_title.'</td>
				</tr>';
	}

	mysqli_stmt_close($select_categories);
}

$data .= '</tbody></table>';

echo $data;

mysqli_close($dbc);
?>

<script>
function searchTog(){
	if ($('#delete-all-verse-groups').is(':visible')){
		verseGroupsToggle();
	} else {}
};
</script>