<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_view')) {
    header("Location:../../index.php?msg1");
    exit();
}
?>

<script>
$(document).ready(function() {
	$('.select-all-options').on('click', function(e) {
     	if ($(this).is(':checked')) {
            $(".options-chk-box").prop('checked', true);
        } else {
            $(".options-chk-box").prop('checked', false);
        }
        updateSelectCount();
    });
    
    $(".options-chk-box").on('click', function(e) {
      	updateSelectCount();
        
      	if ($(".options-chk-box").not(':checked').length == 0) {
            $(".select-all-options").prop("checked", true);
        } else {
            $(".select-all-options").prop("checked", false);
        }
    });
    
    function updateSelectCount() {
        var checkedCount = $("input.options-chk-box:checked").length;
      	$("#select_count").html(checkedCount + " ");
    }
    
  	updateSelectCount();
    
 	$(".options-chk-box").each(function(index, element) {
   	});
});
</script>

<?php
$switch_id = $_SESSION['switch_id'];
$data = '';

$user_categories_query = "SELECT usc.switchboard_cat_id, usc.is_enabled 
                         FROM user_settings_search_categories usc 
                         WHERE usc.user_settings_switch_id = ? AND usc.is_enabled = 1";
$user_categories_stmt = mysqli_prepare($dbc, $user_categories_query);
mysqli_stmt_bind_param($user_categories_stmt, 'i', $switch_id);
mysqli_stmt_execute($user_categories_stmt);
$user_categories_result = mysqli_stmt_get_result($user_categories_stmt);

$selected_categories = [];
while ($row = mysqli_fetch_assoc($user_categories_result)) {
    $selected_categories[] = (int) $row['switchboard_cat_id'];
}
mysqli_stmt_close($user_categories_stmt);

$total_categories_query = "SELECT COUNT(*) as total FROM switchboard_categories";
$total_result = mysqli_query($dbc, $total_categories_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_categories = $total_row['total'];
$search_select_all = (count($selected_categories) == $total_categories) ? 'checked' : '';

$data = '<div class="form-check form-switch">
            <input type="checkbox" class="form-check-input custom-check-input select-all-options" id="select-all-options" '.$search_select_all.'>
            <label class="form-check-label" for="select-all-options">All Contacts</label>
         </div>
         <hr class="text-info">';

$query = "SELECT * FROM switchboard_categories ORDER BY switchboard_cat_display_order ASC";
$result = mysqli_query($dbc, $query);

if (mysqli_num_rows($result) == 0) {
    $data .= '<div class="text-center"><em>No Categories Found</em></div>';
} else {
    $category_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $switchboard_cat_name = htmlspecialchars($row['switchboard_cat_name']);
        $switchboard_cat_id = (int) $row['switchboard_cat_id'];
    	$search_selector = in_array($switchboard_cat_id, $selected_categories) ? 'checked' : '';
		$checkbox_id = 'category_' . $switchboard_cat_id;
		
		$data .= '<div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input custom-check-input options-chk-box" 
                           id="'.$checkbox_id.'" 
                           data-emp-id="'.$switchboard_cat_id.'" '.$search_selector.'>
                    <label class="form-check-label" for="'.$checkbox_id.'"> '.$switchboard_cat_name.'</label>
                  </div>';
        $category_count++;
    }
}

$_SESSION['search_select'] = implode(', ', $selected_categories);

echo $data;
?>