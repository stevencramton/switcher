<?php
session_start(); // Start session

include '../../mysqli_connect.php'; // Open connection to database
include '../../templates/functions.php'; // Open functions file
?>

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

	$my_own_links = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
	
	$query = "SELECT * FROM my_links WHERE my_link_created_by = '$my_own_links' ORDER BY my_link_display_order ASC";
  		
	if ($result = mysqli_query($dbc, $query)){
    		
		while($row = mysqli_fetch_array($result)){
		
			$my_truncated = '';
    		
			$id = mysqli_real_escape_string($dbc, strip_tags($row['my_link_id']));
			$my_link_image = mysqli_real_escape_string($dbc, strip_tags($row['my_link_image']));
			$my_link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['my_link_protocol']));
			$my_link_url = mysqli_real_escape_string($dbc, strip_tags($row['my_link_url']));
			$my_link_id = mysqli_real_escape_string($dbc, strip_tags($row['my_link_id']));
			$my_link_new_tab = mysqli_real_escape_string($dbc, strip_tags($row['my_link_new_tab']));
			$my_link_favorite = mysqli_real_escape_string($dbc, strip_tags($row['my_link_favorite']));
			$my_link_name = htmlspecialchars(strip_tags($row['my_link_name']));
			
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
		
				$data .='<div class="col-md-6">
					<div class="container-fluid border '.$my_selector.' rounded mb-2 bg-white my_search_select" id="'.$my_full_url.'" style="padding:6px !important;cursor:pointer;" data-my-id="'.$my_link_id.'">
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
}

$data .='</div>';
 
echo $data;

}

?>	