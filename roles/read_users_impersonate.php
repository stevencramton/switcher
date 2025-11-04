<?php
ob_start();
session_start();
include "../../mysqli_connect.php";
include "../../templates/functions.php";

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_manage')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])){
    header("Location:../../index.php?msg1");
    exit();
} else {
    
	$data = '<table class="table table-hover tablenew" id="impersonate_user_table">
                <thead>
                    <tr>
                        <th>
                            <div class="form-check">
                                <input type="checkbox" value="" class="form-check-input select-all-role-users">
                                <label class="form-check-label"></label>
                            </div>
                        </th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th class="d-none">Username</th>
                        <th>Role</th>
                        <th style="text-align: right;">View As</th>
                    </tr>
                </thead>
                <tbody>';

    if (isset($_GET['role_id'])) {
        $role_id = $_GET['role_id'];
		$query = "SELECT * FROM users WHERE role_id = ? AND account_delete = 0";

        if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $role_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
			confirmQuery($result);

            while ($row = mysqli_fetch_array($result)) {
                $data .= '<tr class="align-middle" id="user_info">
                            <td class="align-middle">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input chk-box-role-user-select" id="' . $row['id'] . '" data-user-id="' . $row['id'] . '">
                                    <label class="form-check-label"></label>
                                </div>
                            </td>';

                $data .= '<td><img src="' . $row['profile_pic'] . '" class="profile-photo"></td>';
                $data .= '<td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>';
                $data .= '<td class="d-none">' . $row['user'] . '</td>';

                $role_query = "SELECT * FROM roles_dev WHERE role_id = ?";
                if ($role_stmt = mysqli_prepare($dbc, $role_query)) {
                    mysqli_stmt_bind_param($role_stmt, 'i', $row['role_id']);
                    mysqli_stmt_execute($role_stmt);
                    $role_result = mysqli_stmt_get_result($role_stmt);
               	 	confirmQuery($role_result);

                    while ($role_row = mysqli_fetch_array($role_result)) {
                        $role = $role_row['role_name'];
                        $role_icon = $role_row['role_icon'];
                        
                        $data .= '<td><i class="' . $role_icon . '"></i><span class="ms-2">' . $role . '</span></td>';
                    }
                    mysqli_stmt_close($role_stmt);
                }

                $data .= '<td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="beginEditUserRole(' . $row['id'] . ')">Edit</button>';
                if (checkRole('user_impersonate')) {
                    $data .= '<a class="btn btn-sm btn-secondary btn-sm" href="https://switchboardapp.net/dashboard/actions/account_impersonate.php?impersonate=' . $row['unique_id'] . '">
                                <i class="far fa-eye"></i></a>';
                }

                $data .= '</div>
                        </td>
                    </tr>';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $query = "SELECT * FROM users WHERE account_delete = 0";

        if ($result = mysqli_query($dbc, $query)) {
            confirmQuery($result);

            while ($row = mysqli_fetch_array($result)) {
                $data .= '<tr class="align-middle" id="user_info">
                            <td class="align-middle">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input chk-box-role-user-select" id="' . $row['id'] . '" data-user-id="' . $row['id'] . '">
                                    <label class="form-check-label"></label>
                                </div>
                            </td>';

                $data .= '<td><img src="' . $row['profile_pic'] . '" class="profile-photo"></td>';
                $data .= '<td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>';
                $data .= '<td class="d-none">' . $row['user'] . '</td>';

                $role_query = "SELECT * FROM roles_dev WHERE role_id = ?";
                if ($role_stmt = mysqli_prepare($dbc, $role_query)) {
                    mysqli_stmt_bind_param($role_stmt, 'i', $row['role_id']);
                    mysqli_stmt_execute($role_stmt);
                    $role_result = mysqli_stmt_get_result($role_stmt);
                    
                    confirmQuery($role_result);

                    while ($role_row = mysqli_fetch_array($role_result)) {
                        $role = $role_row['role_name'];
                        $role_icon = $role_row['role_icon'];
                        
                        $data .= '<td><i class="' . $role_icon . '"></i><span class="ms-2">' . $role . '</span></td>';
                    }
                    mysqli_stmt_close($role_stmt);
                }

                $data .= '<td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="beginEditUserRole(' . $row['id'] . ')">Edit</button>';
                if (checkRole('user_impersonate')) {
                    $data .= '<a class="btn btn-sm btn-secondary btn-sm" href="https://switchboardapp.net/dashboard/actions/account_impersonate.php?impersonate=' . $row['unique_id'] . '">
                                <i class="far fa-eye"></i></a>';
                }

                $data .= '</div>
                        </td>
                    </tr>';
            }
        }
    }

    $data .= '</tbody></table></div>
			<div class="modal" id="editMultipleUserRoleModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title dark-gray"><i class="fas fa-users"></i> Edit Multiple Roles</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            <div class="mb-3">
                                <label class="form-label" for="editMultiUserRole">Role:</label>
                                <select class="form-select" name="editMultiUserRole" id="editMultiUserRole">';
                                    $role_query_two = "SELECT * FROM roles_dev";
                                    if ($role_result_two = mysqli_query($dbc, $role_query_two)){
                                        while($role_row_two = mysqli_fetch_array($role_result_two)){
                                            $data .= '<option value="' . $role_row_two['role_id'] . '">' . $role_row_two['role_name'] . '</option>';
                                        }
                                    }
                                $data .= '</select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-undo-alt"></i> Cancel</button>
                            <button type="button" class="btn btn-orange" onclick="editMultipleUserRoles()"><i class="fas fa-cloud-upload-alt"></i> Update</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal" id="editUserRoleModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title dark-gray"><i class="fas fa-user-edit"></i> Edit User Role</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="background-color:#f8f8f8;">
                            <div class="shadow-sm rounded border bg-white p-3 mb-3">
                                <div class="alert alert-default mb-0 px-0" role="alert" style="background-color:#f2fcff;color:#24607d;border-color:#67b3bd;border-bottom:1px solid #67b3bd;">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-2">
                                                <script>
                                                    $(document).ready(function(){
                                                        $("select").change(function() {
                                                            var new_icon_value = $("#editUserRole").children(":selected").attr("id");
                                                            $("#icon_value").removeClass().addClass(new_icon_value);
                                                        });
                                                    });    
                                                </script>
                                                <i class="fas fa-user-circle" style="font-size:3em;" id="icon_value"></i>
                                            </div>
                                            <div class="col-8">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span>Name: <h6 id="editUserRoleName" name="editUserRoleName"></h6></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <select class="form-select icon_select" name="editUserRole" id="editUserRole">';
                                    $role_query_two = "SELECT * FROM roles_dev";
                                    if ($role_result_two = mysqli_query($dbc, $role_query_two)){
                                        while($role_row_two = mysqli_fetch_array($role_result_two)){
                                            $data .= '<option id="' . $role_row_two['role_icon'] . '" value="' . $role_row_two['role_id'] . '">' . $role_row_two['role_name'] . '</option>';
                                        }
                                    }
                                $data .= '</select>
                            </div>
                            <input type="hidden" id="hidden_user_role_edit_id" name="hidden_user_role_edit_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-undo-alt"></i> Cancel</button>
                            <button type="button" class="btn btn-orange" onclick="saveEditUserRole()"><i class="fas fa-cloud-upload-alt"></i> Update</button>
                        </div>
                    </div>
                </div>
            </div>';

    echo $data;
}

mysqli_close($dbc);
?>

<style> .profile-photo { height: 40px;width: 40px;border-radius: 50%;vertical-align: middle;border-style: none;} </style>

<script>
$(document).ready(function() {
	
	$('.select-all-role-users').on('click', function(e) {
		if($(this).is(':checked',true)) {
			$(".chk-box-role-user-select").prop('checked', true);
		} else {
			$(".chk-box-role-user-select").prop('checked',false);
		}
	});
	
	$(".chk-box-role-user-select").on('click', function(e) {
		if($(this).is(':checked',true)) {
			$(".select-all-role-users").prop("checked", false);
		} else {
			$(".select-all-role-users").prop("checked", false);
		}
		if ($(".chk-box-role-user-select").not(':checked').length == 0) {
			$(".select-all-role-users").prop("checked", true);
		}
	});
	
	$('.editUserRoleBtn').prop("disabled", true);

	if ($('.chk-box-role-user-select').is(':checked')) {
	    $('.editUserRoleBtn').prop("disabled", false);
	} else {
	    $('.editUserRoleBtn').prop("disabled", true);
	}

	$('input:checkbox').click(function() {
		if ($(this).is(':checked')) {
	        $('.editUserRoleBtn').prop("disabled", false);
	    } else {
	      	if ($('.chk-box-role-user-select').filter(':checked').length < 1) {
	            $('.editUserRoleBtn').prop("disabled", true);
	        }
	    }
	});
	
});
</script>