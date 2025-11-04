<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}
?>

<script>
$(document).ready(function(){
	function countAuditRecords(){
		var count = $('.count-spx-item').length;
	    $('.count-audit-records').html(count);
	} countAuditRecords();
	
	var count = $(".chk-box-delete-audit:checked").length;
	var count_zero = '0';
	if (count != 0){
		$(".my_audit_count").html(count);
	} else {
		$(".my_audit_count").html(count_zero);
	}			
});
</script>

<?php
if (isset($_SESSION['id'])) {

	$data = '<script>
    $("#sxp_table").dataTable( {
        aLengthMenu: [
            [100, 200, -1],
            [100, 200, "All"]
        ],
    });
    </script>

    <div class="table-responsive p-1">
        <table class="table table-sm table-hover table-striped" id="sxp_table">
            <thead class="bg-light">
                <th><small>SXP</small></th>
                <th>Date</th>
                <th>Resource</th>
                <th>Action</th>
            </thead>
            <tbody id="">';

	$sxp_user = $_SESSION['user'];
	$query = "SELECT * FROM user_xp WHERE user = ?";
    
    if ($stmt = mysqli_prepare($dbc, $query)) {
		mysqli_stmt_bind_param($stmt, 's', $sxp_user);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $sxp_id = htmlspecialchars(strip_tags($row['id']));
            $first_name = htmlspecialchars(strip_tags($row['first_name']));
            $last_name = htmlspecialchars(strip_tags($row['last_name']));
            $user = htmlspecialchars(strip_tags($row['user']));
            $sxp = htmlspecialchars(strip_tags($row['xp']));

            $data .= '<tr class="count-spx-item" id="">
                <td class="align-middle" style="width:8%;">
                    <span class="badge bg-audit-primary-ghost shadow-sm">'.$sxp.'</span>
                </td>
                <td class="align-middle" style="width:20%;"><small class="">08-09-2023 8:36 AM</small></td>
                <td class="align-middle" style="width:20%;"><small class="">blog.php</small></td>
                <td class="align-middle" style="width:20%;"><small class="">Added blog post</small></td>
            </tr>';
        }
		mysqli_stmt_close($stmt);

    } else {
     	$data .= '<tr><td colspan="4">Error preparing the query.</td></tr>';
    }

    $data .= '</tbody>
    </table>
    </div>';

 	echo $data;

}
mysqli_close($dbc);
?>