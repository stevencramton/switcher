<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_profile')) {
    header("Location:../../index.php?msg1");
    exit();
}

$data = '<style> .card-img-tops { width: 100%; height: 15vw; object-fit: cover; } </style>
<div class="row row-cols-1 row-cols-md-4 g-3 mb-3">';

$query = "SELECT id, switch_id, first_name, last_name, role, users.role_id, account_locked, 
			last_activity, user, profile_pic, display_name, unique_id, role_name, role_icon
    	  FROM users 
    	  JOIN roles_dev ON users.role_id = roles_dev.role_id 
    	  WHERE account_delete = 0";

if ($stmt = mysqli_prepare($dbc, $query)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    confirmQuery($result);

    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
  	  	$profile_pic = htmlspecialchars($row['profile_pic'], ENT_QUOTES, 'UTF-8');
        $first_name = htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8');
        $last_name = htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8');
        $role_name = htmlspecialchars($row['role_name'], ENT_QUOTES, 'UTF-8');
        $account_locked = htmlspecialchars($row['account_locked'], ENT_QUOTES, 'UTF-8');

        $status = $account_locked >= 1 ? '<i class="fas fa-lock" style="color:#b54398;"></i>' : '<i class="fas fa-check-circle text-success"></i>';

        $data .= '<div class="col">
                    <div class="card h-100 p-2">
                        <img class="card-img-tops bg-white shadow rounded p-1" src="' . $profile_pic . '" loading="lazy">
                        <div class="card-body">
                            <h5 class="card-title mb-2">' . $first_name . ' ' . $last_name . ' </h5>
                            <div class="badge bg-info mb-2">' . $role_name . '</div>
                            <p class="card-text text-secondary">This is a wider card with supporting text below as a natural.</p>
                            <p class="mb-0">
                                <small>Help Desk</small><br>
                                <small>Enterprise Technology & Services</small>
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="justify-content-between align-items-cente w-100">
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-sm btn-secondary w-50 disabled"><i class="fa-solid fa-building-columns"></i> PSU</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-50">View</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
    }
    mysqli_stmt_close($stmt);
}

$data .= '</div>';

echo $data;

mysqli_close($dbc);