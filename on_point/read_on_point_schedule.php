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
	// Sort schedule
	$(".on_point_drag_icon").mousedown(function(){
		$("#sortable_on_point_row" ).sortable({
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
$("tbody#sortable_on_point_row tr").each(function() {
	selectedItem.push($(this).data("id"));
});
var dataString = "sort_order="+selectedItem;
$.ajax({
	type: "GET",
	url: "ajax/on_point/update_on_point_order.php",
	data: dataString,
	cache: false,
	success: function(data){
		readOnPointSchedule();
	}
});
}
</script>

<script>
$(document).ready(function(){
	$("#on_point_color").prop( "disabled", true );
	$("#on_point_text_color").prop( "disabled", true );
	$("td").removeClass("td-color");
	$("td").removeClass("td-text-color");

	$("#enable-color").click(function() {
		$("#on_point_table td").toggleClass("td-color");
		$("#on_point_color").prop("disabled", function(i, v) { return !v; });

			if($(this).prop("checked") == true){
				$("span.paint-roller").removeClass("editable editable-click");

				$(document).ready(function() {
					var isMouseDown = false,
					isHighlighted;

					$("#on_point_table .td-color")

						.mousedown(function () {
							isMouseDown = true;
							$(this).css("background-color", $("#on_point_color").val());

							return false;
							})

							.mouseover(function () {
								if (isMouseDown) {
									$(this).css("background-color", $("#on_point_color").val());
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

					} else if ($(this).prop("checked") == false){
						$("span.paint-roller").addClass("editable editable-click");
						$("td").removeClass("td-color");
						$("#on_point_table td").off();
					}
				});

				$("#enable-text-color").click(function() {
					$("#on_point_table td").toggleClass("td-text-color");
					$("#on_point_text_color").prop("disabled", function(i, v) { return !v; });

					if($(this).prop("checked") == true){
						$("span.paint-roller").removeClass("editable editable-click");
						$(document).ready(function() {

							var isMouseDown = false,
							isHighlighted;

							$("#on_point_table .td-text-color")
							.mousedown(function () {
								isMouseDown = true;
								$(this).css("color", $("#on_point_text_color").val());
								return false;
							})

							.mouseover(function () {
								if (isMouseDown) {
									$(this).css("color", $("#on_point_text_color").val());
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

					} else if ($(this).prop("checked") == false){
						$("span.paint-roller").addClass("editable editable-click");
						$("td").removeClass("td-text-color");
						$("#on_point_table td").off();
					}
				});
			});
			</script>

			<script>
			$(document).ready(function(){
				$("#edit-row").click(function(){
					$(this).find("i").toggleClass("fa-trash-alt").toggleClass("fa-undo-alt");
				});
				$("#edit-show").click(function(){
					$(this).find("i").toggleClass("fa-sort-amount-up-alt").toggleClass("fa-sort-amount-down-alt");
				});
			});
			</script>';

		$data .='<div class="collapse" id="collapseColorSettings">

			<div class="row">
				<div class="col-md-3">
					<div class="shadow-sm rounded border bg-white p-3 mb-3">
						<div class="form-check form-switch mb-3">
		  		 	   		<input type="checkbox" class="form-check-input" id="enable-color">
		  				  	<label class="form-check-label" for="enable-color">Background color</label>
							<input type="color" class="form-control form-control-color float-end" id="on_point_color" name="on_point_color" value="#fafafa">
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="shadow-sm rounded border bg-white p-3 mb-3">
						<div class="form-check form-switch mb-3">
		  		  			<input type="checkbox" class="form-check-input" id="enable-text-color">
		  			  		<label class="form-check-label" for="enable-text-color">Text color</label>
							<input type="color" class="form-control form-control-color float-end" id="on_point_text_color" name="on_point_text_color" value="#fafafa">
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="shadow-sm rounded border bg-white p-3 mb-3">
						<div class="form-check form-switch mb-3">
  							<input type="checkbox" class="form-check-input" id="enable-editing">
  	  						<label class="form-check-label" for="enable-editing">Text editing</label>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="shadow-sm rounded border bg-white p-3 mb-3">
						<div class="d-flex">
							<button type="button" class="btn btn-orange w-100" id="" onclick="saveOnPointColor();"><i class="fas fa-cloud-upload-alt"></i> Save Changes</button>
						</div>
					</div>
				</div>
			</div>

			<hr class="mt-0">
		</div>
	</div>

	<table class="table table-sm table-bordered mb-0" id="on_point_table">
		<thead class="">
			<tr>
				<th class="align-middle text-center" scope="col">
					<div class="btn-group d-flex" role="group" aria-label="Basic example">
						<button type="button" class="btn btn-dark btn-sm" id="add-on-point-row" data-button-svg="" onclick="addOnPointRow();"><i class="fas fa-plus-circle"></i></button>
				  	  	<button type="button" class="btn btn-dark btn-sm" id="edit-row" onclick="showRemoveRow();"><i class="fas fa-trash-alt"></i></button>
						<button type="button" class="btn btn-dark btn-sm" id="edit-show" data-bs-toggle="collapse" data-bs-target="#collapseColorSettings" aria-expanded="false" aria-controls="collapseColorSettings">
							<i class="fas fa-sort-amount-up-alt"></i>
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
		<tbody class="bg-white" id="sortable_on_point_row">';

		$query = "SELECT on_point_id, on_point_time, on_point_monday, on_point_tuesday, on_point_wednesday, on_point_thursday, on_point_friday, on_point_saturday, on_point_sunday, on_point_monday_color, on_point_tuesday_color, on_point_wednesday_color, on_point_thursday_color, on_point_friday_color, on_point_saturday_color, on_point_sunday_color, on_point_monday_text_color, on_point_tuesday_text_color, on_point_wednesday_text_color, on_point_thursday_text_color, on_point_friday_text_color, on_point_saturday_text_color, on_point_sunday_text_color FROM on_point ORDER BY on_point_display_order ASC";

		    if ($stmt = mysqli_prepare($dbc, $query)) {
		   	 	mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $on_point_id, $on_point_time, $on_point_monday, $on_point_tuesday, $on_point_wednesday, $on_point_thursday, $on_point_friday, $on_point_saturday, $on_point_sunday, $on_point_monday_color, $on_point_tuesday_color, $on_point_wednesday_color, $on_point_thursday_color, $on_point_friday_color, $on_point_saturday_color, $on_point_sunday_color, $on_point_monday_text_color, $on_point_tuesday_text_color, $on_point_wednesday_text_color, $on_point_thursday_text_color, $on_point_friday_text_color, $on_point_saturday_text_color, $on_point_sunday_text_color);

		    	while (mysqli_stmt_fetch($stmt)) {
					$on_point_id = htmlspecialchars($on_point_id ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_time = htmlspecialchars($on_point_time ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_monday = htmlspecialchars($on_point_monday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_tuesday = htmlspecialchars($on_point_tuesday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_wednesday = htmlspecialchars($on_point_wednesday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_thursday = htmlspecialchars($on_point_thursday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_friday = htmlspecialchars($on_point_friday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_saturday = htmlspecialchars($on_point_saturday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_sunday = htmlspecialchars($on_point_sunday ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_monday_color = htmlspecialchars($on_point_monday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_tuesday_color = htmlspecialchars($on_point_tuesday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_wednesday_color = htmlspecialchars($on_point_wednesday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_thursday_color = htmlspecialchars($on_point_thursday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_friday_color = htmlspecialchars($on_point_friday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_saturday_color = htmlspecialchars($on_point_saturday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_sunday_color = htmlspecialchars($on_point_sunday_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_monday_text_color = htmlspecialchars($on_point_monday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_tuesday_text_color = htmlspecialchars($on_point_tuesday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_wednesday_text_color = htmlspecialchars($on_point_wednesday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_thursday_text_color = htmlspecialchars($on_point_thursday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_friday_text_color = htmlspecialchars($on_point_friday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_saturday_text_color = htmlspecialchars($on_point_saturday_text_color ?? '', ENT_QUOTES, 'UTF-8');
					$on_point_sunday_text_color = htmlspecialchars($on_point_sunday_text_color ?? '', ENT_QUOTES, 'UTF-8');
		            
					$data.='<script>

					$(document).ready(function() {
						$.fn.editable.defaults.mode = "inline";

						$("#on_point_time_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_monday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_tuesday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_wednesday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_thursday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_friday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_saturday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

						$("#on_point_sunday_'.$on_point_id.'").editable({
							clear: "false",
							placement: "top",
							success: function(response, newValue) {
							}
						});

					});

					$(document).ready(function(){
						$("#on_point_time_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_monday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_tuesday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_wednesday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_thursday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_friday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_saturday_'.$on_point_id.'").editable("option", "disabled", true);
						$("#on_point_sunday_'.$on_point_id.'").editable("option", "disabled", true);

							$("#enable-editing").click(function() {
								$("#on_point_time_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_monday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_tuesday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_wednesday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_thursday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_friday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_saturday_'.$on_point_id.'").editable("toggleDisabled");
								$("#on_point_sunday_'.$on_point_id.'").editable("toggleDisabled");
							});
					});
					</script>

					<input type="hidden" id="hidden_on_point_id" name="hidden_on_point_id" value="'. $on_point_id .'">

					<tr class="" data-id="'.$on_point_id.'">
						<th class="thead-dark text-white" scope="row">
		 					<div class="tab-content">
		 						<div class="tab-pane fade in remove_row_tab">
									<div class="d-grid">
		 								<button type="button" class="btn btn-outline-warning btn-sm btn-block" id="" onclick="removeOnPointRow('.$on_point_id.')">Remove</button>
									</div>
								</div>
		 						<div class="tab-pane fade in show active time_tab">
									<span id="on_point_time_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_time.php" data-title="Enter Time">' . $on_point_time  .' </span>
								</div>
		 					</div>
		 				</th>

						<td class="td-color td-text-color" style="color: '. $on_point_monday_text_color .'; background-color: '. $on_point_monday_color .';" data-point-id="'. $on_point_id .'" data-day="monday">
							<span class="paint-roller" id="on_point_monday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_monday.php" style="" data-title="Enter Name">' . $on_point_monday  .' </span>
						</td>
						<td class="td-color td-text-color" style="color: '. $on_point_tuesday_text_color .'; background-color: '. $on_point_tuesday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="tuesday">
							<span class="paint-roller" id="on_point_tuesday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_tuesday.php" style="" data-title="Enter Name">' . $on_point_tuesday  .' </span>
						</td>
		  				<td class="td-color td-text-color" style="color: '. $on_point_wednesday_text_color .'; background-color: '. $on_point_wednesday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="wednesday">
							<span class="paint-roller" id="on_point_wednesday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_wednesday.php" style="" data-title="Enter Name">' . $on_point_wednesday  .' </span>
						</td>
		  				<td class="td-color td-text-color" style="color: '. $on_point_thursday_text_color .'; background-color: '. $on_point_thursday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="thursday">
							<span class="paint-roller" id="on_point_thursday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_thursday.php" style="" data-title="Enter Name">' . $on_point_thursday  .' </span>
						</td>
		  				<td class="td-color td-text-color" style="color: '. $on_point_friday_text_color .'; background-color: '. $on_point_friday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="friday">
							<span class="paint-roller" id="on_point_friday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_friday.php" style="" data-title="Enter Name">' . $on_point_friday  .' </span>
						</td>
		  				<td class="td-color td-text-color" style="color: '. $on_point_saturday_text_color .'; background-color: '. $on_point_saturday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="saturday">
							<span class="paint-roller" id="on_point_saturday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_saturday.php" style="" data-title="Enter Name">' . $on_point_saturday  .' </span>
						</td>
		  				<td class="td-color td-text-color" style="color: '. $on_point_sunday_text_color .'; background-color: '. $on_point_sunday_color .' !important;" data-point-id="'. $on_point_id .'" data-day="sunday">
							<span class="paint-roller" id="on_point_sunday_'.$on_point_id.'" data-type="text" data-pk="'. $on_point_id .'" data-url="ajax/x_editable/on_point/on_point_sunday.php" style="" data-title="Enter Name">' . $on_point_sunday  .' </span>
						</td>

						<th class="on_point_drag_icon align-middle grab text-center" width="3%">
		  					<span class="btn btn-sm btn-light btn-outline handler ui-sortable-handle">
		  						<i class="fas fa-arrows-alt"></i>
		  					</span>
		  				</th>
					</tr>';
				}
			}

	$data .='</tbody></table>';

echo $data;

}

?>

<script>
$(document).ready(function(){
	$('#add-on-point-row').on('click', function () {
		var $this = $(this);
	  	$this.data("obtn", $this.html());
	 	 var nhtml = "<span class='spinner-grow spinner-grow-sm' role='status' aria-hidden='true'></span> " + this.dataset.buttonSvg;
	  	$this.html(nhtml);
	  	$this.attr("disabled", true);

	 	setTimeout(function () {
	    	$this.html($this.data("obtn"));
	   	 	$this.attr("disabled", false);
		}, 800);
	});
});
</script>