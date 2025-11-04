<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_links_admin')){
	header("Location:../../index.php?msg1");
	exit();
}

if(isset($_SESSION['id'])) {

$data ='<script>
$(function() {
	$("#sortable-links").sortable({
		update: function( event, ui ) {
			updateLinkDisplayOrder();
		}
	});
});
</script>

<script>
function updateLinkDisplayOrder() {
	var selectedListItem = new Array();
	$("ul#sortable-links div.link_item").each(function() {
		selectedListItem.push($(this).data("id"));
	});
	
	var dataString = "sort_order="+selectedListItem;

	$.ajax({
		type: "GET",
  		url: "ajax/links/update_link_display_order.php",
  		data: dataString,
  		cache: false,
  		success: function(data){
    		readLinksList();
    		readRightLinksList();
		}
	});
}
</script>';

$data .='<div class="input-group mb-3">
			<div class="form-floating form-floating-group flex-grow-1">
				<input type="text" class="form-control shadow-sm" id="admin_link_search" placeholder="Search...">
				<label for="admin_link_search">Search...</label>
			</div>
			<button type="button" class="btn btn-light-gray shadow-sm input-group-text admin-link-mgr-search" 
			style="width:46px; border-top-right-radius: 6px; border-bottom-right-radius: 6px;">
				<i class="fa-solid fa-magnifying-glass"></i>
			</button>
		 </div>';


$data .='<ul class="list-group mb-3" id="sortable-links">';
  
$query = "SELECT * FROM links ORDER BY link_display_order ASC";
  		
if ($result = mysqli_query($dbc, $query)){
    		
	while($row = mysqli_fetch_array($result)){
		
		$truncated = '';
    	$full_url = '';
		$id = mysqli_real_escape_string($dbc, strip_tags($row['link_id']));
		$link_image = mysqli_real_escape_string($dbc, strip_tags($row['link_image']));
		$link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['link_protocol']));
		$link_url = mysqli_real_escape_string($dbc, strip_tags($row['link_url']));
		$new_tab = mysqli_real_escape_string($dbc, strip_tags($row['new_tab']));
		$link_full_name = mysqli_real_escape_string($dbc, strip_tags($row['link_full_name']));
		$favorite = mysqli_real_escape_string($dbc, strip_tags($row['favorite']));
		
		if ($link_image != ''){
			$link_image_plus = '<img src="'.$link_image.'" width="43" height="43">';
		} else { $link_image_plus = '<img src="media/links/default_target.png" alt="" width="43" height="43">'; }
    			
		if ($link_protocol == 'https://'){
      		$full_url = "".$link_protocol."".$link_url."";
			$truncated = (strlen($full_url) > 60) ? substr($full_url, 0, 60) . '...' : $full_url;
    	} 
		
		if ($link_protocol == 'http://'){
      		$full_url = "".$link_protocol."".$link_url."";
			$truncated = (strlen($full_url) > 60) ? substr($full_url, 0, 60) . '...' : $full_url;
    	}
		
		if ($link_protocol == 'local_link'){
			$path = '/';
			$switchboard = 'switchboard';
			$full_url = ''.$path.''.$switchboard.''.$path.''.$link_url.'';
			$truncated = (strlen($full_url) > 60) ? substr($full_url, 0, 60) . '...' : $full_url;
		}
		
		if ($link_protocol == 'no_protocol'){
      		$full_url = "".$link_url."";
			$truncated = (strlen($full_url) > 60) ? substr($full_url, 0, 60) . '...' : $full_url;
    	} 
		
		$data .='<div class="container-fluid link_item border veryify-col-1 rounded mb-2 bg-white admin_searching" id="'.$full_url.'" style="padding:10px !important;cursor:move;" data-id='.$id.'>
					<div class="row">
						<div class="col-sm-1">';
		$data .=' '.$link_image_plus.' ';
					
		$data .='</div>
					<div class="col-sm-11">
                  		<h6 class="ms-2 mb-0">
				  			<span class="" style="cursor:pointer;" onclick="readLinkDetails('.$id.');">'.$link_full_name.'</span>';
							
							if ($new_tab == 1){
								$data .='<i class="fas fa-folder-plus float-end px-1 text-success" data-bs-toggle="tooltip" title="Opens in New Tab"></i>';
      						} else {}
      
							if ($favorite == 1){
								$data .='<i class="fas fa-star float-end px-1 text-warning" data-bs-toggle="tooltip" title="Favorite Link"></i>';
							} else {}
								
                  		  $data .='</h6>
				  		
                		<a class="text-decoration-none ms-2" href="'.$full_url.'"';
                  
				  	  	if ($new_tab == "1"){
							$data .=' target="_blank"';
						} else {}
					  
                    	$data .='><small class="text-muted" style="word-wrap: break-word;">'.$truncated.'</small>
						
						</a>';
						
		   $data .='</div>
			   </div>
         	</div>';
	}
}

$data .='</ul>';

echo $data;

}
?>

<script>
$(document).ready(function(){
	$("#admin_link_search").on("keyup", function() {
		if ($("#admin_link_search").val() == ''){
			$(".input-group-text.admin-link-mgr-search").html('<i class="fa fa-search" aria-hidden="true"></i>');
		} else {
			$(".input-group-text.admin-link-mgr-search").html('<i class="fa fa-times-circle" aria-hidden="true"></i>');
			$(".input-group-text.admin-link-mgr-search").click(function() {
				var value = $(this).val().toLowerCase();
				$("div.admin_searching").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
				$('input[type="text"]').val('').trigger('propertychange').focus();
				$(".input-group-text.admin-link-mgr-search").html('<i class="fa fa-search" aria-hidden="true"></i>');
			});
		}
		var value = $(this).val().toLowerCase();
		$("div.admin_searching").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
		
	});
});
</script>