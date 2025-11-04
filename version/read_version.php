<?php
session_start();

include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('version_view')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])){
	
    header("Location:../../index.php?msg1");
    exit();
	
} else {

    $query = "SELECT MAX(id) AS mostRecent FROM versions";
    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $mostRecent);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed");
    }

    $mostRecent = intval($mostRecent);

    $version_query = "SELECT * FROM versions WHERE id = ?";
    if ($stmt = mysqli_prepare($dbc, $version_query)) {
        mysqli_stmt_bind_param($stmt, 'i', $mostRecent);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $version_row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        die("Database query failed");
    }

    $version = htmlspecialchars($version_row['version']);
    $version_notes = htmlspecialchars($version_row['notes']);

    $data = '<script>
    $("#version_table").DataTable({
        responsive: true,
        "order": [[4, "desc"]],
        columnDefs: [
            { "orderable": false, "targets": [3,4] },
            { "targets": 4, "visible": false }
        ]
    });
    </script>';

    $data .= '<div class="card" style="--bs-card-border-color: #84adeb;">
                <div class="card-header d-flex align-items-center p-3 shadow-sm" style="background-color:#edf4ff;">
                    <i class="fas fa-code-branch fa-2x pe-3" style="color:#0d6efd;"></i>
                    <div class="w-100">
                        <h6 class="mb-0 lh-100 fw-bold" style="color:#0d6efd;">Versions';

    if (checkRole('version_create')) {
        $data .= '<button type="button" class="btn btn-sm btn-primary float-end" data-bs-toggle="modal" data-bs-target="#new_version_modal">
                    <i class="fas fa-plus-circle"></i> New Version
                  </button>';
    }

    $data .= '</h6><small style="color:#0d6efd;">Switchboard Versions: ' . $version . '</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-1">
                    <table class="table table-hover table-sm" id="version_table" width="100%">
                        <thead class="table-light">
                            <th>Version</th>
                            <th>Release Notes</th>
                            <th>Date</th>
                            <th>Author</th>
                            <th></th>';

    if (checkRole('version_edit') || checkRole('version_delete')) {
        $data .= '<th></th>';
    }

    $data .= '</thead><tbody>';

    $query = "SELECT * FROM versions ORDER BY id ASC";
    if ($result = mysqli_query($dbc, $query)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $version_id = intval($row['id']);
            $version_release = htmlspecialchars($row['version']);
            $version_notes = nl2br(htmlspecialchars($row['notes']));
            $version_date = htmlspecialchars($row['date']);
            $version_released_by = htmlspecialchars($row['released_by']);

            $data .= '<tr id="version_info">
                        <td><span class="badge bg-cool-ice">' . $version_release . '</span></td>
                        <td>' . $version_notes . '</td>
                        <td style="width:15%;">' . $version_date . '</td>
                        <td style="width:10%;">' . $version_released_by . '</td>
                        <td>' . $version_id . '</td>';

            if (checkRole('version_edit') || checkRole('version_delete')) {
                $data .= '<td style="width:5%">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa fa-cogs"></i>
                                <span class="caret"></span></button>
                                <ul class="dropdown-menu dropdown-menu-end">';
                if (checkRole('version_edit')) {
                    $data .= '<li><a class="dropdown-item" onclick="getVersionDetails(' . $version_id . ')" href="#"><i class="far fa-edit"></i> Edit</a></li>';
                }
                if (checkRole('version_edit') && checkRole('version_delete')) {
                    $data .= '<div class="dropdown-divider"></div>';
                }
                if (checkRole('version_delete')) {
                    $data .= '<li><a class="dropdown-item" onclick="beginVersionDelete(' . $version_id . ')" id="' . $version_id . '" href="#"><i class="far fa-trash-alt"></i> Delete</a></li>';
                }
                $data .= '</ul></div></td>';
            }

            $data .= '</tr>';
        }
    }

    $data .= '</tbody>
            </table>
        </div>
    </div>
</div>';

    echo $data;
}

mysqli_close($dbc);
?>
