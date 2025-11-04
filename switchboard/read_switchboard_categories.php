<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_categories')) {
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_SESSION['id'])) {
	
$data ='<script>
$(document).ready(function(){
	$(".switch_drag_icon").mousedown(function(){
		$( "#sortable_switch_cat_row" ).sortable({
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
	$("tbody#sortable_switch_cat_row tr").each(function() {
		selectedItem.push($(this).data("id"));
	});
	var dataString = "sort_order="+selectedItem;

	$.ajax({
		type: "GET",
  	  	url: "ajax/switchboard/update_switch_cat_display_order.php",
  	  	data: dataString,
  	  	cache: false,
  	  	success: function(data){
    		readSwitchboardCategories();
    		readSwitchboardCatLinks();
			var toastTrigger = document.getElementById("sortable_switch_cat_row")
			var toastLiveExample = document.getElementById("toast-switch-cat-order")
			if (toastTrigger) {
	  			var toast = new bootstrap.Toast(toastLiveExample)
				toast.show()
	 	   	}
		}
	});
}
</script>

<table class="table table-bordered table-hover">
	<thead class="table-secondary text-center">
		<tr>
      		<th></th>
      	  	<th></th>
      	  	<th>Category Name (click to edit)</th>
      	  	<th></th>
    	</tr>
	</thead>
	<tbody id="sortable_switch_cat_row">';

	$query = "SELECT * FROM switchboard_categories ORDER BY switchboard_cat_display_order ASC";

	if($stmt = mysqli_prepare($dbc, $query)){
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		if(mysqli_num_rows($result) == 0){
			$data .='<tr>
				<td></td>
				<td></td>
				<td class="text-center"><em> No Categories Found</em></td>
				<td></td>
			</tr>';
		} else {
			while($row = mysqli_fetch_assoc($result)) {
				$switchboard_cat_name = htmlspecialchars(strip_tags($row['switchboard_cat_name']));
				$switchboard_cat_icon = htmlspecialchars(strip_tags($row['switchboard_cat_icon']));
				$switchboard_cat_display_order = htmlspecialchars(strip_tags($row['switchboard_cat_display_order']));
				$switchboard_cat_id = htmlspecialchars(strip_tags($row['switchboard_cat_id']));
				$switchboard_cat_color = htmlspecialchars(strip_tags($row['switchboard_cat_color']));
				
				$data .='<tr data-id="'.$switchboard_cat_id.'">
					<td class="switch_drag_icon align-middle" width="3%"><i class="fas fa-bars"></i></td>
					<td class="text-center align-middle" style="width:3%" onclick="readSwitchboardCategoryDetails('.$switchboard_cat_id.')" style="cursor:pointer">
						<i class="'.$switchboard_cat_icon.'" style="color:'.$switchboard_cat_color.'"></i>
					</td>
					<td class="cat_title align-middle" onclick="readSwitchboardCategoryDetails('.$switchboard_cat_id.')" style="cursor:pointer">'.$switchboard_cat_name.'</td>
					<td style="width:5%;">';
		
				if($switchboard_cat_id != 0){
					$data .='<button type="button" class="btn btn-sm btn-pink" onclick="deleteSwitchCategory('.$switchboard_cat_id.')"><i class="fas fa-trash-alt"></i></button>';
				}
				
				$data .='</td></tr>';
			}
		}
		mysqli_stmt_close($stmt);
	}

$data .='</tbody></table>';
echo $data;

}
mysqli_close($dbc);