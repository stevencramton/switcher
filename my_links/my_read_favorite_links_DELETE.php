<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if(isset($_SESSION['id'])) {

$data .='<div class="card-header"><i class="fas fa-star"></i> Favorite Links
	<span class="float-end" style="margin-top:-4px;">
		<button type="button" id="showAllLinksBtn" class="btn btn-sm btn-outline-secondary" onclick="linkSwitchToAll();" data-bs-toggle="tooltip" title="Show All Links">
			<i class="fas fa-list"></i>
		</button>
	</span>
</div>

<ul class="list-group list-group-flush">';

	$query = "SELECT * FROM links WHERE favorite = '1' ORDER BY link_display_order ASC";
	
	if ($result = mysqli_query($dbc, $query)){
		
		while($row = mysqli_fetch_array($result)){
  		  
		  	$id = mysqli_real_escape_string($dbc, strip_tags($row['link_id']));
			$link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['link_protocol']));
			$link_url = mysqli_real_escape_string($dbc, strip_tags($row['link_url']));
			$new_tab = mysqli_real_escape_string($dbc, strip_tags($row['new_tab']));
    		$link_icon = mysqli_real_escape_string($dbc, strip_tags($row['link_icon']));
			$link_full_name = mysqli_real_escape_string($dbc, strip_tags($row['link_full_name']));
			
			if($link_protocol != 'no_protocol'){
      			$full_url ="".$link_protocol."".$link_url."";
    		} else {
      			$full_url = '/switchboard'.$link_url.'';
			}
   				 	
			$data .='<a href="'.$full_url.'"';
        
			if ($new_tab == "1"){
				$data .=' target="_blank"';
			} else {}
        			
			$data .='class="list-group-item"><i class="'.$link_icon.'"></i><span class="ms-2"> '.$link_full_name.'</span></a>';

        }
	}
    
	$data .='</ul></div>';

echo $data;

}
?>