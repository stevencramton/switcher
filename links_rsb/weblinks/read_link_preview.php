<?php
session_start();
include '../../../mysqli_connect.php';
include '../../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_links_admin')){
	header("Location:../../../index.php?msg1");
	exit();
}

if(isset($_SESSION['id'])) {

$data = '<div class="mb-3">
            <h5 class="shadow-sm border rounded bg-edit p-3">Requested Links
                <span class="float-end" onclick="showNewRequests();"><i class="fa-solid fa-rotate-left" style="cursor:pointer;"></i></span>
            </h5>
        </div>';

$query = "SELECT * FROM links_request ORDER BY link_request_time ASC";

if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $link_request_id = strip_tags($row['link_request_id']);
            $link_request_title = strip_tags($row['link_request_title']);
			$link_request_location = strip_tags($row['link_request_location']);
            $link_request_url = strip_tags($row['link_request_url']);
            $link_request_sender = strip_tags($row['link_request_sender']);
            $link_request_time = strip_tags($row['link_request_time']);

            $data .= '<span class="list-group-item count-open-request-item shadow-sm border bg-dark text-white rounded d-flex p-3 mb-1" id="' . $link_request_id . '" aria-current="true" style="border-radius:8px !important;">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h5 class="mb-2">' . $link_request_title . '</h5>
                                <h6 class="opacity-75 text-break mb-3">' . $link_request_url . '</h6>
								<h6 class="text-break mb-2">' . $link_request_location . '</h6>
                                <p class="mb-0">
                                    <code style="color:#ff4ea6;">
                                        <small class="">Requested by: ' . $link_request_sender . ' on: ' . $link_request_time . '</small>
                                    </code>
                                </p>
                            </div>
                            <small class="opacity-75 text-nowrap">
								<button type="button" class="btn btn-light btn-sm" onclick="deleteLinkRequest(' . $link_request_id . ');">
                                <i class="fa-solid fa-trash-can"></i>
								</button>
                            </small>
                        </div>
                    </span>';
        }
    } else {
        
        $data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 50px auto 0 !important;">
                    <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                    <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
                  </svg>
                  <p class="one success">Requests empty!</p>
                  <p class="complete">Link requests not found!</p>';
    }

 	mysqli_stmt_close($stmt);
}

echo $data;

}
mysqli_close($dbc);
?>


<script>
$(document).ready(function(){
	function countOpenRequests(){
		var count = $('.count-open-request-item').length;
	    $('.count-open-requests').html(count);
		
	} countOpenRequests();
});
</script>