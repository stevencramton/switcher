<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}
?>

<script>
$(document).ready(function(){
	function countOrphanedProductItems(){
		var count = $('.count-orphaned-product-item').length;
	    $('.count-orphaned-product').html(count);
	} countOrphanedProductItems();
});
</script>

<script>
$(document).ready(function() {
	$('.select-all-orph-product').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".orph-product-chk-box").prop('checked', true);
		}
		else {
			$(".orph-product-chk-box").prop('checked',false); 
		}
		$("#select_count_product").html($("input.orph-product-chk-box:checked").length+" ");
	});
	
	$(".orph-product-chk-box").on('click', function(e) {
		$("#select_count_product").html($("input.orph-product-chk-box:checked").length+" ");
		if ($(this).is(':checked',true)) {
			$(".select-all-orph-product").prop("checked", false);
		}
		else {
			$(".select-all-orph-product").prop("checked", false);
		}
		if ($(".orph-product-chk-box").not(':checked').length == 0) {
			$(".select-all-orph-product").prop("checked", true);
		}
	});

	$('.hide_and_seek_orph_product').prop("disabled", true);
	$('input.manage-orph-product:checkbox').click(function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek_orph_product').prop("disabled", false);
		} else {
			if ($('.orph-product-chk-box').filter(':checked').length < 1){
				$('.hide_and_seek_orph_product').attr('disabled',true);}
			}
		});
});
</script>

<style> .nevo1 { display:none; } </style>

<?php

$data ='<table class="table table-hover mb-0">
	<thead class="table-secondary">
		<tr>
			<th style="width:36px;" class="sorting_disabled" rowspan="1" colspan="1" aria-label="">
				<div class="form-check form-switch">
					<input type="checkbox" class="form-check-input select-all-orph-product manage-orph-product" id="select-all-orph-product">
					<label class="form-check-label" for="select-all-orph-product"></label>
				</div>
			</th>
			<th>Orphaned Components <span class="badge bg-secondary count-orphaned-product"></span></th>
			<th>Group</th>
			<th>Order</th>
			<th>Sort</th>
		</tr>
	</thead>
	<tbody id="">';

$query_two = "SELECT * FROM service_products WHERE product_service_group NOT IN (SELECT group_id FROM service_groups WHERE group_id) ORDER BY product_display_order ASC";

if ($stmt = mysqli_prepare($dbc, $query_two)) {
	mysqli_stmt_execute($stmt);
	$results = mysqli_stmt_get_result($stmt);

	if (mysqli_num_rows($results) > 0) {
		while ($row = mysqli_fetch_assoc($results)) {
			$product_id = $row['product_id'];
			$product_name = $row['product_name'];
			$product_display_order = $row['product_display_order'];
			$product_service_group = $row['product_service_group'];

			$group_name = '<span class="badge bg-hot w-100" style="font-size:14px"><i class="fa-solid fa-circle-info"></i> Orphaned </span>';

			$data .= '<tr class="count-orphaned-product-item" data-orph_id="'.$row['product_id'].'">
						<td style="width:3%;">
							<div class="form-check form-switch">
								<input type="checkbox" class="form-check-input orph-product-chk-box manage-orph-product" id="orph_prod_'.$row['product_id'].'" data-orph-prod-id="'.$row['product_id'].'">
								<label class="form-check-label" for="orph_prod_'.$row['product_id'].'"></label>
							</div>
						</td>
						<td>'.$product_name.'</td>
						<td>'.$group_name.'</td>
						<td>'.$product_display_order.'</td>
						<td class="product_drag_icon align-middle grab" width="3%">
							<span class="btn btn-sm btn-light btn-outline handler ui-sortable-handle">
							<i class="fas fa-arrows-alt"></i>
							</span>
						</td>
					</tr>';
		}
		$data .= '</tbody></table>';
	} else {
		$data .= '</tbody></table>';
		$data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 30px auto 0 !important;">
					<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
					<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
				</svg>
				<p class="one success">Records empty!</p>
				<p class="complete mb-3">Orphaned Service Components not found!</p>';
	}

	mysqli_stmt_close($stmt);
} else {
	exit();
}

echo $data;
mysqli_close($dbc);
?>