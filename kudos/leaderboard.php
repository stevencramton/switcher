<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('kudos_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])) {

    $data = '<script>
    $("#leaderboard_table").dataTable( {
        "autoWidth": false,
        aLengthMenu: [
            [100, 200, -1],
            [100, 200, "All"]
        ],
		"columnDefs": [{ "orderable": false, "targets": 0 }],
		"order": []
    });
    </script>

    <div class="table-responsive">
    <table class="table table-hover table-striped" id="leaderboard_table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Overall <small>(AVG)</small></th>
                <th>Teamwork</th>
                <th>Knowledge</th>
                <th>Customer Service</th>
                <th>Kudos <small>(#)</small></th>
            </tr>
        </thead>
        <tbody id="items">';

    $query = "
    SELECT users.user, users.first_name, users.last_name, users.profile_pic, 
           IFNULL(COUNT(kudos.recipient), 0) AS total_kudos,
           AVG(NULLIF(kudos.teamwork, 0)) AS avg_teamwork, 
           AVG(NULLIF(kudos.knowledge, 0)) AS avg_knowledge, 
           AVG(NULLIF(kudos.service, 0)) AS avg_service
    FROM users
    LEFT JOIN kudos ON users.user = kudos.recipient
    WHERE users.user != ? AND users.user != ? AND users.account_delete != ?
    GROUP BY users.user, users.first_name, users.last_name, users.profile_pic
    ORDER BY total_kudos DESC";

    if ($stmt = mysqli_prepare($dbc, $query)) {
        $excluded_user1 = 'infotech';
        $excluded_user2 = 'jguilmet';
        $account_delete = '1';
        mysqli_stmt_bind_param($stmt, "ssi", $excluded_user1, $excluded_user2, $account_delete);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_array($result)) {

            $profile_pic = htmlspecialchars($row['profile_pic'], ENT_QUOTES, 'UTF-8');
            $first_name = htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8');
            $last_name = htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8');

            $data .= '<tr>
                <td class="align-middle text-center" style="width:5%;"><img src="' . $profile_pic . '" class="profile-photo"></td>
                <td class="align-middle">'.$first_name. ' '.$last_name.'</td>';

            $user = htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8');
            $avg_teamwork = (float) ($row['avg_teamwork'] ?? 0);
            $avg_knowledge = (float) ($row['avg_knowledge'] ?? 0);
            $avg_service = (float) ($row['avg_service'] ?? 0);

            $teaching_variables = array_filter([$avg_teamwork, $avg_knowledge, $avg_service], function($value) {
                return $value != 0.0;
            });

            $non_zero_count = count($teaching_variables);
            $non_zero_sum = array_sum($teaching_variables);
            $average = $non_zero_count > 0 ? round($non_zero_sum / $non_zero_count, 1) : 0;

            $data .= '<td class="align-middle" width="">';
            $data .= $average ? generateKudosStars($average).'<span class="ms-1">'.number_format($average, 2).'</span>' : '<span class="text-center">N/A</span>';
            $data .= '</td>';
			$data .= '<td class="align-middle" width="">';
            $data .= $avg_teamwork ? generateKudosStars($avg_teamwork).'<span class="ms-1">'.number_format($avg_teamwork, 2).'</span>' : '<span class="text-center">N/A</span>';
            $data .= '</td>';
			$data .= '<td class="align-middle" width="">';
            $data .= $avg_knowledge ? generateKudosStars($avg_knowledge).'<span class="ms-1">'.number_format($avg_knowledge, 2).'</span>' : '<span class="text-center">N/A</span>';
            $data .= '</td>';
			$data .= '<td class="align-middle" width="">';
            $data .= $avg_service ? generateKudosStars($avg_service).'<span class="ms-1">'.number_format($avg_service, 2).'</span>' : '<span class="text-center">N/A</span>';
            $data .= '</td>';
			$total_count = $row['total_kudos'];
            $data .= '<td class="align-middle text-center" width="">'.$total_count.'</td>';
            $data .= '</tr>';
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    }

    $data .= '</tbody>
    </table>
    </div>';

    echo $data;

} else {
    echo 'No session found. Please log in.';
}
mysqli_close($dbc);
?>