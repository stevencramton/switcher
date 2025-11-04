<?php
session_start(); // Start session

include '../../mysqli_connect.php'; // Open connection to database
include '../../templates/functions.php'; // Open functions file
?>

<link rel="stylesheet" href="plugins/sort/basscss.css">
<script src=""></script>
<script src="plugins/sort/html5sortable.js"></script>

<style>
	
	.ui-sortable-helper {
		height:60px !important;
	}

	.lock:hover .icon-unlock, .lock .icon-lock {
	    display: none;
	}
	
	.lock:hover .icon-lock {
	    display: inline;
	}
	
	.folder > .highlight-my-link {
	    background-color: #ebf4ff !important;
	    border-bottom: 1px solid #4e9dff !important;
		border-left: none !important;
		border-right: none !important;
		border-top: none !important;
	}
	
</style>

<script>
$(document).ready(function(){
	
	//My Links search function
	$("#search_my_links").on("keyup", function() {

		if ($("#search_my_links").val() == ''){

			$(".input-group-text.my-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");

		} else {

			$(".input-group-text.my-link-search").find('.fa').removeClass('fa-search').addClass("fa-times-circle");

			$('.fa-times-circle').click(function() {

				var value = $(this).val().toLowerCase();
				
				$("div .col_links").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});
				
				$("div .col_folders").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});
				
				$("div .col_header").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});

				$('input[type="text"]').val('').trigger('propertychange').focus();
				$(".input-group-text.my-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");

				$(".card-header").find(".fa-circle").removeClass("fa-circle").addClass("fa-dot-circle");

			});

		}

		var value = $(this).val().toLowerCase();
		
		$("div .col_links").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
		
		$("div .col_folders").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
		
		$("div .col_header").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
		
	});

});
</script>

<script>
$(document).ready(function(){
	
	if ($("div.my_search_select").hasClass("highlight-my-link")){
		
		$('#my_open_selected').prop('disabled', false);
		
	} else {
		
		$('#my_open_selected').prop('disabled', true);
		
	}
	
	var count = $(".highlight-my-link").length;
	var count_zero = '0';
	
	if (count != 0){
		$(".my_count").html(count);
	} else {
		$(".my_count").html(count_zero);
	} 

	$("div.my_search_select").click(function() {
		
	    $(this).toggleClass("highlight-my-link");
		
		if ($("div.my_search_select").hasClass("highlight-my-link")){
			
			var my_link_record = [];

			$(".highlight-my-link").each(function() {
				my_link_record.push($(this).data('id'));
			});

			var selected_values = my_link_record.join(", ");
			
			$('.folder_link_highlight_count').each(function(){    
			  	$(this).html($(this).parents('.card-header').next('.product_accordion').find('.card-body ul li div.highlight-my-link').length);
			})
			
			$.ajax({
				type: "POST", 
				url: "ajax/my_links/apply_my_link_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
				var count = $(".highlight-my-link").length;
				var count_zero = '0';
			
				if (count != 0){
					$(".my_count").html(count);
				} else {
					$(".my_count").html(count_zero);
				} 
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
			
			$('#my_open_selected').prop('disabled', false);
			
		} else {
			
			var selected_values = '';
			
			$.ajax({
				type: "POST",
				url: "ajax/my_links/apply_my_link_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
				var count_zero = '0';
			
				$(".my_count").html(count_zero);
				$('#my_open_selected').prop('disabled', true);
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
			
		}
		
	});
	
});	
</script>

<script>
$(document).ready(function(){

	$(".js-inner-handle").mousedown(function(){

		$(".js-sortable-inner-connected").sortable({
			forcePlaceholderSize: true,
			connectWith: '.js-sortable-inner-connected',
			handle: '.js-inner-handle',
			items: '.item',
			maxItems: 15,
			placeholder: 'border rounded border-white bg-concrete',
			update: function( event, ui ) {
				updateMyLinkOrder();
			}

		});

	});

});
</script>

<style>
	
	.ui-sortable-placeholder { margin-bottom: 6px; height: 58px !important; } to .ui-sortable-placeholder { visibility: visible !important; }
	
	.product_accordion .ui-sortable-placeholder { margin-bottom: 0px !important; border-radius: 0px !important; height: 58px !important; } to .ui-sortable-placeholder { visibility: visible !important; }
	
</style>

<script>
function updateMyLinkOrder() {
	var selectedItems = {};
	var i = 0;
	
	$("ul.js-sortable-inner-connected li").each(function() {
		var note_id = $(this).data("id");
		var group_id = $(this).closest('.folder_groups').data("id");

		selectedItems['item_'+i]= note_id+":"+group_id;
		i = i+1;
	});
	
	selectedItems = JSON.stringify(selectedItems);
	var dataString = "sort_my_link_order="+selectedItems;

		$.ajax({
			type: "GET",
			url: "ajax/my_links/update_my_link_order.php",
			data: dataString,
			cache: false,
			success: function(data){
	    		readMySortLinks();
			}
		});
}
</script>

<script>
// function to sort Service Groups
$(document).ready(function(){

	$(".js-handle").mousedown(function(){

		$("#sortable_folder_groups_row").sortable({

			update: function( event, ui ) {
				updateMyFolderOrder();
			}
		});

	});

});
</script>

<script>
// function to save display sort order
function updateMyFolderOrder() {

	var selectedItem = new Array();

	$("ul#sortable_folder_groups_row li").each(function() {
		
		selectedItem.push($(this).data("id"));
	
	});

	var dataString = "sort_order="+selectedItem;

	$.ajax({
		type: "GET",
		url: "ajax/my_links_folders/update_my_folder_order.php",
		data: dataString,
		cache: false,
		success: function(data){
	    	readMySortLinks();
		}
	});
}
</script>

<script>
$(document).ready(function(){
	
	$("button.pdp-accord-toggle").click(function() {
		
	    $(this).toggleClass("collapsed");
		
		if ($("button.pdp-accord-toggle").hasClass("collapsed")){
			
			var my_link_record = [];

			$(".collapsed").each(function() {
				my_link_record.push($(this).data('id'));
			});

			var selected_values = my_link_record.join(", ");
			
			$.ajax({
				type: "POST", 
				url: "ajax/my_links_folders/apply_my_folder_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
			
		} else {
			
			var selected_values = '';
			
			$.ajax({
				type: "POST",
				url: "ajax/my_links_folders/apply_my_folder_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
			
		}
		
	});
	
});	
</script>


<script>
$(document).ready(function(){
	
	var count_z = $("div#collapse59 .highlight-my-link").length;
	var count_zero_z = '0';
	
	if (count_z != 0){
		$(".my_count_z").html(count_z);
	} else {
		$(".my_count_z").html(count_zero_z);
	} 
	
});	
</script>


<script>
$(document).ready(function(){
	
	$('.folder_link_highlight_count').each(function(){    
	  	$(this).html($(this).parents('.card-header').next('.product_accordion').find('.card-body ul li div.highlight-my-link').length);
	})

});	
</script>

	<script>
	$(document).ready(function(){
	
	
		$("#accord_open").click(function() {
			$(".product_accordion").addClass('show');
		
		});

		$("#accord_close").click(function() {
			$(".product_accordion").removeClass('show');
		
		});
	
	});
	</script>
	
<?php

if (isset($_SESSION['id'])) {
	
$data = '<div class="row">
			<div class="col-md-4">

				<div class="card mb-2 col_header">
					<h5 class="card-header text-secondary">Resources</h5>
  					<div class="card-body">
   						<p class="card-text">
							<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 30px auto 0 !important;">
  								<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
								<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
							</svg>
							<p class="one success">Empty!</p>
							<p class="complete mb-3">Folders not found!</p>
						</p>
					</div>
				</div> <!-- card mb-3 -->
				
			</div>
				
			<div class="col-md-4">

				<!--
				<div class="btn-group d-flex mb-2">
					<button type="button" class="btn btn-outline-primary w-50" id="accord_open"> <i class="fas fa-folder-open" aria-hidden="true"></i></button>
					<button type="button" class="btn btn-outline-primary w-50" id="accord_close"> <i class="fas fa-folder" aria-hidden="true"></i></button>
				</div>
				-->
				
				<ul class="js-sortable-connected list flex flex-column list-reset mb-0" id="sortable_folder_groups_row">';

				$my_own_folders = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
				
				$query = "SELECT * FROM my_links_folders WHERE my_folder_created_by = '$my_own_folders' ORDER BY my_folder_display_order ASC";

				if (!$result = mysqli_query($dbc, $query)) {
					exit();
				}

				// if query results contains rows then featch those rows
				if (mysqli_num_rows($result) > 0) {

					while ($row = mysqli_fetch_assoc($result)) {

						$my_folder_id = mysqli_real_escape_string($dbc, strip_tags($row['my_folder_id']));
						$my_folder_name = mysqli_real_escape_string($dbc, strip_tags($row['my_folder_name']));
						$my_link_creator = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
						
						$count_query = "SELECT COUNT(my_link_id) FROM my_links WHERE my_link_created_by = '$my_link_creator' AND my_link_folder_group = '$my_folder_id'";
						$count_result = mysqli_query($dbc, $count_query);
						$rows = mysqli_fetch_row($count_result);
						
						$my_folder_option = explode(',', $_SESSION['my_folder_select']);  

						if (in_array($my_folder_id, $my_folder_option)) {

							$my_folder_selector = 'collapsed';
							$my_folder_display = 'show';
					
						} else {

							$my_folder_selector = '';
							$my_folder_display = '';

						}
						
						$my_link_select_z = mysqli_real_escape_string($dbc, strip_tags($_SESSION['my_link_select']));
						$my_link_creator_z = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
						
						$count_query_z = "SELECT COUNT(my_link_id) FROM my_links WHERE my_link_created_by = '$my_link_creator_z' AND my_link_folder_group = '$my_folder_id' AND my_link_highlight = 1";
						$count_result_z = mysqli_query($dbc, $count_query_z);
						$rows_z = mysqli_fetch_row($count_result_z);
						
						$data .='<script>// scripts are needed for a test for indexes in sortupdate</script>
						<script></script>
						<script></script>
												  
						<li class="folder_groups col_folders" data-id="'.$my_folder_id.'">
							
							<div class="accordion mb-2 js-handle" id="'.$my_folder_id.'">
								<div class="card border">
									<div class="card-header shadow-sm border-0" id="heading'.$my_folder_id.'" style="padding: 0.5rem !important;padding-left: 0px !important;">
										<button type="button" class="btn btn-link btn-block pdp-accord-toggle text-decoration-none '.$my_folder_selector.'" 
										data-bs-toggle="collapse" data-bs-target="#collapse'.$my_folder_id.'" aria-expanded="false" aria-controls="collapse'.$my_folder_id.'" data-id="'.$row['my_folder_id'].'">
											<span class="badge rounded-pill bg-light-blue-badge shadow-sm me-2">'.$rows[0].'</span>
											<span class="hyperlink"> '.$my_folder_name.'</span>
										</button>
										<span class="lock text-decoration-none float-end mt-2">
										    <span class="icon-unlock badge rounded-pill bg-light-blue folder_link_highlight_count" id="badge_count_'.$my_folder_id.'"></span>
											<i class="icon-lock fa-solid fa-pen-to-square text-secondary" style="cursor:pointer;" onclick="readMyFolderDetails('.$my_folder_id.');"></i>
										</span>
									</div>
									<div id="collapse'.$my_folder_id.'" class="collapse product_accordion my_accord '.$my_folder_display.'" aria-labelledby="heading'.$my_folder_id.'" data-parent="#accordion'.$my_folder_id.'">
										<div class="card-body p-0">
											
											<ul class="js-sortable-inner-connected list flex flex-column list-reset">';
									
											if(isset($_GET['my_link_name']) && $_GET['my_link_name'] !== ""){
									
												$my_own_links = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
												
												$query_two = "SELECT * FROM my_links WHERE my_link_created_by = '$my_own_links' AND my_link_folder_group = '$my_folder_id' ORDER BY my_link_display_order ASC";
										
											} else {
									
												$my_own_links = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
												$query_two = "SELECT * FROM my_links WHERE my_link_created_by = '$my_own_links' AND my_link_folder_group = '$my_folder_id' ORDER BY my_link_display_order ASC";

											}

											if (!$results = mysqli_query($dbc, $query_two)) {
												exit();
											}

												// if query results contains rows then featch those rows
												if (mysqli_num_rows($results) > 0) {

													while ($row = mysqli_fetch_assoc($results)) {

														$my_truncated = '';
														
														$id = mysqli_real_escape_string($dbc, strip_tags($row['my_link_id']));
														$my_link_name = htmlspecialchars(strip_tags($row['my_link_name']));
														$my_link_image = mysqli_real_escape_string($dbc, strip_tags($row['my_link_image']));
														$my_link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['my_link_protocol']));
														$my_link_url = mysqli_real_escape_string($dbc, strip_tags($row['my_link_url']));
														$my_link_new_tab = mysqli_real_escape_string($dbc, strip_tags($row['my_link_new_tab']));
														$my_link_favorite = mysqli_real_escape_string($dbc, strip_tags($row['my_link_favorite']));
															
														if ($my_link_image != ''){
			
															$my_link_image_plus = '<img src="'.$my_link_image.'" alt="" width="43" height="43">';
			
														} else { $my_link_image_plus = '<img src="media/links/default_target.png" alt="" width="43" height="43">'; }
    			
														if ($my_link_protocol == 'https://'){
      				
															$my_full_url = "".$my_link_protocol."".$my_link_url."";
			
															$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
														} 
		
														if ($my_link_protocol == 'http://'){
      				
															$my_full_url = "".$my_link_protocol."".$my_link_url."";
			
															$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
														}
		
														if ($my_link_protocol == 'local_link'){
					
															$path = '/';
															$switchboard = 'switchboard';
			
										   	 				$my_full_url = ''.$path.''.$switchboard.''.$path.''.$my_link_url.'';
			
															$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
		
														}
		
														if ($my_link_protocol == 'no_protocol'){
      				
															$my_full_url = "".$my_link_url."";
			
															$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
														} 
		
														$my_link_option = explode(',', $_SESSION['my_link_select']);  
		
														if (in_array($id, $my_link_option)) {
			
															$my_selector = 'highlight-my-link';
													
														} else {
			
															$my_selector = '';

														}

														$data .='<li class="item folder" id="'.$id.'" data-id="'.$id.'">
															
														<div class="container-fluid js-inner-handle '.$my_selector.' list-group-item-action my_search_select" id="'.$my_full_url.'" 
														style="padding:6px !important;cursor:pointer;border-top:1px solid #dee2e6!important;" data-id="'.$id.'">
														
														<div class="row">
															<div class="col-sm-2">';
			
  															$data .=' '.$my_link_image_plus.' ';
			
  										           $data .='</div> <!-- End col-sm-2 -->
					   					
													   		<div class="col-sm-10" style="padding-left: 10px !important;">
																	
																<h6 class="mb-0">
			    													<span class="text-break" style="cursor:pointer;" onclick="readMyLinkDetails('.$id.');">'.$my_link_name.'</span>';
				
	  																if ($my_link_new_tab == 1){
	
	  																	$data .='<i class="fas fa-folder-plus float-end px-1 text-success" data-bs-toggle="tooltip" title="Opens in New Tab"></i>';
		
	  																} else {}

	  																if ($my_link_favorite == 1){
			
	  																	$data .='<i class="fas fa-star float-end px-1 text-warning" data-bs-toggle="tooltip" title="Favorite Link"></i>';

	  															 	} else {}
																					
	  					           					   $data .='</h6>
  		  							
	  															<a class="text-decoration-none" href="'.$my_full_url.'"';
          
  		  														if ($my_link_new_tab == 1){
								
  		  															$data .=' target="_blank"';
							
  		  														} else {}
				
  		  														$data .='><small class="text-break text-muted">'.$my_truncated.'</small>
				
  		  								    					</a>
									
													   		</div> <!-- End col-sm-10 -->';
														
	  					           				$data .='</div> </div><!-- End row -->
  									  
  								   				 	</li> <!-- End li -->';		
														  
													} $data .='<!-- End While $row = -->';
 
						   }  else {
        			   // records not found
        			   $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
  	  								<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  				  					<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				  				</svg>
				  			  	<p class="one success">Empty!</p>
				  			  	<p class="complete mb-3">Add some links!</p>';

							}
						   
							$data .='</ul></li>';

						} 
					   
				   }  else {
        			   // records not found
        			   $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 30px auto 0 !important;">
  	  								<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  				  					<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				  				</svg>
				  			  	<p class="one success">Empty!</p>
				  			  	<p class="complete mb-3">Folders not found!</p>';

						}
					
					   
	   $data .='</ul>
		    </div> <!-- End col-md-4 -->';


  $data .= '<div class="col-md-4">
				
				<ul class="js-sortable-inner-connected list flex flex-column list-reset" id="orphaned-list">
						
					<div class="row">';
						
						$my_own_links = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
						
						$query_two = "SELECT * FROM my_links WHERE my_link_created_by = '$my_own_links' AND my_link_folder_group NOT IN (SELECT my_folder_id FROM my_links_folders WHERE my_folder_id) ORDER BY my_link_display_order ASC";
				
						if (!$results = mysqli_query($dbc, $query_two)) {
							exit();
						}

						// if query results contains rows then featch those rows
						if (mysqli_num_rows($results) > 0) {

							while ($row = mysqli_fetch_assoc($results)) {

								$my_truncated = '';
    			
								$id = mysqli_real_escape_string($dbc, strip_tags($row['my_link_id']));
								$my_link_name = htmlspecialchars(strip_tags($row['my_link_name']));
								$my_link_image = mysqli_real_escape_string($dbc, strip_tags($row['my_link_image']));
								$my_link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['my_link_protocol']));
								$my_link_url = mysqli_real_escape_string($dbc, strip_tags($row['my_link_url']));
								$my_link_new_tab = mysqli_real_escape_string($dbc, strip_tags($row['my_link_new_tab']));
								$my_link_favorite = mysqli_real_escape_string($dbc, strip_tags($row['my_link_favorite']));
								
								if ($my_link_image != ''){
			
									$my_link_image_plus = '<img src="'.$my_link_image.'" alt="" width="43" height="43">';
			
								} else { $my_link_image_plus = '<img src="media/links/default_target.png" alt="" width="43" height="43">'; }
    			
								if ($my_link_protocol == 'https://'){
      				
									$my_full_url = "".$my_link_protocol."".$my_link_url."";
			
									$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
								} 
		
							    if ($my_link_protocol == 'http://'){
      				
									$my_full_url = "".$my_link_protocol."".$my_link_url."";
			
									$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
								}
		
								if ($my_link_protocol == 'local_link'){
					
									$path = '/';
									$switchboard = 'switchboard';
			
						     		$my_full_url = ''.$path.''.$switchboard.''.$path.''.$my_link_url.'';
			
									$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
		
								}
		
								if ($my_link_protocol == 'no_protocol'){
      				
									$my_full_url = "".$my_link_url."";
			
									$my_truncated = (strlen($my_full_url) > 30) ? substr($my_full_url, 0, 30) . '...' : $my_full_url;
    			
								} 
		
								$my_link_option = explode(',', $_SESSION['my_link_select']);  
		
								if (in_array($id, $my_link_option)) {
			
									$my_selector = 'highlight-my-link';
			
								} else {
			
								    $my_selector = '';

								}
		
								$data .='<div class="col-md-12 col_links">
									
									<ul class="js-sortable-inner-connected list flex flex-column list-reset">
									
										<li class="item" data-id="'.$id.'">
								<div class="container-fluid js-inner-handle border '.$my_selector.' rounded mb-2 bg-white my_search_select" id="'.$my_full_url.'" style="padding:6px !important;cursor:pointer;" data-id="'.$id.'">
									<div class="row">
										<div class="col-sm-2">';
					
											$data .=' '.$my_link_image_plus.' ';
					
							   $data .='</div>
			   		
								   		<div class="col-sm-10">
						            		<h6 class="mb-0">
					    
										  		<span class="text-break" style="cursor:pointer;" onclick="readMyLinkDetails('.$id.');">'.$my_link_name.'</span>';
						
												if ($my_link_new_tab == 1){
			
													$data .='<i class="fas fa-folder-plus float-end px-1 text-success" data-bs-toggle="tooltip" title="Opens in New Tab"></i>';
  				
												} else {}
  
												if ($my_link_favorite == 1){
					
										    		$data .='<i class="fas fa-star float-end px-1 text-warning" data-bs-toggle="tooltip" title="Favorite Link"></i>';
		
												} else {}
							
						              		  $data .='</h6>
					  
						             		<a class="text-decoration-none" href="'.$my_full_url.'"';
                  
											if ($my_link_new_tab == 1){
										
												$data .=' target="_blank"';
									
											} else {}
						
											$data .='><small class="text-break text-muted">'.$my_truncated.'</small>
						
											</a>
										</div>';
					
							   $data .='</div>
						       		</div>
								</div>';
							}

							$data .='</div></li></ul></div>';

						} else {
							
        					// records not found
        					$data .='<div class="col-md-12">
								
								
								<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 30px auto 0 !important;">
  	  									<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  				  						<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				  					 </svg>
				  				   	<p class="one success">Empty!</p>
				  				  	<p class="complete mb-3">No links found!</p></div>';
        					}

	 					   $data .='
		 					 </ul> </div> <!-- End row -->';

			echo $data;

}
		
mysqli_close($dbc);

?>