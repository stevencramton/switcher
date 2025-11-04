<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (isset($_SESSION['id'])) {
	$luncheon_user = strip_tags($_SESSION['user']);
    $luncheon_switch_id = strip_tags($_SESSION['switch_id']);
    $user = strip_tags($_SESSION['user']);
	$query = "SELECT * FROM luncheon WHERE luncheon_sender = ?";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $user);

        if (mysqli_stmt_execute($stmt)) {
            $select_alerts = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($select_alerts) > 0) {
                while ($row = mysqli_fetch_assoc($select_alerts)) {
					$luncheon_id = strip_tags($row['luncheon_id']);
                    $luncheon_color = strip_tags($row['luncheon_color']);
                    $luncheon_time_start = strip_tags($row['luncheon_time_start']);
                    $luncheon_time_end = strip_tags($row['luncheon_time_end']);
                    $luncheon_status = strip_tags($row['luncheon_status']);

                    if ($luncheon_status == 1) {
                        $status_icon = "<i class='fas fa-eye-slash' id='edit_luncheon_status'></i>";
                    } else {
                        $status_icon = "<i class='fas fa-eye' id='edit_luncheon_status'></i>";
                    }

		 	 	   $data ='<style>
				   #our_table_update.table-bordered thead td, .table-bordered thead th {
		 		    	border-bottom-width: none !important;
		 			}

		 			#our_table_update.table thead th {
		 		    	vertical-align: bottom;
		 		    	border-bottom: none !important;
		 				border-bottom-color: rgb(222, 226, 230);
		 			}
					</style>
					
					<script>
		 			$(document).ready(function(){
						$("#edit_luncheon_color").on("change", function(){
		 					var new_color = $("#edit_luncheon_color").val();
		 					$("#highlighted_css").html("table td.td-time.highlighted {background-color:"+new_color+"; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;}")
						})
		 			});
		 			</script>
					
					<script>
					$(function () {
						var isMouseDown = false,
		 				isHighlighted;

		 				$("#our_table_update td")

		 				.mousedown(function () {
		 					isMouseDown = true;
		 					$(this).toggleClass("highlighted");
		 					isHighlighted = $(this).hasClass("highlighted");
		 					return false; // prevent text selection
		 					})

		 				.mouseover(function () {
		 					if (isMouseDown) {
		 						$(this).toggleClass("highlighted", isHighlighted);
		 					}
		 				})

		 				.bind("selectstart", function () {
		 					return false;
		 				})

		 				$(document)
		 					.mouseup(function () {
		 						$("table.new .highlighted:first").each(function() {
									var luncheon_time_start = $(this).text();
		 							$("#edit_luncheon_time_start").val(luncheon_time_start).html(luncheon_time_start);
								});
		 						$("table.new .highlighted:last").each(function() {
									var luncheon_time_end = $(this).text();
		 							$("#edit_luncheon_time_end").val(luncheon_time_end).html(luncheon_time_end);
								});
		 					isMouseDown = false;
		 				});
		 			});
					</script>
					<script>
		 			$(document).ready(function(){
		 				editLunchTime('.$luncheon_id.');
		 			});
		 			</script>
					<script>
		 			$(document).ready(function(){
						$("table.new .highlighted:first").each(function() {
							var luncheon_time_start = $(this).text();
		 					$("#edit_luncheon_time_start").val(luncheon_time_start).html(luncheon_time_start);
						});
						$("table.new .highlighted:last").each(function() {
							var luncheon_time_end = $(this).text();
		 					$("#edit_luncheon_time_end").val(luncheon_time_end).html(luncheon_time_end);
						});
					});
		 			</script>
					<script>
		 			$(document).ready(function(){
						$("#edit_luncheon_view").click(function(){
		 	   		 		$("#edit_luncheon_status").toggleClass("fa-eye fa-eye-slash");
		 					$("#edit_luncheon_status_away").toggleClass("fa-eye fa-eye-slash");
		 				});
					});
		 			</script>

		 			<div class="mb-2">
						<input type="color" class="form-control-color" name="edit_luncheon_color" id="edit_luncheon_color" value="';

						if(empty($luncheon_color)){
							$data .='#aad400';
						} else {
							$data .= $luncheon_color;
						}

						$data .='" ">
						<div class="btn-group float-end" role="group" aria-label="Basic example">
                            <button type="button" class="btn btn-orange" onclick="updateLunchTime(' . $luncheon_id . ');">
								<i class="fas fa-cloud-upload-alt"></i> Update Time
							</button>
                            <button type="button" class="btn btn-orange-dark" data-bs-toggle="modal" data-bs-target="#timeModal">
								<i class="fas fa-info-circle"></i>
							</button>
                        </div>
                        <div class="btn-group float-end me-1" role="group" aria-label="edit_luncheon_status">
                            <button type="button" class="btn btn-outline-secondary" id="edit_luncheon_view">' . $status_icon . '</button>
                        </div>
                        <button type="button" class="btn btn-outline-secondary float-end me-1" onclick="deleteLunchTime(' . $luncheon_id . ');">
							<i class="fas fa-trash-alt"></i>
						</button>
						<input type="hidden" id="hidden_luncheon_id" name="hidden_luncheon_id" value="">

		 			</div>

		 			<div class="table-responsive">
						<table class="table table-sm table-bordered new" id="our_table_update">
                            <thead class="table-secondary">
                            <tr>';
							
							$time_query = "SELECT * FROM luncheon_admin";
								if($time_result = mysqli_query($dbc, $time_query)){
									while($time_row = mysqli_fetch_array($time_result)){

										$new_time = "";
								 		$time_format = $time_row['time_format'];
								 		$start_time = $time_row['start_time'];
								 		$start_time = strtotime($start_time);
								 		$end_time = $time_row['end_time'];
								 		$end_time = strtotime($end_time);
								 		$x= 0;

								 	while($new_time < $end_time){

										if($x == 0){
											$data .='<th class="cell-square-start" colspan="4">'.date(''.$time_format.'',$start_time).'</th>';
											$new_time = $start_time + 3600;
											$x++;
										} else if($x != 0){
											$data .='<th class="cell-square-start" colspan="4">'.date(''.$time_format.'', $new_time).'</th>';
											$new_time = $new_time + 3600;
											$x++;
										}
									}

									if ($new_time == $end_time){
										$data .= '';
									}
								}
							}

							$data .='</tr></thead><tbody>';
							
							$luncheon_sender = strip_tags($_SESSION['user']);
							$luncheon_query = "SELECT * FROM luncheon WHERE luncheon_sender = ?";

                    		if ($stmt2 = mysqli_prepare($dbc, $luncheon_query)) {
                        		mysqli_stmt_bind_param($stmt2, "s", $luncheon_sender);

                        		if (mysqli_stmt_execute($stmt2)) {
                            		$select_luncheon_record = mysqli_stmt_get_result($stmt2);

                            		while ($row = mysqli_fetch_assoc($select_luncheon_record)) {
                               		 	$data .= '<tr>';

                                		$luncheon_submitter = strip_tags($row['luncheon_sender']);
										$submitter_query = "SELECT * FROM users WHERE user = ? AND account_delete = 0";

                               		 	if ($stmt3 = mysqli_prepare($dbc, $submitter_query)) {
                                    		mysqli_stmt_bind_param($stmt3, "s", $luncheon_submitter);

                                    		if (mysqli_stmt_execute($stmt3)) {
                                        		$submitter_result = mysqli_stmt_get_result($stmt3);
                                        		$submitter_row = mysqli_fetch_array($submitter_result);
												$submitter_first_name = strip_tags($submitter_row['first_name']);
                                        		$submitter_last_name = strip_tags($submitter_row['last_name']);
												$submitter_name = $submitter_first_name . ' ' . $submitter_last_name;
												$luncheon_id = strip_tags($row['luncheon_id']);
                                       	 	   	$luncheon_sender = strip_tags($row['luncheon_sender']);
                                       		 	$luncheon_status = strip_tags($row['luncheon_status']);

                                        		if ($luncheon_status == 1) {
                                            		$status_icon = "<i class='fas fa-eye-slash dark-gray'></i>";
                                            		$status_checked = "checked";
                                            		$table_status = "Away";
                                        		} else {
                                            		$status_icon = "<i class='fas fa-eye dark-gray'></i>";
                                            		$status_checked = "";
                                            		$table_status = "Available";
                                        		}

                                        		$col_names = [];
                                        		$search = "time_cell_";

                                        		foreach ($row as $key => $value) {
                                            		if (strpos($key, $search) !== false) {
                                                		$col_names[] = $key;
                                           		 	}
                                        		}

                                        		$col_count = count($col_names);
                                        		$a = 1;
                                        		$x = 0;

                                        		foreach ($col_names as $key => $value) {

                                            		$begin_time_string = substr($value, 10);
                                            		$begin_time_string = substr_replace($begin_time_string, ':', 2, 0);
                                            		$begin_time_string = strtotime($begin_time_string);
                                            		$end_time_string = $begin_time_string + 900;
                                            		$begin_time_string = date('h:i a', $begin_time_string);
                                            		$end_time_string = date('h:i a', $end_time_string);

                                            if ($x == 0 && $a < $col_count) {
                                                if ($row[$value] == 1) {
                                                    $data .= '<td class="highlighted td-time cell-square-start" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                } else {
                                                    $data .= '<td class="td-time cell-square-start" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                }
                                                $x++;
                                                $a++;
                                            } else if ($x < 3 && $a < $col_count) {
                                                if ($row[$value] == 1) {
                                                    $data .= '<td class="highlighted td-time cell-square" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                } else {
                                                    $data .= '<td class=" td-time cell-square" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                }
                                                $x++;
                                                $a++;
                                            } else if ($x == 3 && $a < $col_count) {
                                                if ($row[$value] == 1) {
                                                    $data .= '<td class="highlighted td-time cell-square-end" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                } else {
                                                    $data .= '<td class="td-time cell-square-end" id="' . $value . '"><span style="display:none;">' . $begin_time_string . ' and ' . $end_time_string . '</span></td>';
                                                }
                                                $x = 0;
                                                $a++;
                                            } else if ($a == $col_count) {
                                                $data .= "";
                                            }
                                        }
                                    }
                                }
                                $data .= '</tr></tbody></table></div>

                                <div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Table Cell Status</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <p class="lh-lg"><strong>Square Border:</strong><br>
                                                    No border for unselected, thin border for selected (past or future), thick border for the current hour.<br>
                                                    <strong>Square Color:</strong><br>
                                                    Grey for unselected, blue for selected.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-orange" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                            }
                        }
                    }
                }
            } else {
               
				$data ='<style>
					#our_table.table-bordered thead td, .table-bordered thead th {
				    border-bottom-width: none !important;
				}

				#our_table.table thead th {
				    vertical-align: bottom;
				    border-bottom: none !important;
					border-bottom-color: rgb(222, 226, 230);
				}
				</style>

				<script>
				$(document).ready(function(){
					var new_color = $("#luncheon_color").val();
					$("#highlighted_css").html("table td.td-time.highlighted {background-color:"+new_color+"; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;	}")

					$("#luncheon_color").on("change", function(){
						var new_color = $("#luncheon_color").val();
						$("#highlighted_css").html("table td.td-time.highlighted {background-color:"+new_color+"; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;	}")
					})
				});
				</script>

				<script>
					$(function () {
						var isMouseDown = false,
						isHighlighted;

						$("#our_table td")

						.mousedown(function () {
							isMouseDown = true;
							$(this).toggleClass("highlighted");
							isHighlighted = $(this).hasClass("highlighted");
							return false;
						})

						.mouseover(function () {
							if (isMouseDown) {
								$(this).toggleClass("highlighted", isHighlighted);
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

					<script>
					$(document).ready(function(){
						$("#luncheon_view").click(function(){
							$("#luncheon_status").toggleClass("fa-eye fa-eye-slash");
						});
					});
					</script>

					<div class="mb-2">
						<label for="favcolor">Select your color:</label>
						<input type="color" class="form-control-color" id="luncheon_color" name="luncheon_color" value="#aad400">
						<input type="hidden" id="luncheon_sender" value="'.$luncheon_user.'">
						<input type="hidden" id="luncheon_switch_id" value="'.$luncheon_switch_id.'">
						<button type="button" class="btn btn-primary float-end" onclick="sendLunchTime();"><i class="far fa-check-circle"></i> Submit Time</button>
						<button type="button" class="btn btn-outline-secondary float-end me-1" id="luncheon_view"><i class="fas fa-eye" id="luncheon_status"></i></button>
					</div>

					<div class="table-responsive">
						<table class="table table-sm table-bordered start" id="our_table">
							<thead class="table-secondary">
							<tr>';
							
							$time_query = "SELECT * FROM luncheon_admin";

								if($time_result = mysqli_query($dbc, $time_query)){

									while($time_row = mysqli_fetch_array($time_result)){
										$new_time = "";
								 	   	$time_format = $time_row['time_format'];
								 	  	$start_time = $time_row['start_time'];
								 	 	$start_time = strtotime($start_time);
								 		$end_time = $time_row['end_time'];
								 		$end_time = strtotime($end_time);
								 		$x= 0;

									while($new_time < $end_time){

									if($x == 0){
										$data .='<th class="cell-square-start" colspan="4">'.date(''.$time_format.'',$start_time).'</th>';
										$new_time = $start_time + 3600;
										$x++;
									} else if($x != 0){

										$data .='<th class="cell-square-start" colspan="4">'.date(''.$time_format.'', $new_time).'</th>';
										$new_time = $new_time + 3600;
										$x++;
									}
								}
								if ($new_time == $end_time){
									$data .='';
								}
							}
						}

						$data .='</thead><tbody><tr>';

						$col_names = [];

						$col_name_query = "select * from information_schema.columns where table_name='luncheon' AND column_name LIKE 'time_cell%'";

							if($col_name_result = mysqli_query($dbc, $col_name_query)){
								while($col_name_row = mysqli_fetch_array($col_name_result)){
									array_push($col_names, $col_name_row['COLUMN_NAME']);
								}
							}

						$col_count = count($col_names);

						$a = 1;
						$x = 0;

						foreach($col_names as $key => $value){
							$begin_time_string = substr($value, 10);
							$begin_time_string = substr_replace($begin_time_string, ':', 2, 0);
							$begin_time_string = strtotime($begin_time_string);
							$end_time_string = $begin_time_string + 900;
							$begin_time_string = date('h:i a',$begin_time_string);
							$end_time_string = date('h:i a',$end_time_string);

							$luncheon_sender = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
							$luncheon_query = "SELECT * FROM luncheon WHERE luncheon_sender = '$luncheon_sender'";

								if(mysqli_num_rows($select_luncheon_record = mysqli_query($dbc, $luncheon_query))!=0){
									$row = mysqli_fetch_assoc($select_luncheon_record);
							
									if($x == 0 && $a < $col_count){
									
										if($row[$value] == 1){
											$data .='<td class="td-time highlighted_'.$luncheon_id.'" id="'.$value.'"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										} else {
											$data .='<td id="'.$value.'" class="td-time"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										}
									
										$x++;
										$a++;
								
									} else if($x < 3 && $a < $col_count) {
									
										if($row[$value] == 1){
											$data .='<td class="td-time highlighted_'.$luncheon_id.'" id="'.$value.'"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										} else {
											$data .='<td id="'.$value.'" class="td-time" ><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										}
									
										$x++;
										$a++;
							
									} else if($x == 3 && $a < $col_count){
									
										if($row[$value] == 1){
											$data .='<td class="td-time highlighted_'.$luncheon_id.'" id="'.$value.'"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										} else {
											$data .='<td  id="'.$value.'" class="td-time"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										}
									
										$x = 0;
										$a++;
								
									} else if($a == $col_count){
										$data .= "";
									}
								} else {
							
									if($x == 0 && $a < $col_count){
										$data.='<td id="'.$value.'" class="td-time"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										$x++;
										$a++;
									
									} else if($x < 3 && $a < $col_count) {
									
										$data .='<td id="'.$value.'" class="td-time" ><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										$x++;
										$a++;
									
									} else if($x == 3 && $a < $col_count){
									
										$data .='<td  id="'.$value.'" class="td-time"><span style="display:none;">'.$begin_time_string. ' and ' .$end_time_string.'</span></td>';
										$x = 0;
										$a++;
									
									} else if($a == $col_count){
										$data .="";
									}
								}
							}
							$data .='</tr>

							</tbody>
						</table>
					</div>';
			}
        } else {
            $data = '<p class="text-center">An error occurred while executing the query.</p>';
        }

        mysqli_stmt_close($stmt);
    } else {
        $data = '<p class="text-center">An error occurred while preparing the query.</p>';
    }
	
	echo $data;
	
	mysqli_close($dbc);
}