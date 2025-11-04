<?php
ob_start();
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
?>

<link rel="stylesheet" href="plugins/sort/basscss.css">
<script src="plugins/sort/html5sortable.js"></script>

<style>
.js-inner-handle { cursor: pointer; }
.bg-fire { 
	background-color: #2b2b2b;
	color: #00bcd4;
	}
</style>

<script>
$(document).ready(function() {
	$('.select-all-orphaned-notes').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".orphaned-notes-chk-box").prop('checked', true);
		}
		else {
			$(".orphaned-notes-chk-box").prop('checked', false);
		}
		$("#select_count").html($("input.orphaned-notes-chk-box:checked").length+" ");
	});
	$(".orphaned-notes-chk-box").on('click', function(e) {
		$("#select_count").html($("input.orphaned-notes-chk-box:checked").length+" ");
		if ($(this).is(':checked',true)) {
			$(".select-all-orphaned-notes").prop("checked", false);
		}
		else {
			$(".select-all-orphaned-notes").prop("checked", false);
		}
		if ($(".orphaned-notes-chk-box").not(':checked').length == 0) {
			$(".select-all-orphaned-notes").prop("checked", true);
		}
	});
});
</script>

<script>
$(document).ready(function() {
	$('.select-all-verse-groups').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".verse-group-chk-box").prop('checked', true);
		}
		else {
			$(".verse-group-chk-box").prop('checked', false);
		}
		$("#select_count").html($("input.verse-group-chk-box:checked").length+" ");
	});
	$(".verse-group-chk-box").on('click', function(e) {
		$("#select_count").html($("input.verse-group-chk-box:checked").length+" ");
		if ($(this).is(':checked',true)) {
			$(".select-all-verse-groups").prop("checked", false);
		}
		else {
			$(".select-all-verse-groups").prop("checked", false);
		}
		if ($(".verse-group-chk-box").not(':checked').length == 0) {
			$(".select-all-verse-groups").prop("checked", true);
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
			placeholder: 'border border-white bg-navy mb1',
			update: function( event, ui ) {
				updateVerseNoteOrder();
			}

		});
	});
});
</script>

<script>
function updateVerseNoteOrder() {
	var selectedItems = {};
	var i = 0;
	
	$("ul.js-sortable-inner-connected li").each(function() {
		var note_id = $(this).data("id");
		var group_id = $(this).closest('.verse_group').data("id");

		selectedItems['item_'+i]= note_id+":"+group_id;
		i = i+1;
	});
		
	selectedItems = JSON.stringify(selectedItems);
	var dataString = "sort_verse_note_order="+selectedItems;

	$.ajax({
		type: "GET",
		url: "ajax/verse_notes/update_verse_note_order.php",
		data: dataString,
		cache: false,
		success: function(data){
			readVerseServiceGroupRecords();
			readVerseGroupRecords();
		}
	});
}
</script>

<script>
// function to sort Service Groups
$(document).ready(function(){
	$(".js-handle").mousedown(function(){
		$("#sortable_verse_group_row").sortable({
			update: function( event, ui ) {
				updateVerseGroupOrder();
			}
		});
	});
});
</script>

<script>
function updateVerseGroupOrder() {
	var selectedItem = new Array();
	$("ul#sortable_verse_group_row li").each(function() {
		selectedItem.push($(this).data("id"));
	});
	var dataString = "sort_order="+selectedItem;

	$.ajax({
		type: "GET",
		url: "ajax/verse_groups/update_verse_group_order.php",
		data: dataString,
		cache: false,
		success: function(data){
	    	readVerseServiceGroupRecords();
			readVerseGroupRecords();
		}
	});
}
</script>

<?php

$data = '<div class="row">
	  		<div class="col-md-4">
				<ul class="js-sortable-connected list flex flex-column list-reset" id="sortable_verse_group_row">';

				$query = "SELECT * FROM verse_groups ORDER BY verse_group_display_order ASC";

				if (!$result = mysqli_query($dbc, $query)) {
					exit();
				}

				if (mysqli_num_rows($result) > 0) {

					while ($row = mysqli_fetch_assoc($result)) {

						$verse_group_id = $row['verse_group_id'];

						$data .='<script>// scripts are needed for a test for indexes in sortupdate</script>
								 <script></script>
								 <script></script>
								 
								 <li class="p1 mb1 white rounded bg-maroon verse_group" data-id="'.$row['verse_group_id'].'">
									<div class="mb1">
										<div class="custom-control custom-switch" style="display:none;">
										<input type="checkbox" class="custom-control-input verse-group-chk-box" id="'.$row['verse_group_id'].'" data-vgrp-id="'.$row['verse_group_id'].'">
										<label class="custom-control-label" for="'.$row['verse_group_id'].'"></label>
									</div>
									<span class="js-handle px1">
										<i class="fas fa-arrows-alt"></i>
									</span>'.$row['verse_group_name'].'
									<button type="button" class="btn btn-danger btn-sm float-end" onclick="deleteVerseGroup('.$row['verse_group_id'].');">
										<i class="fas fa-trash-alt"></i>
									</button>
								</div>

								<ul class="js-sortable-inner-connected list flex flex-column list-reset m0 py1">';

								if(isset($_GET['verse_note_title']) && $_GET['verse_note_title'] !== ""){
									$query_two = "SELECT * FROM verse_notes WHERE verse_note_group = '$verse_group_id' ORDER BY verse_note_display_order ASC";
								} else {
									$query_two = "SELECT * FROM verse_notes WHERE verse_note_group = '$verse_group_id' ORDER BY verse_note_display_order ASC";
								}

								if (!$results = mysqli_query($dbc, $query_two)) {
									exit();
								}

								if (mysqli_num_rows($results) > 0) {

									while ($row = mysqli_fetch_assoc($results)) {
										$verse_note_id = $row['verse_note_id'];
										$verse_note_title = $row['verse_note_title'];
										$verse_note_info = $row['verse_note_info'];
										$verse_note_private = $row['verse_note_private'];
										$verse_note_tags = $row['verse_note_tags'];

										$data .='<li class="p1 mb1 rounded border border-white white bg-navy item" data-id="'.$row['verse_note_id'].'">
															<span class="js-inner-handle px1"><i class="fas fa-arrows-alt-v"></i></span>'.$row['verse_note_title'].'
															<button type="button" class="btn btn-outline-danger btn-sm float-end" onclick="deleteVerseNote('.$row['verse_note_id'].');">
																<i class="fas fa-trash-alt"></i>
															</button>
														  </li>';
													  }

												  } $data .='</ul></li>';

											  }
								  } else {

        $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
  	  				<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  				  	<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				  </svg>
				  <p class="one success">Empty!</p>
				  <p class="complete mb-3">Verse Notes not found!</p>';

			  }

			  $data .='</ul>
				  
	</div>';

$data .= '<div class="col-md-8">
	
<ul class="list flex flex-column list-reset">
	<li class="">
		<ul class="js-sortable-inner-connected list flex flex-column list-reset mb0 py1" id="orphaned-list">';
				
		$query_two = "SELECT * FROM verse_notes WHERE verse_note_group NOT IN (SELECT verse_group_id FROM verse_groups WHERE verse_group_id) ORDER BY verse_note_display_order ASC";

		if (!$results = mysqli_query($dbc, $query_two)) {
			exit();
		}
		
		if (mysqli_num_rows($results) > 0) {

			while ($row = mysqli_fetch_assoc($results)) {
				$verse_note_id = $row['verse_note_id'];
				$verse_note_title = $row['verse_note_title'];
				$verse_note_info = $row['verse_note_info'];
				$verse_note_private = $row['verse_note_private'];
				$verse_note_tags = $row['verse_note_tags'];

				$data .='<li class="p1 mb1 rounded border border-white white bg-navy item" data-id="'.$row['verse_note_id'].'">
							<div class="custom-control custom-switch" style="display:none;">
								<input type="checkbox" class="custom-control-input orphaned-notes-chk-box" id="'.$row['verse_note_id'].'" data-emp-id="'.$row['verse_note_id'].'">
								<label class="custom-control-label" for="'.$row['verse_note_id'].'"></label>
							</div>
							<span class="js-inner-handle px1">
								<i class="fas fa-arrows-alt-v"></i>
							</span>'.$row['verse_note_title'].'
							<button type="button" class="btn btn-outline-danger btn-sm float-end" onclick="deleteVerseNote('.$row['verse_note_id'].');">
								<i class="fas fa-trash-alt"></i>
							</button>
						</li>';
			}
			$data .='</ul></li></ul>';

		} else {
        
        $data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 10px auto 0 !important;">
  	  				<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  				  	<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				  </svg>
				  <p class="one success">Empty!</p>
				  <p class="complete mb-3">No links found!</p>';
			  }

	 $data .='</div>
		 </div>';

echo $data;
mysqli_close($dbc);

?>

<script>
function verseGroupsToggle(){
	$("#delete-all-verse-groups").toggle();
	$("#connected-group-notes").toggle();
	$("#sortable_verse_group_row .bg-maroon").toggleClass("bg-fire");
};
	
function verseNotesToggle(){
	$("#delete-all-verse-notes").fadeToggle();
	$("#orphaned-group-notes").toggle();
	$("#orphaned-list .bg-navy").toggleClass("bg-fire");
		
	var value = $(this).val().toLowerCase();
	$("tr").filter(function() {
		$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
	});

	$('input[type="text"]').val('');
	$(".input-group-text.service-search").find('.fa').removeClass('fa-times').addClass("fa-search");
};
</script>