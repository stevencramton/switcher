<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

$user_id = $_SESSION['switch_id'];

$query = "SELECT password, last_pw_change, account_locked FROM users WHERE switch_id = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($password, $last_pw_change, $account_locked);
$stmt->fetch();
$stmt->close();

$last_pw_change_formatted = $last_pw_change ? date("F j, Y g:i A", strtotime($last_pw_change)) : 'N/A';
$account_locked_status = $account_locked == 1 ? 'Yes' : 'No';
$is_password_removed = is_null($password);

$data = '';

if ($is_password_removed) {
    $data .= '<form role="form" name="change_pass" id="no_change_pass" class="needs-validation" action="" method="POST" novalidate>
    <div class="row gx-3">
    	<div class="col-md-8">
			<div class="card bg-white rounded-bottom rounded-top smooth-shadow-sm pt-0 mb-3">
		   		<div class="card-header">
		      		<span class="dark-gray">
		             	<i class="fas fa-user-shield me-1"></i> Your Account Password Has Been Removed
		     		</span>
		   		 	<i class="fa-solid fa-backward float-end mt-1" style="cursor:pointer;" onclick="closeAuthTabs();"></i>
		  		</div>
		     	<div class="card-body">
					<span class="text-muted"> Please contact the Switchboard admin for further assistance with authentication.</span>
				</div>
			</div>
			</div>
			<div class="col-md-4">
            	<div class="card bg-white rounded-bottom rounded-top smooth-shadow-sm pt-0 mb-3">
                	<div class="card-header">
                    	<span class="dark-gray">
                        	<i class="fas fa-shield-alt dark-gray me-1"></i> Password Info
                    	</span>
						<i class="fa-solid fa-backward float-end mt-1" style="cursor:pointer;" onclick="closeAuthTabs();"></i>
                	</div>
                	<div class="card-body p-0">
                    	<ul class="list-group list-group-flush">
                    		<li class="list-group-item">
                            	<p class="dark-gray fw-bold float-left mb-0">Password Status</p>
                            	<span class="read_account_password_status text-muted"></span>
                        	</li>
						</ul>
                	</div>
         		</div>
        	</div>
    	</div>
	</form>';

} else {
    $data .= '<script>
$(function() {
    $.toggleShowPassword = function(options) {
        const settings = $.extend({
            field: "#change_new_pass, #pass_verify, #pass_current",
            control: "#toggle_show_password"
        }, options);
		const control = $(settings.control);
        const field = $(settings.field);
		control.on("click", () => {
            const fieldType = control.is(":checked") ? "text" : "password";
            field.attr("type", fieldType);
        });
    };
	$.toggleShowPassword({
        field: "#change_new_pass, #pass_verify, #pass_current",
        control: "#profile_pass_revealer"
    });
});
</script>
	
	<form role="form" name="change_pass" id="change_pass" class="needs-validation" action="" method="POST" novalidate>
    	<div class="row gx-3">
     		<div class="col-md-8">
				<div class="card bg-white rounded-bottom rounded-top smooth-shadow-sm pt-0 mb-3">
		     		<div class="card-header">
		            	<span class="dark-gray">
		               		<i class="fas fa-user-shield me-1"></i> Change Your Password
		              	</span>
		             	<i class="fa-solid fa-backward float-end mt-1" style="cursor:pointer;" onclick="closeAuthTabs();"></i>
		        	</div>
		        	<div class="card-body">
		         		<input type="text" style="display:none;" name="change_pass_username" id="change_pass_username" autocomplete="change_pass_username">
		          	  	<div class="mb-3 row">
		             		<label for="pass_current" class="col-sm-4 col-form-label form-label">Current password</label>
		                	<div class="col-md-8 col-12">
		                   		<input type="password" class="form-control" placeholder="Current password" id="pass_current" name="pass_current" required autocomplete="current-password">
		                      	<div class="invalid-feedback">
		                        	Does not match your current password
		                    	</div>
		                 	</div>
		            	</div>
		           	 	<div class="mb-3 row">
		              		<label for="change_new_pass" class="col-sm-4 col-form-label form-label">New password</label>
		                	<div class="col-md-8 col-12">
		                   		<input type="password" class="form-control" id="change_new_pass" name="change_new_pass" placeholder="New password" required autocomplete="change_new_pass">
		                   	 	<div class="invalid-feedback"></div>
		                 	</div>
		           		</div>
		           	 	<div class="mb-2 row">
		              		<label for="pass_verify" class="col-sm-4 col-form-label form-label">Confirm new password</label>
		                 	<div class="col-md-8 col-12">
		                   		<input type="password" class="form-control" id="pass_verify" name="pass_verify" placeholder="Confirm new password" required autocomplete="pass_verify">
		                    	<div class="invalid-feedback">
		                        	New Passwords do not match
		                     	</div>
		                	</div>
		             	</div>
		           	 	<div class="row align-items-center">
		              		<div class="offset-md-4 col-md-8 col-12 mt-0">
		              	  		<div class="form-check form-switch reveal mb-4">
		                       		<input type="checkbox" class="form-check-input" id="profile_pass_revealer">
		                        	<label class="form-check-label" for="profile_pass_revealer">Show password</label>
		                    	</div>
		                   	 	<h6 class="mb-1">Password requirements:</h6>
		                    	<p>Ensure that these requirements are met:</p>
		                   	 	<ul class="list-unstyled">
		                      		<li> <i class="fa-regular fa-circle" id="length-icon"></i> Minimum 8 characters long (the more, the better)</li>
		                         	<li> <i class="fa-regular fa-circle" id="lowercase-icon"></i> At least one lowercase character</li>
		                        	<li> <i class="fa-regular fa-circle" id="uppercase-icon"></i> At least one uppercase character</li>
		                       	 	<li> <i class="fa-regular fa-circle" id="number-symbol-icon"></i> At least one number or symbol</li>
		                     	</ul>
		              		</div>
						</div>
					</div>
				</div>
			</div>
		
        	<div class="col-md-4">
            	<div class="card bg-white rounded-bottom rounded-top smooth-shadow-sm pt-0 mb-3">
                	<div class="card-header">
                    	<span class="dark-gray">
                        	<i class="fas fa-shield-alt dark-gray me-1"></i> Password Info
                    	</span>
						<i class="fa-solid fa-backward float-end mt-1" style="cursor:pointer;" onclick="closeAuthTabs();"></i>
                	</div>
                	<div class="card-body p-0">
                   	 	<ul class="list-group list-group-flush">
                        	<li class="list-group-item">
                            	<p class="dark-gray fw-bold float-left mb-0">Password Last Changed</p>
                            	<span class="text-muted">'. $last_pw_change_formatted .'</span>
                        	</li>
							<li class="list-group-item">
                            	<p class="dark-gray fw-bold float-left mb-0">Password Status</p>
                            	<span class="read_account_password_status text-muted"></span>
                        	</li>
							<li class="list-group-item">
                            	<p class="dark-gray fw-bold float-left mb-0">Account Locked</p>
                            	<span class="text-muted">'. $account_locked_status .'</span>
                        	</li>
                    	</ul>
                	</div>
                	<div class="card-footer">
                    	<div class="btn-group d-flex" role="group" aria-label="Basic example">
                        	<button type="button" class="btn btn-atomic w-75" id="pass_submit">
                            	<i class="fas fa-cloud-upload-alt"></i> Change Password
                        	</button>
                        	<button type="reset" class="btn btn-outline-orange w-25" id="clear-pass-form">
                            	<i class="fas fa-eraser"></i>
                        	</button>
                    	</div>
                	</div>

            	</div>
        	</div>
    	</div>
	</form>';
}

echo $data;

mysqli_close($dbc);
?>

<script>
$(document).ready(function() {
	function validatePassword() {
		var newPass = $("#change_new_pass").val();
		var confirmPass = $("#pass_verify").val();
	 	var hasLowercase = /[a-z]/.test(newPass);
	 	var hasUppercase = /[A-Z]/.test(newPass);
		var hasNumberOrSymbol = /[0-9!@#$%^&*(),.?":{}|<>]/.test(newPass);
        
		$("#length-icon").removeClass("fa-circle-xmark text-danger").addClass("fa-circle").css('opacity', 1.0);
	 	$("#lowercase-icon").removeClass("fa-circle-xmark text-danger").addClass("fa-circle").css('opacity', 1.0);
		$("#uppercase-icon").removeClass("fa-circle-xmark text-danger").addClass("fa-circle").css('opacity', 1.0);
	 	$("#number-symbol-icon").removeClass("fa-circle-xmark text-danger").addClass("fa-circle").css('opacity', 1.0);

		if (newPass.length >= 8) {
			$("#change_new_pass").addClass('is-valid');
			$("#change_new_pass").removeClass('is-invalid');
	   	 	$("#length-icon").removeClass("fa-circle").addClass("fa-solid fa-circle-check text-success").fadeIn();
	 	} else {
			$("#change_new_pass").removeClass('is-valid');
			$("#change_new_pass").addClass('is-invalid');
	    	$("#length-icon").removeClass("fa-solid fa-circle-check text-success").addClass("fa-circle-xmark text-danger").fadeIn();
	 	}

		if (hasLowercase) {
	    	$("#lowercase-icon").removeClass("fa-circle").addClass("fa-solid fa-circle-check text-success").fadeIn();
		} else {
	    	$("#lowercase-icon").removeClass("fa-solid fa-circle-check text-success").addClass("fa-circle-xmark text-danger").fadeIn();
		}

		if (hasUppercase) {
	   	 	$("#uppercase-icon").removeClass("fa-circle").addClass("fa-solid fa-circle-check text-success").fadeIn();
		} else {
	    	$("#uppercase-icon").removeClass("fa-solid fa-circle-check text-success").addClass("fa-circle-xmark text-danger").fadeIn();
		}

		if (hasNumberOrSymbol) {
	  	  	$("#number-symbol-icon").removeClass("fa-circle").addClass("fa-solid fa-circle-check text-success").fadeIn();
		} else {
	  	  	$("#number-symbol-icon").removeClass("fa-solid fa-circle-check text-success").addClass("fa-circle-xmark text-danger").fadeIn();
	 	}

		if (newPass === confirmPass) {
	     	$("#confirm-password-message").html('<i class="fa-solid fa-circle-check text-success"></i> Passwords match');
		} else {
	     	$("#confirm-password-message").html('<i class="fa-solid fa-circle-xmark text-danger"></i> Passwords do not match');
	 	}
	}

	$("#change_new_pass, #pass_verify").on('input', function() {
	 	validatePassword();
	});
});
</script>	

<script>
$(document).ready(function() {
   	$("#pass_current").focusout(function() {
        var current = $("#pass_current").val();

        $.ajax({
            type: "POST",
            url: "ajax/profile/verify_current_password.php",
            cache: false,
            data: { current: current },
            success: function(data) {
                data = JSON.parse(data);
                if (data == "fail") {
                    $("#pass_current").addClass('is-invalid');
                } else {
                    $("#pass_current").removeClass('is-invalid');
                    $("#pass_current").addClass('is-valid');
                }
            }
        });
    });

    $("#pass_verify").focusout(function() {
        if ($("#change_new_pass").val() != $("#pass_verify").val()) {
            $("#pass_verify").addClass('is-invalid');
        } else {
            $("#pass_verify").removeClass('is-invalid');
            $("#pass_verify").addClass('is-valid');
        }
    });

	$(document).ready(function(){
  	  	$("#pass_submit").click(function(event) {
			$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
			var flag = 0;
			if ($("#pass_current").val() === "" || $("#change_new_pass").val() === "" || $("#pass_verify").val() === "") {
		        swal.fire({
		            icon: 'warning',
		            title: 'Incomplete Fields',
		            text: 'Please fill out all fields before submitting.',
		            didClose: () => {
		                $("#pass_submit").html('<i class="fas fa-cloud-upload-alt"></i> Change Password');
		            }
		        });
		        event.preventDefault();
		        return;
		    }
			if ($("#pass_current").hasClass('is-invalid')) {
	            swal.fire({
	                icon: 'warning',
	                title: 'Invalid Current Password',
	                text: 'The current password you entered is incorrect.',
		            didClose: () => {
		                $("#pass_submit").html('<i class="fas fa-cloud-upload-alt"></i> Change Password');
		            }
	            });
	            event.preventDefault();
	            return;
	        }
			if ($("#change_new_pass").hasClass('is-invalid') || $("#pass_verify").hasClass('is-invalid')) {
	            swal.fire({
	                icon: 'warning',
	                title: 'Password Mismatch or Invalid',
	                text: 'Please ensure the new password meets all criteria and matches the confirmation.',
		            didClose: () => {
		                $("#pass_submit").html('<i class="fas fa-cloud-upload-alt"></i> Change Password');
		            }
	            });
	            event.preventDefault();
	            return;
	        }
			changePassword();
	    });
	});
});

function changePassword() {
    var current_pass = $("#pass_current").val();
    var new_pass = $("#change_new_pass").val();
    var verify = $("#pass_verify").val();

    $.post("ajax/profile/change_password.php", {
        current_pass: current_pass,
        new_pass: new_pass,
        verify: verify,
    }, function(data, status) {
        data = JSON.parse(data);

        if (data == "success") {
            swal.fire({
                icon: 'success',
                title: 'Password Update Successful',
                text: "Your password has been successfully changed!",
            });
			$("#pass_submit").html('<i class="fas fa-cloud-upload-alt"></i> Change Password');
            $("#pass_current").val("").removeClass("is-valid");
            $("#change_new_pass").val("").removeClass("is-valid");
            $("#pass_verify").val("").removeClass("is-valid");
			$("#profile_pass_revealer").prop('checked', false);
        } else {
            swal.fire({
                icon: 'error',
                title: "Problem Updating Password",
                text: "We encountered a problem updating your password. Please try again.",
	            didClose: () => {
	                $("#pass_submit").html('<i class="fas fa-cloud-upload-alt"></i> Change Password');
	            }
            });
        }
    });
}
</script>

<script>
$(document).ready(function() {
	    $("#clear-pass-form").click(function() {
			$(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
	        $("#pass_current").removeClass('is-invalid is-valid');
	        $("#pass_verify").removeClass('is-invalid is-valid');
	        $("#change_new_pass").removeClass('is-invalid is-valid');
			$("#length-icon").removeClass("fa-solid fa-circle-check text-success fa-circle-xmark text-danger").addClass("fa-regular fa-circle").css('opacity', 1.0);
	        $("#lowercase-icon").removeClass("fa-solid fa-circle-check text-success fa-circle-xmark text-danger").addClass("fa-regular fa-circle").css('opacity', 1.0);
	        $("#uppercase-icon").removeClass("fa-solid fa-circle-check text-success fa-circle-xmark text-danger").addClass("fa-regular fa-circle").css('opacity', 1.0);
	        $("#number-symbol-icon").removeClass("fa-solid fa-circle-check text-success fa-circle-xmark text-danger").addClass("fa-regular fa-circle").css('opacity', 1.0);
			$("#confirm-password-message").html('<i class="fa-regular fa-circle"></i> New Passwords do not match');
			$("#change_new_pass, #pass_verify, #pass_current").attr('type', 'password');
			setTimeout(function() {
	            $("#clear-pass-form").html('<i class="fas fa-eraser"></i>');
	        }, 300);
	    });
	});
</script>