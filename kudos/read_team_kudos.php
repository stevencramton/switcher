<?php
session_start();
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
    $("#kudos_table").dataTable({
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
        <table class="table table-hover table-striped" id="kudos_table">
            <thead>
                <tr>';

    if (checkRole('kudos_admin')) {
        $data .= '<th>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input team-select-all" id="switch1">
                <label class="form-check-label" for="switch1"></label>
            </div>
        </th>';
    }

    $data .= '<th>Date</th>
              <th>From</th>
              <th>To</th>
              <th>Teamwork</th>
              <th>Knowledge</th>
              <th>Customer Service</th>
              <th>Public Notes</th>
              </tr>
              </thead>
              <tbody id="items">';

    $user = strip_tags($_SESSION['user']);

    $query = "SELECT * FROM kudos ORDER BY id DESC";
    if ($result = mysqli_query($dbc, $query)) {
        while ($row = mysqli_fetch_array($result)) {
            $id = strip_tags($row['id']);
            $kudos_date = strip_tags($row['kudos_date']);
            $kudos_time = strip_tags($row['kudos_time']);
            $data .= '<tr>';

            if (checkRole('kudos_admin')) {
                $data .= '<td class="align-middle" style="width:5%;">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input team-kudos-box" id="team_' . htmlspecialchars($id) . '" data-kudos-read-id="' . htmlspecialchars($id) . '">
                        <label class="form-check-label" for="team_' . htmlspecialchars($id) . '"></label>
                    </div>
                </td>';
            }

            $data .= '<td class="align-middle"><small class="text-muted">' . htmlspecialchars($kudos_date) . ' ' . htmlspecialchars($kudos_time) . '</small></td>';
			
			$from = strip_tags($row['kudos_from']);
			$from_query = "SELECT first_name, last_name FROM users WHERE user = ?";
            
			if ($stmt = mysqli_prepare($dbc, $from_query)) {
                mysqli_stmt_bind_param($stmt, "s", $from);
                mysqli_stmt_execute($stmt);
                $from_result = mysqli_stmt_get_result($stmt);
                $from_row = mysqli_fetch_array($from_result);
                $first_name = htmlspecialchars($from_row['first_name'] ?? '');
                $last_name = htmlspecialchars($from_row['last_name'] ?? '');
                mysqli_stmt_close($stmt);
            }

			$teamwork = is_numeric($row['teamwork']) ? (int)$row['teamwork'] : 0;
			$knowledge = is_numeric($row['knowledge']) ? (int)$row['knowledge'] : 0;
			$service = is_numeric($row['service']) ? (int)$row['service'] : 0;
			$notes_public = htmlspecialchars(strip_tags($row['notes_public'] ?? '')); 

            $from_name = $first_name . ' ' . $last_name;

            if (empty($first_name)) {
                $data .= '<td class="align-middle"><span class="badge bg-audit-raspberry shadow-sm"><i class="fa-solid fa-user-slash"></i> Deleted Account</span></td>';
            } else {
                $data .= '<td class="align-middle">' . $from_name . '</td>';
            }

            $data .= '<td class="align-middle">' . htmlspecialchars($row['recipient_name']) . '</td>';

            if ($teamwork == 0) {
                $data .= '<td class="align-middle"><span class="text-center">N/A</span></td>';
            } else {
                $data .= '<td class="align-middle">';
                $data .= generateKudosStars($teamwork);
                $data .= '<span class="ms-1">' . htmlspecialchars($teamwork) . '</span></td>';
            }

            if ($knowledge == 0) {
                $data .= '<td class="align-middle"><span class="text-center">N/A</span></td>';
            } else {
                $data .= '<td class="align-middle">';
                $data .= generateKudosStars($knowledge);
                $data .= '<span class="ms-1">' . htmlspecialchars($knowledge) . '</span></td>';
            }

            if ($service == 0) {
                $data .= '<td class="align-middle"><span class="text-center">N/A</span></td>';
            } else {
                $data .= '<td class="align-middle">';
                $data .= generateKudosStars($service);
                $data .= '<span class="ms-1">' . htmlspecialchars($service) . '</span></td>';
            }

			$data .= '<td class="align-middle" style="width:20%;">';

			if (empty($notes_public)) {
			 	$data .= '';
			} else {
			 	$data .= '<button class="btn btn-outline-orange-dark btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#public-notes-' . htmlspecialchars($id) . '" aria-expanded="false" aria-controls="public-notes-' . htmlspecialchars($id) . '"><i class="fa-brands fa-readme"></i> Read Notes</button>';
			    $data .= '<div class="collapse mt-2" id="public-notes-' . htmlspecialchars($id) . '">' . nl2br($notes_public) . '</div>';
			}

			$data .= '</td></tr>';
        }
        mysqli_free_result($result);
    }

    $data .= '</tbody></table></div>';
    echo $data;

} else {
    echo 'No session found. Please log in.';
}

mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	$('.team-select-all').on('click', function(e) {
        if ($(this).is(':checked', true)) {
            $(".team-kudos-box").prop('checked', true);
        } else {
            $(".team-kudos-box").prop('checked', false);
        }
		toggleTeamButton();
    });
	$(".team-kudos-box").on('click', function(e) {
        if ($(this).is(':checked', true)) {
            $(".team-select-all").prop("checked", false);
        } else {
            $(".team-select-all").prop("checked", false);
        }
		if ($(".team-kudos-box").not(':checked').length == 0) {
            $(".team-select-all").prop("checked", true);
        }
		toggleTeamButton();
    });
	function toggleTeamButton() {
        if ($('.team-kudos-box:checked').length > 0) {
            $('#team-kudos-btn').fadeIn();
        } else {
            $('#team-kudos-btn').fadeOut();
        }
    }
    $('.team_kudos_hide_n_seek').prop("disabled", true);
    $('#kudos_table').on("click", 'input:checkbox', function() {
        if ($(this).is(':checked')) {
            $('.team_kudos_hide_n_seek').prop("disabled", false);
        } else {
            if ($('.team-kudos-box').filter(':checked').length < 1) {
                $('.team_kudos_hide_n_seek').attr('disabled', true);
            }
        }
    });
});
</script>