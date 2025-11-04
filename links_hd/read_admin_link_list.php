<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('hd_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

$switch_id = mysqli_real_escape_string($dbc, strip_tags($_SESSION['switch_id']));

$token_query = "SELECT user_settings.admin_link_select
                FROM user_settings
                WHERE user_settings.user_settings_switch_id = '$switch_id'";

if ($result = mysqli_query($dbc, $token_query)) {
    if ($row = mysqli_fetch_assoc($result)) {
        $admin_link_select = isset($row['admin_link_select']) ? $row['admin_link_select'] : '';
    } else {
        $admin_link_select = '';
    }
    mysqli_free_result($result);
} else {
    $admin_link_select = '';
}

?>

<script>
$(document).ready(function(){
	$("#search_admin_links").on("keyup", function() {
		if ($("#search_admin_links").val() == ''){
			$(".input-group-text.admin-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
		} else {
			$(".input-group-text.admin-link-search").find('.fa').removeClass('fa-search').addClass("fa-times-circle");
			
			$('.input-group-text.admin-link-search').off('click').on('click', function() {
				if ($(this).find('.fa-times-circle').length > 0) {
					var value = $(this).val().toLowerCase();
					$("div.admin_search_select").filter(function() {
						$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
					});
					$('input[type="text"]').val('').trigger('propertychange').focus();
					$(".input-group-text.admin-link-search").find('.fa').removeClass('fa-times-circle').addClass("fa-search");
					$(".card-header").find(".fa-circle").removeClass("fa-circle").addClass("fa-dot-circle");
				}
			});
		}
		var value = $(this).val().toLowerCase();
		$("div.admin_search_select").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
		});
	});

	if ($("div.admin_search_select").hasClass("highlight-admin-link")){
		$('#admin_open_selected').prop('disabled', false);
	} else {
		$('#admin_open_selected').prop('disabled', true);
	}
	
	var count = $(".highlight-admin-link").length;
	var count_zero = '0';
	
	if (count != 0){
		$(".admin_count").html(count);
	} else {
		$(".admin_count").html(count_zero);
	} 

	$("div.admin_search_select").click(function() {
		$(this).toggleClass("highlight-admin-link");
		if ($("div.admin_search_select").hasClass("highlight-admin-link")){
			var admin_link_record = [];
			$(".highlight-admin-link").each(function() {
				admin_link_record.push($(this).data('admin-id'));
			});
			var selected_values = admin_link_record.join(", ");
			
			$.ajax({
				type: "POST",
				url: "ajax/my_links/apply_admin_link_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
				var count = $(".highlight-admin-link").length;
				var count_zero = '0';
			
				if (count != 0){
					$(".admin_count").html(count);
				} else {
					$(".admin_count").html(count_zero);
				} 
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
			
			$('#admin_open_selected').prop('disabled', false);
			
		} else {
			
			var selected_values = '';
			
			$.ajax({
				type: "POST",
				url: "ajax/my_links/apply_admin_link_options.php",
				cache:false,
				data: {emp_id:selected_values }
			}).done(function(response){
			
				var count_zero = '0';
			
				$(".admin_count").html(count_zero);
				$('#admin_open_selected').prop('disabled', true);
			
			}).fail(function(){
				swal.fire('Oops...', 'Something went wrong!', 'error');
			});
		}
	});
});	
</script>

<?php
if (isset($_SESSION['id'])) {
	$data = '<div class="input-group input-group-lg mb-3">
				<input type="text" class="form-control link-search-menu" id="search_admin_links" name="keyword" placeholder="Search..." autocomplete="off">
				<span class="input-group-text admin-link-search text-secondary" role="button">
					<i class="fa fa-search" aria-hidden="true"></i>
				</span>
			</div>
			<div class="row gx-3">';
  
	$query = "SELECT * FROM links ORDER BY link_display_order ASC";

	if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		while ($row = mysqli_fetch_assoc($result)) {
			
			$id = htmlspecialchars($row['link_id']);
			$link_image = htmlspecialchars($row['link_image']);
			$link_protocol = htmlspecialchars($row['link_protocol']);
			$link_url = htmlspecialchars($row['link_url']);
			$truncated = '';

			if ($link_image != '') {
				$link_image_plus = '<img src="'.$link_image.'" alt="" width="43" height="43">';
			} else {
				$link_image_plus = '<img src="media/links/default_target.png" alt="" width="43" height="43">';
			}
			
			if ($link_protocol == 'https://' || $link_protocol == 'http://') {
				$full_url = $link_protocol.$link_url;
				$truncated = (strlen($full_url) > 30) ? substr($full_url, 0, 30) . '...' : $full_url;
			}
			
			if ($link_protocol == 'local_link') {
				$path = '/';
				$switchboard = 'switchboard';
				$full_url = $path.$switchboard.$path.$link_url;
				$truncated = (strlen($full_url) > 30) ? substr($full_url, 0, 30) . '...' : $full_url;
			}

			if ($link_protocol == 'no_protocol') {
				$full_url = $link_url;
				$truncated = (strlen($full_url) > 30) ? substr($full_url, 0, 30) . '...' : $full_url;
			}
			
			$admin_link_option = explode(',', $admin_link_select);
			$admin_selector = in_array($id, $admin_link_option) ? 'highlight-admin-link' : '';

			$data .= '<div class="col-md-6">
			<div class="container-fluid shadow-sm border '.$admin_selector.' rounded mb-2 bg-white admin_search_select" id="'.$full_url.'" style="padding:6px !important;cursor:pointer;" data-admin-id="'.$id.'">
				<div class="row">
					<div class="col-sm-2">'.$link_image_plus.'</div>
					<div class="col-sm-10">
						<h6 class="mb-0">
							<span class="text-break">'.htmlspecialchars($row['link_full_name']).'</span>
						</h6>
						<a class="text-decoration-none" href="'.$full_url.'"'.($row['new_tab'] == "1" ? ' target="_blank"' : '').'>
							<small class="text-break text-muted">'.$truncated.'</small>
						</a>
						<span role="button" class="text-cloud-blue float-end px-1 clipboard-btn">
							<i class="fa-solid fa-clone" onclick="copyUrlToClipboard(this)"></i>
						</span>
						<script>
							function copyUrlToClipboard(iconElement) {
								var urlToCopy = iconElement.parentElement.parentElement.querySelector("a").getAttribute("href");
								navigator.clipboard.writeText(urlToCopy).then(function() {
									iconElement.classList.remove("fa-clone");
									iconElement.classList.add("fa-spinner", "fa-spin-pulse");
									setTimeout(function() {
										iconElement.classList.remove("fa-spinner", "fa-spin-pulse");
										iconElement.classList.add("fa-clone");
									}, 800);
								}, function() {
									alert("Failed to copy URL!");
								});
							}
						</script>
					</div>
				</div>
			</div>
		</div>';
		}

		mysqli_stmt_close($stmt);
	}

	$data .= '</div>';
	echo $data;
}
?>