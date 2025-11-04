<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
?>

<script>
$(document).ready(function(){
	$("#search_my_links").on("keyup", function() {
		if ($("#search_my_links").val() == ''){
			$(".input-group-text.my-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
		} else {
			$(".input-group-text.my-link-search").find('.fa').removeClass('fa-search').addClass("fa-times-circle");
			$('.fa-times-circle').click(function() {
				var value = $(this).val().toLowerCase();
				$("div.my_search_select").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});
				$(".accordion .card").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
				});
				$('input[type="text"]').val('').trigger('propertychange').focus();
				$(".input-group-text.my-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
				$(".card-header").find(".fa-circle").removeClass("fa-circle").addClass("fa-dot-circle");
			});
		}
		var value = $(this).val().toLowerCase();
		$("div.my_search_select").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
		$(".accordion .card").filter(function() {
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
				my_link_record.push($(this).data('my-id'));
			});

			var selected_values = my_link_record.join(", ");
			
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

<?php

if(isset($_SESSION['id'])) {
	
	$data ='<div class="row">';

	$my_own_folders = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));

	$query = "SELECT * FROM my_links_folders WHERE my_folder_created_by = '$my_own_folders' ORDER BY my_folder_display_order ASC";
  		
	if ($result = mysqli_query($dbc, $query)){
    		
		while($row = mysqli_fetch_array($result)){
		
			$my_folder_id = mysqli_real_escape_string($dbc, strip_tags($row['my_folder_id']));
			$my_folder_name = mysqli_real_escape_string($dbc, strip_tags($row['my_folder_name']));
		
			$data .='<div class="accordion mb-2" id="'.$my_folder_id.'">
					<div class="card border">
						<div class="card-header" id="heading'.$my_folder_id.'">
							<button type="button" class="btn btn-link btn-block text-left text-decoration-none collapsed" data-bs-toggle="collapse" data-bs-target="#collapse'.$my_folder_id.'" aria-expanded="false" aria-controls="collapse'.$my_folder_id.'">
								<i class="far fa-dot-circle me-1"></i><span class="hyperlink"> '.$my_folder_name.'</span>
							</button>
							<span class="deletefolderhover float-end text-secondary opacity-50 mt-1">
								<i class="fa-solid fa-pen-to-square" style="cursor:pointer;" onclick="readMyFolderDetails('.$my_folder_id.');"></i>
						 	</span>
						</div>
						<div id="collapse'.$my_folder_id.'" class="collapse product_accordion" aria-labelledby="heading'.$my_folder_id.'" data-parent="#accordion'.$my_folder_id.'">
							<div class="card-body" style="padding:0px;">
								<div class="container-fluid list-group-item-action my_search_selects" id="" style="padding:6px !important;cursor:pointer;border-bottom:1px solid #dee2e6!important;">
									<div class="row">
										<div class="col-sm-2">
											<img src="https://play-lh.googleusercontent.com/MmLHNN4_lwIN7kMG7XWnOxSYbEju-FBMEn8oDj4Xt8t9EnnH6S6GQeMHJDWpGfeDOSpM" class="flex-shrink-0" width="35" height="35">
										</div>
			   							<div class="col-sm-10" style="padding-left: 10px !important;">
											<i class="fas fa-folder-plus float-end text-concrete me-1"></i>
											<i class="fas fa-star float-end me-2 text-warning"></i>
											<h6 class="mb-1">
												<span class="">Booglez</span>
										 	</h6>
				  							<a href=""><small class="text-muted" style="word-wrap: break-word;">https://googleoogleooogleooog...</small></a>
										</div>
									</div>
								</div>
								<div class="container-fluid list-group-item-action my_search_selects" id="" style="padding:6px !important;cursor:pointer;border-bottom:1px solid #dee2e6!important;">
									<div class="row">
										<div class="col-sm-2">
											<img src="https://www.iconpacks.net/icons/2/free-reddit-logo-icon-2436-thumb.png" class="flex-shrink-0" width="35" height="35">
										</div>
			   							<div class="col-sm-10" style="padding-left: 10px !important;">
											<i class="fas fa-folder-plus float-end text-concrete me-1"></i>
											<i class="fas fa-star float-end me-2 text-warning"></i>
											<h6 class="mb-1">
												<span class="">Booglez</span>
										 	</h6>
				  							<a href=""><small class="text-muted" style="word-wrap: break-word;">https://googleoogleooogleooog...</small></a>
										</div>
									</div>
								</div>
											
								</div> <!-- End card-body -->
							</div> <!-- End collapse product_accordion -->
						</div> <!-- End card -->
					</div> <!-- End accordion -->';
					
				}
			}

	$data .='</div>';
 
echo $data;

}
?>	