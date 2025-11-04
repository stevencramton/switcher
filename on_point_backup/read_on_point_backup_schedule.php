<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('on_point_view')) {
    header("Location:../../index.php?msg1");
}

if(isset($_SESSION['user'])){

	$data ='<script>
		$(document).ready(function(){
			$(".on_point_backup_drag_icon").mousedown(function(){
				$( "#sortable_on_point_backup_row" ).sortable({
					update: function( event, ui ) {
						updateBackupDisplayRowOrder();
					}
					});
				});

			});
		</script>

		<script>
		function updateBackupDisplayRowOrder() {
        	var selectedItem = new Array();

			$("tbody#sortable_on_point_backup_row tr").each(function() {
				selectedItem.push($(this).data("id"));
			});

			var dataString = "sort_order_backup="+selectedItem;

			$.ajax({
	  			type: "GET",
	  			url: "ajax/on_point_backup/update_on_point_backup_order.php",
	  		  	data: dataString,
	  		  	cache: false,
	  		  	success: function(data){
	    			readOnPointBackupSchedule();
	  		  		}
				});

			}
		</script>
		
		<script>
		$(function () {

			var isMouseDown = false,
			isHighlighted;

			$("#on_point_backup_table .td-color-backup")

			.mousedown(function () {
				isMouseDown = true;
				$(this).css("background-color", $("#on_point_backup_color").val());

				return false;
				})

			.mouseover(function () {
				if (isMouseDown) {
					$(this).css("background-color", $("#on_point_backup_color").val());
				}
			})

			.bind("selectstart", function () {
				return false;
			})

			$(document)
				.mouseup(function () {
				isMouseDown = false;
			});
			
		});
		
		</script>
	
	<div class="collapse" id="collapseBackupColorSettings">
		<div class="col-md-12">
			<div class="shadow-sm rounded border bg-white p-3 mb-3">
				<input type="color" class="form-control-color" id="on_point_backup_color" name="on_point_backup_color" value="#c2c2c2">
				<span class="btn-group float-end mb-3" role="group" aria-label="Basic example">
					<button type="button" class="btn btn-dark" id="add-on-point-backup-row" onclick="addOnPointBackupRow();"><i class="fas fa-plus-circle"></i> Add Row</button>
			  	  	<button type="button" class="btn btn-dark" id="" onclick="saveOnPointBackupColor();"><i class="fas fa-cloud-upload-alt"></i> Update</button>
			  	  	<button type="button" class="btn btn-dark" id="edit-row" onclick="showRemoveBackupRow();"><i class="fas fa-trash-alt"></i></button>
				</div>
			</div>
		</div>
	</div> 

	<table class="table table-sm table-bordered mb-0" id="on_point_backup_table">
		<thead class="">
			<tr>
				<th class="align-middle text-center" scope="col">
					<div class="btn-group d-flex" role="group" aria-label="Basic example">
						<button type="button" class="btn btn-sm btn-dark w-100" id="" data-button-svg="" data-bs-toggle="collapse" data-bs-target="#collapseBackupColorSettings" aria-expanded="false" aria-controls="collapseColorSettings">
							<i class="fas fa-cog"></i>
						</button>
					    
					</div>
				</th>
				<th class="align-middle text-center" scope="col">Monday</th>
				<th class="align-middle text-center" scope="col">Tuesday</th>
				<th class="align-middle text-center" scope="col">Wednesday</th>
				<th class="align-middle text-center" scope="col">Thursday</th>
				<th class="align-middle text-center" scope="col">Friday</th>
				<th class="align-middle text-center" scope="col">Saturday</th>
				<th class="align-middle text-center" scope="col">Sunday</th>
				<th class="align-middle text-center" scope="col">Sort</th>
			 </tr>
		</thead>
		<tbody class="bg-white" id="sortable_on_point_backup_row">';
		
		$query = "SELECT on_point_backup_id, on_point_backup_time, on_point_backup_monday, on_point_backup_tuesday, on_point_backup_wednesday, on_point_backup_thursday, on_point_backup_friday, on_point_backup_saturday, on_point_backup_sunday, on_point_backup_monday_color, on_point_backup_tuesday_color, on_point_backup_wednesday_color, on_point_backup_thursday_color, on_point_backup_friday_color, on_point_backup_saturday_color, on_point_backup_sunday_color FROM on_point_backup ORDER BY on_point_backup_display_order ASC";

		if ($stmt = mysqli_prepare($dbc, $query)) {
			mysqli_stmt_execute($stmt);

		    mysqli_stmt_bind_result($stmt, $on_point_backup_id, $on_point_backup_time, $on_point_backup_monday, $on_point_backup_tuesday, $on_point_backup_wednesday, $on_point_backup_thursday, $on_point_backup_friday, $on_point_backup_saturday, $on_point_backup_sunday, $on_point_backup_monday_color, $on_point_backup_tuesday_color, $on_point_backup_wednesday_color, $on_point_backup_thursday_color, $on_point_backup_friday_color, $on_point_backup_saturday_color, $on_point_backup_sunday_color);

			while (mysqli_stmt_fetch($stmt)) {
		       	$on_point_backup_id = htmlspecialchars($on_point_backup_id ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_time = htmlspecialchars($on_point_backup_time ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_monday = htmlspecialchars($on_point_backup_monday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_tuesday = htmlspecialchars($on_point_backup_tuesday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_wednesday = htmlspecialchars($on_point_backup_wednesday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_thursday = htmlspecialchars($on_point_backup_thursday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_friday = htmlspecialchars($on_point_backup_friday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_saturday = htmlspecialchars($on_point_backup_saturday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_sunday = htmlspecialchars($on_point_backup_sunday ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_monday_color = htmlspecialchars($on_point_backup_monday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_tuesday_color = htmlspecialchars($on_point_backup_tuesday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_wednesday_color = htmlspecialchars($on_point_backup_wednesday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_thursday_color = htmlspecialchars($on_point_backup_thursday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_friday_color = htmlspecialchars($on_point_backup_friday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_saturday_color = htmlspecialchars($on_point_backup_saturday_color ?? '', ENT_QUOTES, 'UTF-8');
				$on_point_backup_sunday_color = htmlspecialchars($on_point_backup_sunday_color ?? '', ENT_QUOTES, 'UTF-8');
		        
					$data .='<script>

					$(document).ready(function() {
						$.fn.editable.defaults.mode = "inline";

						$("#on_point_backup_time_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_monday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_tuesday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_wednesday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_thursday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_friday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_saturday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_backup_sunday_'.$on_point_backup_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

					});
					</script> 

					<input type="hidden" id="hidden_on_point_backup_id" name="hidden_on_point_backup_id" value="'. $on_point_backup_id .'">

					<tr class="" data-id="'. $on_point_backup_id .'">
						<th class="thead-dark text-white" scope="row">
		 					<div class="tab-content">
		 						<div class="tab-pane fade in remove_backup_row_tab">
									<div class="d-grid">
		 								<button type="button" class="btn btn-danger btn-sm btn-block" id="" onclick="removeOnPointBackupRow('.$on_point_backup_id.')">Remove</button>
									</div>
								</div>
		 						<div class="tab-pane fade in show active time_backup_tab">
									<span id="on_point_backup_time_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_time.php" data-title="Enter Time">' . $on_point_backup_time  .' </span>
								</div>
		 					</div>
		 				</th>

						<td class="td-color-backup" style="background-color: '. $on_point_backup_monday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="monday">
							<span id="on_point_backup_monday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_monday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_monday  .' </span>
						</td>
						<td class="td-color-backup" style="background-color: '. $on_point_backup_tuesday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="tuesday">
							<span id="on_point_backup_tuesday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_tuesday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_tuesday  .' </span>
						</td>
		  				<td class="td-color-backup" style="background-color: '. $on_point_backup_wednesday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="wednesday">
							<span id="on_point_backup_wednesday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_wednesday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_wednesday  .' </span>
						</td>
		  				<td class="td-color-backup" style="background-color: '. $on_point_backup_thursday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="thursday">
							<span id="on_point_backup_thursday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_thursday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_thursday  .' </span>
						</td>
		  				<td class="td-color-backup" style="background-color: '. $on_point_backup_friday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="friday">
							<span id="on_point_backup_friday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_friday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_friday  .' </span>
						</td>
		  				<td class="td-color-backup" style="background-color: '. $on_point_backup_saturday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="saturday">
							<span id="on_point_backup_saturday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_saturday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_saturday  .' </span>
						</td>
		  				<td class="td-color-backup" style="background-color: '. $on_point_backup_sunday_color .' !important;" data-point-backup-id="'. $on_point_backup_id .'" data-day-backup="sunday">
							<span id="on_point_backup_sunday_'.$on_point_backup_id.'" data-type="text" data-pk="'. $on_point_backup_id .'" data-url="ajax/x_editable/on_point_backup/on_point_backup_sunday.php" style="border: none;" data-title="Enter Name">' . $on_point_backup_sunday  .' </span>
						</td>

						<td class="on_point_backup_drag_icon align-middle grab text-center" width="3%">
		  					<span class="btn btn-sm btn-light btn-outline handler ui-sortable-handle">
		  						<i class="fas fa-arrows-alt"></i>
		  					</span>
		  				</td>
					</tr>';
				}
			}

	$data .='</tbody></table>';

echo $data;

}
?>