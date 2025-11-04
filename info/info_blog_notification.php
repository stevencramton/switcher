<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

date_default_timezone_set("America/New_York");

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('blog_view')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {
    $username = $_SESSION['user'];

    $query = "SELECT i.info_id, i.info_icon, i.info_icon_color, i.info_text_color, i.info_background_color, 
        i.info_border_color, i.info_button_text, i.info_btn_color, i.info_btn_bg_color, i.info_btn_bord_color, 
        i.info_btn_icon, i.info_btn_icon_status, i.info_btn_status, i.info_title, i.info_subtitle, i.info_message,
        i.info_publish, i.info_expire
              FROM info i
              LEFT JOIN info_confirm ic ON i.info_id = ic.info_id AND ic.username = ?
              WHERE i.info_status = 1 AND ic.info_id IS NULL
              ORDER BY i.info_display_order ASC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $data = '<div class="accordion accordion-flush mb-2" id="blogNotification">';

            while ($row = mysqli_fetch_assoc($result)) {
                $info_id = htmlspecialchars(strip_tags($row['info_id'] ?? ''));
                $info_icon = htmlspecialchars(strip_tags($row['info_icon'] ?? ''));
                $info_icon_color = htmlspecialchars(strip_tags($row['info_icon_color'] ?? ''));
                $info_text_color = htmlspecialchars(strip_tags($row['info_text_color'] ?? ''));
                $info_background_color = htmlspecialchars(strip_tags($row['info_background_color'] ?? ''));
                $info_border_color = htmlspecialchars(strip_tags($row['info_border_color'] ?? ''));
                $info_button_text = htmlspecialchars(strip_tags($row['info_button_text'] ?? ''));
                $info_btn_color = htmlspecialchars(strip_tags($row['info_btn_color'] ?? ''));
                $info_btn_bg_color = htmlspecialchars(strip_tags($row['info_btn_bg_color'] ?? ''));
                $info_btn_bord_color = htmlspecialchars(strip_tags($row['info_btn_bord_color'] ?? ''));
                $info_btn_icon = htmlspecialchars(strip_tags($row['info_btn_icon'] ?? ''));
                $info_btn_icon_status = htmlspecialchars(strip_tags($row['info_btn_icon_status'] ?? ''));
                $info_btn_status = htmlspecialchars(strip_tags($row['info_btn_status'] ?? ''));
                $info_title = htmlspecialchars(strip_tags($row['info_title'] ?? ''));
                $info_subtitle = htmlspecialchars(strip_tags($row['info_subtitle'] ?? ''));
                $info_message = htmlspecialchars(strip_tags($row['info_message'] ?? ''));
                $info_message = nl2br($info_message);

				$current_time = new DateTime();
				$info_publish = !empty($row['info_publish']) && $row['info_publish'] !== '0000-00-00 00:00:00' ? new DateTime($row['info_publish']) : null;
				$info_expire = !empty($row['info_expire']) && $row['info_expire'] !== '0000-00-00 00:00:00' ? new DateTime($row['info_expire']) : null;

				if (
				    (!$info_publish && !$info_expire) || // No date range, show if active
				    ($info_publish && !$info_expire && $current_time >= $info_publish) ||
				    (!$info_publish && $info_expire && $current_time <= $info_expire) ||
				    ($info_publish && $info_expire && $current_time >= $info_publish && $current_time <= $info_expire)
				) {
				    $data .= '<div class="accordion-item mb-2 border-0 shadow-sm">
				                <h2 class="accordion-header">
				                    <button type="button" class="accordion-button collapsed" style="background-color:'.$info_background_color.'; border: 1px dotted '.$info_border_color.';" data-bs-toggle="collapse" data-bs-target="#flush-collapseBlogNofity-'.$info_id.'" aria-expanded="false" aria-controls="flush-collapseBlogNofity-'.$info_id.'">
				                        <i class="'.$info_icon.' me-2" style="color:'.$info_icon_color.'"></i> <span style="color:'.$info_text_color.'">'.$info_title.'</span>
				                    </button>
				                </h2>
				                <div id="flush-collapseBlogNofity-'.$info_id.'" class="accordion-collapse collapse" data-bs-parent="#blogNotification">
				                    <div class="accordion-body p-4">
				                        <h5 class="text-break">'.$info_subtitle.'</h5>
				                        <p class="text-break mb-3">'.$info_message.'</p>';

				    if ($info_btn_status == 1) {
				        $data .= '<div class="">
				                    <button type="button" class="btn btn-primary w-100 confirm-info-btn" 
				                        style="color:'.$info_btn_color.' !important;background-color:'.$info_btn_bg_color.' !important;border: 1px solid '.$info_btn_bord_color.';" 
				                        data-info-id="'.$info_id.'" id="confirm-btn-'.$info_id.'">';

				        if ($info_btn_icon_status == 1) {
				            $data .= '<i class="'.$info_btn_icon.'" style="color:'.$info_text_color.'"></i> ';
				        }

				        $data .= $info_button_text . '</button></div>';
				    }

				    $data .= '</div>
				           </div>
				        </div>';
				}
            }

            $data .= '</div>';

            echo $data;
        }
		mysqli_stmt_close($stmt);
    }
	mysqli_close($dbc);
}
?>

<script>
$(document).ready(function(){
    $(document).on('click', '.confirm-info-btn', function() {
        var $button = $(this);
        var infoId = $button.data('info-id');

		$button.prop('disabled', true);

        $.ajax({
            url: 'ajax/info/info_confirm.php',
            type: 'POST',
            data: { info_id: infoId },
            success: function(response) {
                if (response === 'success') {
           		 	readInfoNotification();
				 	var toast = new bootstrap.Toast(document.getElementById('toast-info-confirm'));
                    toast.show();
                } else if (response === 'already_confirmed') {
                    alert('Info has already been confirmed.');
                } else {
                    alert('Error confirming info');
                }
            },
            error: function() {
                $button.prop('disabled', false);
                alert('Error confirming info');
            }
        });
    });
});
</script>