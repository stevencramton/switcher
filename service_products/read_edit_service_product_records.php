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
	function countProductItems(){
		var count = $('.count-product-item').length;
	    $('.count-product').html(count);
	} countProductItems();
});
</script>

<script>
$(document).ready(function() {
	$('.select-all-product').on('click', function(e) {
		if ($(this).is(':checked',true)) {
			$(".product-chk-box").prop('checked', true);
		}
		else {
			$(".product-chk-box").prop('checked',false);
		}
		$("#select_count_product").html($("input.product-chk-box:checked").length+" ");
	});
	$(".product-chk-box").on('click', function(e) {
		$("#select_count_product").html($("input.product-chk-box:checked").length+" ");

		if ($(this).is(':checked',true)) {
			$(".select-all-product").prop("checked", false);
		}
		else {
			$(".select-all-product").prop("checked", false);
		}

		if ($(".product-chk-box").not(':checked').length == 0) {
			$(".select-all-product").prop("checked", true);
		}
	});

	$('.hide_and_seek_product').prop("disabled", true);
	$('input.manage-product:checkbox').click(function() {
		if ($(this).is(':checked')) {
			$('.hide_and_seek_product').prop("disabled", false);
		} else {
			if ($('.product-chk-box').filter(':checked').length < 1){
				$('.hide_and_seek_product').attr('disabled',true);}
			}
		});
});
</script>

<script>
$(document).ready(function(){
	$(".product_drag_icon").mousedown(function(){
		$( "#sortable_product_row" ).sortable({
			update: function( event, ui ) {
				updateProductDisplayOrder();
			}
		});
	});
});
</script>

<script>
function updateProductDisplayOrder() {
	var selectedItem = new Array();

	$("tbody#sortable_product_row tr").each(function() {
		selectedItem.push($(this).data("id"));
	});

	var dataString = "sort_product_order="+selectedItem;

	$.ajax({
		type: "GET",
		url: "ajax/service_products/update_product_order.php",
		data: dataString,
		cache: false,
		success: function(data){
			readEditProductGroupRecords();
			readServiceGroupRecords();
		}
	});
}
</script>

<style>
	.nevo1 { display:none; }
</style>

<?php
$data ='<table class="table table-bordered table-hover">
<thead class="table-secondary">
    <tr>
        <th style="width:36px;" class="sorting_disabled" rowspan="1" colspan="1" aria-label="">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input select-all-product manage-product" id="select-all-product">
                <label class="form-check-label" for="select-all-product"></label>
            </div>
        </th>
        <th>Components <span class="badge bg-secondary count-product"></span></th>
        <th>Group</th>
        <th>Order</th>
        <th>Sort</th>
    </tr>
</thead>
<tbody id="sortable_product_row">';

$query = "SELECT * FROM service_products ORDER BY product_display_order ASC";
$stmt = mysqli_prepare($dbc, $query);

if (!$stmt) {
    exit();
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
		$products_id = strip_tags($row['product_id'] ?? '');
		$product_name = strip_tags($row['product_name'] ?? '');
		$product_display_order = strip_tags($row['product_display_order'] ?? '');
		$product_service_group = strip_tags($row['product_service_group'] ?? '');

    	$query_two = "SELECT * FROM service_groups WHERE group_id = ?";
        $stmt_two = mysqli_prepare($dbc, $query_two);

        if (!$stmt_two) {
            exit();
        }

        mysqli_stmt_bind_param($stmt_two, 'i', $product_service_group);
        mysqli_stmt_execute($stmt_two);
        $result_two = mysqli_stmt_get_result($stmt_two);

        if (mysqli_num_rows($result_two) > 0) {
            $row_two = mysqli_fetch_assoc($result_two);
			$group_id_two = strip_tags($row_two['group_id'] ?? '');
			$group_name_two = strip_tags($row_two['group_name'] ?? '');

            $data .= '<tr class="count-product-item" data-id="' . $products_id . '">
                <td style="width:3%;">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input product-chk-box manage-product" id="prod_' . $products_id . '" data-prod-id="' . $products_id . '">
                    <label class="form-check-label" for="prod_' . $products_id . '"></label>
                </div>
                </td>
                <td>' . $product_name . '</td>
                <td>' . $group_name_two . '</td>
                <td>' . $product_display_order . '</td>
                <td class="product_drag_icon align-middle grab" width="3%">
                    <span class="btn btn-sm btn-light btn-outline handler ui-sortable-handle">
                    <i class="fas fa-arrows-alt"></i>
                    </span>
                </td>
            </tr>';
        }
        mysqli_stmt_close($stmt_two);
    }
    $data .= '</tbody></table>';
} else {
    $data .= '</tbody></table>';
    $data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 0px auto 0 !important;">
    <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
    <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
    </svg>
    <p class="one success">Records empty!</p>
    <p class="complete">Service Components not found!</p>';
}

echo $data;
mysqli_stmt_close($stmt);
mysqli_close($dbc);
?>