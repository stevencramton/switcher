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

if (isset($_SESSION['id'])) {

    $data = '<script>
    $(document).ready(function(){
        $("#roles_search").keyup(function(){
            var search = $("#roles_search").val();
            var len = search.length;
            if (len > 0){
                $.ajax({
                    url:"ajax/roles/read_roles_search.php",
                    data: {search:search},
                    type: "POST",
                    success:function(data){
                        if(!data.error){
                            $("#roles_search_result").html(data);
                            $("#roles_table").fadeOut("fast", function(){
                                $("#role_search_icon").html("<i class=\\"fa-regular fa-circle-xmark\\" onclick=\\"clearRoleSearch();\\"></i>")
                                $("#roles_search_result").css("display", "");
                            });
                        }
                    }
                });
            } else {
                $("#role_search_icon").html("<i class=\\"fas fa-search\\"></i>")
                $("#roles_search_result").css("display", "none")
                $("#roles_table").css("display","");
            }
        });
    });
    </script>

    <style>
    input:focus{
        outline:none;
    }
    .role-info-button{
        cursor:pointer;
        width:65%
    }
    </style>

    <div class="row gx-3">
      <div class="col-sm-4">
        <div class="card mb-3">
          <div class="card-header align-items-center">
            <h5 class="dark-gray">
              <i class="fas fa-shield-alt"></i> Roles
                <button type="button" id="addRoleBtn" class="btn btn-primary btn-sm float-end">
                  <i class="fas fa-plus-circle"></i> Add Role
                </button>
            </h5>
          </div><!-- End card-header -->
          <div class="card-body">
            <div class="input-group mb-3">
                <input type="text" id="roles_search" class="form-control form-control-lg" placeholder="Search..." autocomplete="off">
           	 	<span id="role_search_icon" class="input-group-text" onclick="clearRoleSearch();" style="cursor:pointer;"><i class="fas fa-search"></i></span>
            </div>

              <table class="table table-hover tablenew" id="roles_table">
                  <thead>
                    <tr>
                      <th>Role Name</th>
                      <th style="text-align:right;">Manage
                      </th>
                    </tr>
                  </thead>
                  <tbody>';

	$query = "SELECT * FROM roles_dev";
    
	if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $role_id = htmlspecialchars($row['role_id']);
            $role_icon = htmlspecialchars($row['role_icon']);
            $role_name = htmlspecialchars($row['role_name']);

            $data .= '<tr class="align-middle">
                        <td class="role-info-button">
                            <i class="'.$role_icon.'"></i><span class="ms-2"> '.$role_name.' </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="beginEditRole('.$role_id.')">Edit</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="deleteRole('.$role_id.')"><i class="fas fa-trash-alt"></i></button>
                                <button type="button" class="btn btn-secondary" onclick="filterUserTable('.$role_id.')"><i class="fas fa-filter"></i></button>
                            </div>
                        </td>
                      </tr>';
        }
        mysqli_stmt_close($stmt);
    }

    $data .= '</tbody>
        </table>
        <div id="roles_search_result" style="display:none;"></div>
    </div>
    <div class="card-footer text-muted"></div>
  </div>

  </div>
  
    <div class="col-sm-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="dark-gray">
                    <i class="fas fa-users"></i> Users
                    <div id="userFilterBtnGroup" class="btn-group float-end" style="display:none">
                        <button type="button" id="resetUserFilterBtn" class="btn btn-outline-primary btn-sm" onclick="resetUserFilter();">
                            <i class="fas fa-sync-alt"></i> Reset Filter
                        </button>
                        <button type="button" class="btn btn-primary btn-sm editUserRoleBtn" disabled>
                            <i class="fas fa-edit"></i> Edit Role
                        </button>
                    </div>
                    <button type="button" id="editUserRoleBtnOnly" class="btn btn-primary btn-sm float-end editUserRoleBtn" disabled>
                        <i class="fas fa-edit"></i> Edit Role
                    </button>
                </h5>
            </div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" id="user_impersonate_search" class="form-control form-control-lg" placeholder="Search..." autocomplete="off">
                    <span id="user_search_icon" class="input-group-text" onclick="clearUserSearch();" style="cursor:pointer;"><i class="fas fa-search"></i></span>
                </div>
                <div class="records_users_impersonate"></div>
                <div id="user_search_result" style="display:none"></div>
            </div>
            <div class="card-footer text-muted"><small></small></div>
        </div>
    </div>

</div>';

echo $data;

mysqli_close($dbc);

}
?>

<script>
function filterUserTable(role_id) {
	$.get("ajax/roles/read_users_impersonate.php",{role_id: role_id},  function (data, status) {
		$(".records_users_impersonate").html(data);
		$("#editUserRoleBtnOnly").fadeOut("fast",function(){
			$("#userFilterBtnGroup").fadeIn("fast");
		})
	});
};
</script>

<script>
function readUsersImpersonate() {
	$.get("ajax/roles/read_users_impersonate.php",  function (data, status) {
		$(".records_users_impersonate").html(data);
	});
};

$(document).ready(function(){
	readUsersImpersonate();
});
</script>

<script>
$(document).ready(function(){
	$(".editUserRoleBtn").on("click", function(){
 	   var len = $(".chk-box-role-user-select:checked").length;
	   if ( len === 1){
		   var user_id = $(".chk-box-role-user-select:checked").data("user-id");
		   beginEditUserRole(user_id);
	   } else if (len > 1){
		   $("#editMultipleUserRoleModal").modal("show");
	   }
	})
});
</script>

<script>
$(document).ready(function(){
 	$("#addRoleBtn").on("click", function(){
		readAddRole();
		$("#roles_tab").removeClass("show active");
		$("#add_role_tab").addClass("show active");
	})
});

function clearRoleSearch(){
  	$("#roles_search").val("");
	$("#roles_search_result").fadeOut("fast", function(){
   	 	$("#role_search_icon").html("<i class='fas fa-search'></i>")
    	$("#roles_table").fadeIn("fast");
  	});
}

function clearUserSearch(){
	$("#user_impersonate_search").val("");
	$("#user_search_result").fadeOut("fast", function(){
    	$("#user_search_icon").html("<i class='fas fa-search'></i>")
    	$(".records_users_impersonate").fadeIn("fast");
  	});
}

function resetUserFilter(){
	readUsersImpersonate();
	
	$("#userFilterBtnGroup").fadeOut("fast", function(){
		$("#editUserRoleBtnOnly").fadeIn("fast");
	});
}
</script>

<script>
$(document).ready(function(){
	$("#user_impersonate_search").on("keyup", function() {
		if ($("#user_impersonate_search").val() == ''){
			$("#user_search_icon").html("<i class='fas fa-search'></i>");
		} else {
			$("#user_search_icon").html("<i class='fa-regular fa-circle-xmark cancelUserSearch'></i>");
		}
		$('.cancelUserSearch').click(function() {
			$("#user_impersonate_search").val("");
			
			var value = $("#user_impersonate_search").val().toLowerCase();
			$("#impersonate_user_table tbody tr").filter(function() {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
			$("#user_impersonate_search").find('.fa-regular').removeClass('fa-circle-xmark').addClass("fa-search");
		});

		var value = $(this).val().toLowerCase();
		$("#impersonate_user_table tbody tr").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
});
</script>