<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!isset($_SESSION['user'])) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!checkRole('user_role')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['search'])) {
	$search = $_POST['search'];
	$query = "SELECT * FROM roles_dev WHERE role_name LIKE CONCAT('%', ?, '%')";

    if ($stmt = mysqli_prepare($dbc, $query)) {
  	  	mysqli_stmt_bind_param($stmt, 's', $search);
		mysqli_stmt_execute($stmt);

		$result = mysqli_stmt_get_result($stmt);

		$data = '<table class="table table-hover tablenew" id="roles_table">
            <thead>
              <tr>
                <th>Role Name</th>
                <th style="text-align:right;">Edit</th>
              </tr>
            </thead>
            <tbody>';

		while ($row = mysqli_fetch_assoc($result)) {
        	$role_name = htmlspecialchars($row['role_name']);
            $role_id = htmlspecialchars($row['role_id']);
            $role_icon = htmlspecialchars($row['role_icon']);

          	$data .= '<tr>
                <td class="role-info-button" onclick="readRoleInfo(' . $role_id . ')">
                  <i class="' . $role_icon . '"></i><span class="ml-2"> ' . $role_name . '</span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-secondary" onclick="beginEditRole(' . $role_id . ')">Edit</button>
                     <button type="button" class="btn btn-outline-secondary" onclick="deleteRole(' . $role_id . ')"><i class="fas fa-trash-alt"></i></button>
                     <button type="button" class="btn btn-secondary" onclick="filterUserTable(' . $role_id . ')"><i class="fas fa-filter"></i></button>
                  </div>
                </td>
              </tr>';
        }

    	mysqli_stmt_close($stmt);
		mysqli_close($dbc);
		
		$data .= '</tbody></table>';
		echo $data;
    } else {
    	echo "Error preparing the SQL statement.";
    }
}