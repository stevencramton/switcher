<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('user_role')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])) {
    header("Location:../../index.php?msg1");
    exit();
}

$data = '<script>
$(document).ready(function(){
    $("[data-toggle=\\"popover\\"]").popover()
});
</script>

<h5 class="dark-gray"> <i class="fas fa-user-shield"></i> Add Role
    <button type="button" id="cancelAddRoleBtn" class="btn btn-sm btn-hot float-end" onclick="cancelAddRole();"><i class="fas fa-undo-alt"></i></button>

    <div class="btn-group me-2 float-end">
        <button type="button" class="btn btn-sm btn-sm btn-slate-dark" data-bs-toggle="modal" data-bs-target="#addRoleTypeModal">
            <i class="fas fa-plus-circle"></i> Role Type
        </button>
        <button type="button" class="btn btn-sm btn-outline-slate-dark" data-bs-toggle="modal" data-bs-target="#addRoleCatModal">
            <i class="fas fa-folder-plus"></i> Role Category
        </button>
    </div>
</h5>

<hr>

<div class="row gx-3">
    <div class="col-md-6">
        <div class="input-group mb-3">
            <div class="form-floating form-floating-group flex-grow-1">
                <input type="text" class="form-control shadow-sm" id="role-name" name="role-name" placeholder="Role Name...">
                <label for="role-name">Role Name...</label>
            </div>
            <button type="button" class="btn btn-light-gray shadow-sm" id="role_name_clear">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>
        </div>
    </div> 
    
    <div class="col-md-6">
        <div class="input-group mb-3" style="height:59px;">
            <input id="role-icon" class="form-control form-control-lg shadow-sm icp icp-auto" placeholder="Click to search icons..." type="text"/>
            <span id="" class="input-group-text text-secondary shadow-sm"></span>
        </div>
    </div>
</div>

<div class="input-group mb-3">
    <div class="form-floating form-floating-group flex-grow-1">
        <textarea class="form-control shadow-sm" id="addRoleDescription" placeholder="Role Description..." style="height: 120px;"></textarea>
        <label for="addRoleDescription">Role Description...</label>
    </div>
    <button type="button" class="btn btn-light-gray shadow-sm reset_role_description">
        <i class="fa-solid fa-circle-xmark"></i>
    </button>
</div>';

$x = 0;
$i = 1;

$role_cat_query = "SELECT * FROM roles_categories";

if ($stmt = mysqli_prepare($dbc, $role_cat_query)) {
    mysqli_stmt_execute($stmt);
    $role_cat_result = mysqli_stmt_get_result($stmt);
    $cat_count = mysqli_num_rows($role_cat_result);

    while ($role_cat_row = mysqli_fetch_array($role_cat_result)) {
        $role_cat_name = htmlspecialchars($role_cat_row['role_cat_name']);
        $role_cat_icon = htmlspecialchars($role_cat_row['role_cat_icon']);
        $role_cat_id = htmlspecialchars($role_cat_row['role_cat_id']);

        if ($x == 0 && $i !== $cat_count) {
            $data .= '<div class="row gx-3">
            <div class="col-sm-4">
                <div class="card shadow border-0 mb-3">
                <div class="card-header">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input role-check-select-all">
                        <label class="form-check-label" for=""><i class="' . $role_cat_icon . '" aria-hidden="true"></i> ' . $role_cat_name . '</label>
                        <span class="float-end">
                            <a href="javascript:void(0);" onclick="beginEditRoleCat(' . $role_cat_id . ')">
                                <i class="bi bi-gear-wide-connected text-secondary"></i>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="card-body">';
            
            $role_type_query = "SELECT * FROM roles_type WHERE role_type_category = ?";
            if ($stmt_type = mysqli_prepare($dbc, $role_type_query)) {
                mysqli_stmt_bind_param($stmt_type, "i", $role_cat_id);
                mysqli_stmt_execute($stmt_type);
                $role_type_result = mysqli_stmt_get_result($stmt_type);

                while ($role_type_row = mysqli_fetch_array($role_type_result)) {
                    $role_type_name = htmlspecialchars($role_type_row['role_type_name']);
                    $role_type_db = htmlspecialchars($role_type_row['role_type_db_name']);
                    $role_type_category = htmlspecialchars($role_type_row['role_type_category']);
                    $role_type_description = htmlspecialchars($role_type_row['role_type_description'] ?? '');
                    $role_type_id = htmlspecialchars($role_type_row['role_type_id']);
                    
                    $data .= '<div class="form-check">
                    <input type="checkbox" class="form-check-input role-check-select" data-db-name="' . $role_type_db . '">
                    <label class="form-check-label" for="">
                        <span data-toggle="popover" data-trigger="hover" data-content="' . $role_type_description . '"> ' . $role_type_name . '</span>
                    </label>
                    <span class="float-end">
                        <a href="javascript:void(0);" onclick="beginEditRoleType(' . $role_type_id . ')">
                            <i class="bi bi-sliders text-secondary"></i>
                        </a>
                    </span>
                </div>';
                }
                mysqli_stmt_close($stmt_type);
            }
            $data .= '</div><!-- end card-body -->
                </div><!--end card -->
                </div> <!-- end col-sm-4 -->';

            $x++;
            $i++;
        } else if ($x == 1 && $i !== $cat_count) {
            $data .= '<div class="col-sm-4">
            <div class="card shadow border-0 mb-3">
                <div class="card-header">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input role-check-select-all">
                        <label class="form-check-label" for=""><i class="' . $role_cat_icon . '" aria-hidden="true"></i> ' . $role_cat_name . '</label>
                        <span class="float-end">
                        <a href="javascript:void(0);" onclick="beginEditRoleCat(' . $role_cat_id . ')">
                            <i class="bi bi-gear-wide-connected text-secondary"></i>
                        </a>
                        </span>
                    </div>
                </div>
                <div class="card-body">';
            
            $role_type_query = "SELECT * FROM roles_type WHERE role_type_category = ?";
            if ($stmt_type = mysqli_prepare($dbc, $role_type_query)) {
                mysqli_stmt_bind_param($stmt_type, "i", $role_cat_id);
                mysqli_stmt_execute($stmt_type);
                $role_type_result = mysqli_stmt_get_result($stmt_type);

                while ($role_type_row = mysqli_fetch_array($role_type_result)) {
                    $role_type_name = htmlspecialchars($role_type_row['role_type_name']);
                    $role_type_db = htmlspecialchars($role_type_row['role_type_db_name']);
                    $role_type_category = htmlspecialchars($role_type_row['role_type_category']);
                    $role_type_description = htmlspecialchars($role_type_row['role_type_description'] ?? '');
                    $role_type_id = htmlspecialchars($role_type_row['role_type_id']);

                    $data .= '<div class="form-check">
                    <input type="checkbox" class="form-check-input role-check-select" data-db-name="' . $role_type_db . '">
                    <label class="form-check-label" for="">
                        <span data-toggle="popover" data-trigger="hover" data-content="' . $role_type_description . '"> ' . $role_type_name . '</span>
                    </label>
                    <span class="float-end">
                        <a href="javascript:void(0);" onclick="beginEditRoleType(' . $role_type_id . ')">
                            <i class="bi bi-sliders text-secondary"></i>
                        </a>
                    </span>
                </div>';
                }
                mysqli_stmt_close($stmt_type);
            }
            $data .= '</div><!-- end card-body -->
                </div><!--end card -->
                </div> <!-- end col-sm-4 -->';

            $x++;
            $i++;
        } else if ($x == 2 || $i == $cat_count) {
            if ($x == 0) {
                $data .= '<div class="row">';
            }

            $data .= '<div class="col-sm-4';

            if ($x == 0) {
                $data .= '';
            } else if ($x == 2) {
                $data .= '';
            } else if ($x == 1) {
                $data .= '';
            }

            $data .= '">
            <div class="card shadow border-0 mb-3">
                <div class="card-header">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input role-check-select-all">
                        <label class="form-check-label" for=""><i class="' . $role_cat_icon . '" aria-hidden="true"></i> ' . $role_cat_name . '</label>
                        <span class="float-end">
                        <a href="javascript:void(0);" onclick="beginEditRoleCat(' . $role_cat_id . ')">
                            <i class="bi bi-gear-wide-connected text-secondary"></i>
                        </a>
                        </span>
                    </div>
                </div>
                <div class="card-body">';
            
            $role_type_query = "SELECT * FROM roles_type WHERE role_type_category = ?";
            if ($stmt_type = mysqli_prepare($dbc, $role_type_query)) {
                mysqli_stmt_bind_param($stmt_type, "i", $role_cat_id);
                mysqli_stmt_execute($stmt_type);
                $role_type_result = mysqli_stmt_get_result($stmt_type);

                while ($role_type_row = mysqli_fetch_array($role_type_result)) {
                    $role_type_name = htmlspecialchars($role_type_row['role_type_name']);
                    $role_type_db = htmlspecialchars($role_type_row['role_type_db_name']);
                    $role_type_category = htmlspecialchars($role_type_row['role_type_category']);
                    $role_type_description = htmlspecialchars($role_type_row['role_type_description'] ?? '');
                    $role_type_id = htmlspecialchars($role_type_row['role_type_id']);

                    $data .= '<div class="form-check">
                    <input type="checkbox" class="form-check-input role-check-select" data-db-name="' . $role_type_db . '">
                    <label class="form-check-label" for="">
                        <span data-toggle="popover" data-trigger="hover" data-content="' . $role_type_description . '"> ' . $role_type_name . '</span>
                    </label>
                    <span class="float-end">
                        <a href="javascript:void(0);" onclick="beginEditRoleType(' . $role_type_id . ')">
                            <i class="bi bi-sliders text-secondary"></i>
                        </a>
                    </span>
                </div>';
                }
                mysqli_stmt_close($stmt_type);
            }
            $data .= '</div><!-- end card-body -->
                </div><!--end card -->
                </div> <!-- end col-sm-4 -->
                </div><!-- end row -->';

            $x = 0;
            $i++;
        }
    }
    mysqli_stmt_close($stmt);
}

$data .= '<div class="row">
  		<div class="col-lg-12">
    		<div class="card">
        		<div class="card-body">
        			<button type="button" class="btn btn-slate-dark float-end" onclick="addNewRole();"><i class="fas fa-spinner"></i> Save Role</button>
        			<button type="button" class="btn btn-hot me-2 float-end" id="cancelAddRoleBtn" onclick="cancelAddRole();"><i class="fas fa-undo-alt"></i> Cancel</button>
      			</div>
    		</div>
  	 	 </div>
	  </div>
</div>';

echo $data;

mysqli_close($dbc);
?>

<script>
$(document).ready(function(){
	$('#role-icon').iconpicker({
		placement:'bottomLeft',
		showFooter: false,
		hideOnSelect: true,
		component: '.input-group-text'
	})
});
</script>

<script>
$(document).ready(function(){
	$(".role-check-select").on('click', function(e) {
    
	if ($(this).is(':checked',true)) {
       $(this).closest(".card").find('.card-header').find(".role-check-select-all").prop("checked", false);
    } else {
      $(this).closest(".card").find('.card-header').find(".role-check-select-all").prop("checked", false);
    }

    if ($(this).closest(".card-body").find(".role-check-select").not(':checked').length == 0) {
      $(this).closest(".card").find('.card-header').find(".role-check-select-all").prop("checked", true);

    }
});

$(".role-check-select-all").click(function(){
	if ($(this).is(':checked')) {
		 $(this).closest(".card").find('.card-body').find('.role-check-select').prop('checked', true);
	} else {
		$(this).closest(".card").find('.card-body').find('.role-check-select').prop('checked', false);
	}
 	})
});
</script>

<script>
function cancelAddRole(){
	$("#add_role_tab").removeClass("show active");
	$("#roles_tab").addClass("show active");
};
</script>
  
<script>
$(document).ready(function(){
	$(".reset_role_description").click(function() {
	    $("#addRoleDescription").val("");
	});
	$("#role_name_clear").on("click", function(){
		$("#role-name").val("");
	})
});
</script>