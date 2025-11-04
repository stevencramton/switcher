<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')){
    header("Location:index.php?msg1");
    exit();
}
?>

<script>
$(document).ready(function(){
	$('[data-button-svg]').on('click', function () {
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
	$(".copy-btn").on("click", function(){
		var share_mapping = $(this).siblings('span').text();
		copyToClipboard(share_mapping);
	})
});

function copyToClipboard(textToCopy) {
var textArea;

function isOS() {
	return navigator.userAgent.match(/(iPod|iPhone|iPad)/);
}

function createTextArea(text) {
 textArea = document.createElement('textArea');
 textArea.readOnly = false;
 textArea.contentEditable = true;
 textArea.value = text;
 document.body.appendChild(textArea);
}

function selectText() {
 var range, selection;

 if (isOS()) {
   range = document.createRange();
   range.selectNodeContents(textArea);
   selection = window.getSelection();
   selection.removeAllRanges();
   selection.addRange(range);
   textArea.setSelectionRange(0, 999999);
 } else {
   textArea.select();
 }
}

function copyTo() {
 document.execCommand('copy');
 document.body.removeChild(textArea);
}

createTextArea(textToCopy);
selectText();
copyTo();
}
</script>

<?php

$data ='<style>
.copy-btn{
  border:none !important;
}
.copy-btn:hover {
  border:none !important;
  background-color:transparent;
  color:#6c757d;
}
.copy-btn:active {
  border:none !important;
  background-color:transparent!important;
  color:#6c757d !important;
}
.info-btn{
	color:#6c757d;
}
.info-btn:hover, .info-btn:active{
	color:#6c757d;
}
</style>';

$data .='<div class="table-responsive">
  <table class="table table-sm table-hover mb-0">
    <thead class="table-light text-center">
      <th class="align-middle">
	  	<div class="form-check">
         <input type="checkbox" class="form-check-input select-all-shares" id="select-all-shares">
         <label class="form-check-label" for="select-all-shares"></label>
       </div>
      </th>
      <th class="align-middle">Share</th>
      <th class="align-middle">AD Group</th>
      <th class="align-middle">Server</th>
      <th class="align-middle">Mapping</th>
	  <th class="align-middle"></th>
    </thead>
    <tbody>';

    $query = "SELECT * FROM shares";
    
	if($result = mysqli_query($dbc, $query)){
		while($row = mysqli_fetch_array($result)){
			
			$share_id = mysqli_real_escape_string($dbc, strip_tags($row['share_id']));
			$share_drive_name = mysqli_real_escape_string($dbc, strip_tags($row['share_drive_name']));
			$share_ad_name = mysqli_real_escape_string($dbc, strip_tags($row['share_ad_name']));
			$share_mapping = mysqli_real_escape_string($dbc, strip_tags($row['share_mapping']));
			$share_server = mysqli_real_escape_string($dbc, strip_tags($row['share_server']));
			
          	$data .='<tr class="text-center">
          	  			<td class="align-middle" style="width:3%">
						<div class="mb-3 form-check">
              			  		<input type="checkbox" class="form-check-input chk-box-share-select" id="switchboard_'.$share_id.'" data-share-id="'.$share_id.'">
               				 	<label class="form-check-label" for="switchboard_'.$share_id.'"></label>
             				</div>
           				</td>
            			<td class="align-middle"><span id="share_drive_name">'.$share_drive_name.'</span></td>
            			<td class="align-middle"><span id="share_ad_name">'.$share_ad_name.'</span><button type="button" class="btn btn-sm float-end btn-outline-secondary copy-btn" data-button-svg="Copied"><i class="far fa-copy"></i></button></td>
            			<td class="align-middle"><span id="share_server">'.$share_server.'</span></td>
            			<td class="align-middle"><span id="share_mapping">'.$share_mapping.'</span><button type="button" class="btn btn-sm float-end btn-outline-secondary copy-btn" data-button-svg="Copied"><i class="far fa-copy"></i></button></td>
						<td class="align-middle"><a href="javascript:void(0)" class="info-btn"><i class="fas fa-info-circle"></i></a></td>
          			 </tr>';
		}
	}

$data .='</tbody></table></div>';

echo $data;

?>

<script>
$(document).ready(function() {
	$('.select-all-shares').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".chk-box-share-select").prop('checked', true);
			$(".chk-box-share-select").each(function(){
				
				var share_id = $(this).data("share-id");
	         	var share_name = $(this).closest("tr").find("#share_drive_name").text();
	         	var share_ad_name = $(this).closest("tr").find("#share_ad_name").text();
	         	var share_server = $(this).closest("tr").find("#share_server").text();
	         	var share_mapping = $(this).closest("tr").find("#share_mapping").text();
				
				$(this).closest("tr").find("#share_drive_name").html('<a href="#" id="share_name" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_drive_name" data-title="Enter Name">'+share_name+'</a>');
				$(this).closest("tr").find("#share_ad_name").html('<a href="#" id="share_ad" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_ad_name" data-title="Enter Name">'+share_ad_name+'</a>');
				$(this).closest("tr").find("#share_server").html('<a href="#" id="share_server_name" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_server" data-title="Enter Name">'+share_server+'</a>');
				$(this).closest("tr").find("#share_mapping").html('<a href="#" id="share_map" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_mapping" data-title="Enter Name">'+share_mapping+'</a>');
				
				$.fn.editable.defaults.mode = 'inline';
				
				$('#share_name, #share_ad, #share_server_name, #share_map').editable({
					clear: 'false',
					success: function(response, newValue) {
						readShares();
						if (!response.success) return response.msg;
					}
				});
			});
				
		} else {
			
			$(".chk-box-share-select").prop('checked',false);
			$(".chk-box-share-select").each(function(){
				var share_id = $(this).data("share-id");
				var share_name = $(this).closest("tr").find("#share_drive_name").text();
				var share_ad_name = $(this).closest("tr").find("#share_ad_name").text();
				var share_server = $(this).closest("tr").find("#share_server").text();
				var share_mapping = $(this).closest("tr").find("#share_mapping").text();
						
				$(this).closest("tr").find("#share_drive_name").html(share_name);
				$(this).closest("tr").find("#share_ad_name").html(share_ad_name);
				$(this).closest("tr").find("#share_server").html(share_server);
				$(this).closest("tr").find("#share_mapping").html(share_mapping);
			});
		}
	});

	$(".chk-box-share-select").on('click', function(e) {
		if ($(this).is(':checked',true)) {
        	$(".select-all-shares").prop("checked", false);
      	} else {
        	$(".select-all-shares").prop("checked", false);
      	}

      	if ($(".chk-box-share-select").not(':checked').length == 0) {
        	$(".select-all-shares").prop("checked", true);
		}
    });

    $('#deleteShareBtn').prop("disabled", true);
	$('#massUpdateBtn').prop('disabled',true);
	$('input:checkbox').click(function() {
	
		var share_id = $(this).data("share-id");
		var share_name = $(this).closest("tr").find("#share_drive_name").text();
		var share_ad_name = $(this).closest("tr").find("#share_ad_name").text();
		var share_server = $(this).closest("tr").find("#share_server").text();
		var share_mapping = $(this).closest("tr").find("#share_mapping").text();
	
		if ($(this).is(':checked')) {
			$(this).closest("tr").find("#share_drive_name").html('<a href="#" id="share_name" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_drive_name" data-title="Enter Name">'+share_name+'</a>');
			$(this).closest("tr").find("#share_ad_name").html('<a href="#" id="share_ad" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_ad_name" data-title="Enter Name">'+share_ad_name+'</a>');
			$(this).closest("tr").find("#share_server").html('<a href="#" id="share_server_name" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_server" data-title="Enter Name">'+share_server+'</a>');
			$(this).closest("tr").find("#share_mapping").html('<a href="#" id="share_map" data-type="text" data-pk="'+share_id+'" data-url="ajax/shares/update_share.php?type=share_mapping" data-title="Enter Name">'+share_mapping+'</a>');
    		$.fn.editable.defaults.mode = 'inline';
		
			$('#share_name, #share_ad, #share_server_name, #share_map').editable({
  		  		clear: 'false',
 		   		success: function(response, newValue) {
				readShares();
       			if (!response.success) return response.msg;
 		   		}
 	   		});
		
			$('#deleteShareBtn').addClass("btn-pink");
			$('#massUpdateBtn').addClass("btn-orange");
			$('#deleteShareBtn').prop("disabled", false);
			$('#massUpdateBtn').prop('disabled', false);

			} else {
   		 		$(this).closest("tr").find("#share_drive_name").html(share_name);
          		$(this).closest("tr").find("#share_ad_name").html(share_ad_name);
          		$(this).closest("tr").find("#share_server").html(share_server);
          		$(this).closest("tr").find("#share_mapping").html(share_mapping);
        
				if ($('.chk-box-share-select').filter(':checked').length < 1){
  		  			$('#deleteShareBtn').removeClass("btn-pink");
          			$('#deleteShareBtn').addClass("btn-primary");
          			$('#deleteShareBtn').attr('disabled', true);
					
  		  			$('#massUpdateBtn').removeClass("btn-orange");
          			$('#massUpdateBtn').addClass("btn-primary");
          			$('#massUpdateBtn').attr('disabled', true);
        		}
			}
 	});
});
</script>