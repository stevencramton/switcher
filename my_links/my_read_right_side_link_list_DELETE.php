<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if(isset($_SESSION['id'])) {

$data ='<ul class="list-group list-group-flush">';

$query = "SELECT * FROM links ORDER BY link_display_order ASC";
	$query = "SELECT * FROM links ORDER BY link_display_order ASC";
		if ($result = mysqli_query($dbc, $query)){
			while($row = mysqli_fetch_array($result)){

				$id = mysqli_real_escape_string($dbc, strip_tags($row['link_id']));
				$link_protocol = mysqli_real_escape_string($dbc, strip_tags($row['link_protocol']));
				$link_url = mysqli_real_escape_string($dbc, strip_tags($row['link_url']));
				$new_tab = mysqli_real_escape_string($dbc, strip_tags($row['new_tab']));
				$link_icon = mysqli_real_escape_string($dbc, strip_tags($row['link_icon']));
				$link_full_name = mysqli_real_escape_string($dbc, strip_tags($row['link_full_name']));
					
				if ($link_protocol != 'no_protocol'){
					$full_url ="".$link_protocol."".$link_url."";
				} else {
					$full_url = '/switchboard'.$link_url.'';
				}
					
    			$data .='<li class="list-group-item link_item p-0 right-search-three" style="border:none; background-color:transparent">
              	  		<h6 class="my-0" style="font-size:14px;"><a href="'. $full_url .'"';
				
				if ($new_tab == "1"){
					$data .=' target="_blank"';
				} else {}
					
				$data .='><i class="'.$link_icon.' mr-2"></i>';
					
				if (strlen($link_full_name) >= 20){
					$data .=''.substr($link_full_name,0,20).'...';
				} else {
					$data .=''.$link_full_name.'';
				}

				$data .='</a></h6></li>';
			}
		}

		$data .='</ul>';

echo $data;

}
?>