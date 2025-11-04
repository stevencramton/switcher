<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

$user_id = $_SESSION['switch_id'];
$query = "SELECT COUNT(*) FROM users_passkey WHERE switch_key = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($passkey_count);
$stmt->fetch();
$stmt->close();

$query2 = "SELECT password FROM users WHERE switch_id = ?";
$stmt2 = $dbc->prepare($query2);
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$stmt2->bind_result($password);
$stmt2->fetch();
$stmt2->close();

$has_passkey = $passkey_count > 0;
$is_password_removed = is_null($password);

$data = '';

if ($is_password_removed) {
    $data .= '<h2 class="text-info">Authentication</h2>
				<p style="color:#dee2e6;">The Switchboard provides the following forms of authentication:</p>
					<ul class="list-unstyled">
						<li class="text-info">
							<i class="fa-regular fa-circle me-1" style="color:#dee2e6;"></i>
							<strong style="color:#ffe69c;">Username and Password</strong>
							<span class="text-secondary">-</span>
							<span style="color:#79dfc1;">Traditional authentication using a username and password combination.</span>
						</li>
						<li class="text-info">
							<i class="fa-solid fa-circle-check me-1"></i>
							<strong style="color:#ffe69c;">Authentication via Google Gmail Account</strong> 
							<span class="text-secondary">-</span>  
							<span style="color:#79dfc1;">Sign in with your Google Gmail account.</span>
						</li>
						<li class="text-info">
							<i class="fa-solid fa-circle-check me-1"></i>
							<strong style="color:#ffe69c;">Authentication via Microsoft Email Account</strong> 
							<span class="text-secondary">-</span>  
							<span style="color:#79dfc1;">Sign in with your Microsoft email account.</span>
						</li>
						<li class="text-info">
							<i class="fa-solid fa-circle-check me-1"></i>
							<strong style="color:#ffe69c;">Passkey Authentication</strong> 
							<span class="text-secondary">-</span>  
							<span style="color:#79dfc1;">Secure authentication using passkeys for enhanced security.</span>
						</li>
					</ul>
					<p style="color:#dee2e6;">Choose the authentication method that best suits your needs for a seamless and secure experience on Switchboard App.</p>
					<div class="accordion accordion-flush" id="accordionAuthInfoPassRemoved">
						<div class="accordion-item">
							<h2 class="accordion-header">
								<button class="accordion-button collapsed" type="button" style="color: #ffffff; 
								background-image: linear-gradient(15deg, #822ebf 0%, #4b88ff 100%); border-color: #772dba;"
								data-bs-toggle="collapse" data-bs-target="#flush-collapseAuthInfoPassRemoved" 
								aria-expanded="false" aria-controls="flush-collapseAuthInfoPassRemoved">
									<i class="fa-solid fa-circle-info me-2"></i> Updated Information
								</button>
							</h2>
							<div id="flush-collapseAuthInfoPassRemoved" class="accordion-collapse collapse" data-bs-parent="#accordionAuthInfoPassRemoved">
								<div class="accordion-body">
									<p>
								   	Your password has been removed from the system. Please contact the Switchboard admin for authentication related assistance.
									</p>
								</div>
							</div>
						</div>
					</div>';
} 

elseif ($has_passkey) {
    $data .= '
	    <h2 class="text-info">Authentication</h2>
	    <p style="color:#dee2e6;">The Switchboard provides the following forms of authentication:</p>
	    <ul class="list-unstyled">
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Username and Password</strong>
				<span class="text-secondary">-</span>
				<span style="color:#79dfc1;">Traditional authentication using a username and password combination.</span>
			</li>
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Authentication via Google Gmail Account</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Sign in with your Google Gmail account.</span>
			</li>
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Authentication via Microsoft Email Account</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Sign in with your Microsoft email account.</span>
			</li>
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Passkey Authentication</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Secure authentication using passkeys for enhanced security.</span>
			</li>
	    </ul>
	    <p style="color:#dee2e6;">Choose the authentication method that best suits your needs for a seamless and secure experience on Switchboard App.</p>
		
		<div class="accordion accordion-flush" id="accordionOptPassRemove">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" 
                        style="color: #fff; background-image: linear-gradient(15deg, #dc3545 0%, #ff9800 100%); 
                        border-color: #ff9800;" data-bs-toggle="collapse" data-bs-target="#flush-collapseOptPassRemove" 
                        aria-expanded="false" aria-controls="flush-collapseOptPassRemove">
                        <i class="fa-solid fa-circle-radiation me-2"></i> Remove Account Password
                        </button>
                    </h2>
                    <div id="flush-collapseOptPassRemove" class="accordion-collapse collapse" data-bs-parent="#accordionOptPassRemove">
                        <div class="accordion-body">
                            <div class="list-group list-group-radio d-grid gap-2 border-0 mb-0">
                                <div class="row gx-3">
                                    <div class="col-md-6 mb-3 mb-md-0 h-100">
                                        <div class="position-relative mb-3 d-flex flex-column h-100">
                                            <input class="form-check-input position-absolute top-50 end-0 me-3 fs-5" 
                                                type="radio" name="listGroupRadioGridPass" id="listGroupRadioGridLeft" value="" checked>
                                            <label class="list-group-item py-3 pe-5 flex-grow-1" for="listGroupRadioGridLeft">
                                                <h4 class="fw-semibold mb-3">Information</h4>
                                                <h6 class="fw-semibold">Where Are Passkeys Stored?</h6>
                                                <p class="d-block small opacity-75">Passkeys are securely stored on your device 
                                                    and are never shared with the Switchboard server.</p>
                                                <h6 class="fw-semibold">Why Have Multiple Passkeys?</h6>
                                                <p class="d-block small opacity-75">You may want more than one passkey for different devices (e.g., your phone, laptop, or tablet).</p>
                                            </label>
                                            
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-dark w-100" onclick="closeAccordion();">
                                                    <i class="fa-solid fa-backward"></i> Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3 mb-md-0 h-100">
                                        <div class="position-relative mb-3 d-flex flex-column h-100">
                                            <input class="form-check-input position-absolute top-50 end-0 me-3 fs-5" 
                                                type="radio" name="listGroupRadioGridPass" id="listGroupRadioGridRight" value="">
                                            <label class="list-group-item py-3 pe-5 flex-grow-1" for="listGroupRadioGridRight">
                                                <h4 class="fw-semibold mb-3">Proceed</h4>
                                                <h6 class="fw-semibold">Lets give it a go!</h6>
                                                <p class="d-block small opacity-75">In case your passkey stops working or if you switch to a new device, contact the Switchboard admin.</p>
                                                <h6 class="fw-semibold">Getting Help</h6>
                                                <p class="d-block small opacity-75">If you face any issues with your passkey please contact the Switchboard admin for further guidance.</p>
                                            </label>
                                            
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-atomic w-100" id="removeAccountPasswordBtn" onclick="removeUserPassword();" disabled>
                                                    <i class="fa-solid fa-circle-check"></i> Remove Account Password
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> 
				</div>
              </div>';
} 

else {
    $data .= '<h2 class="text-info">Authentication</h2>
	    <p style="color:#dee2e6;">The Switchboard provides the following forms of authentication:</p>
	    <ul class="list-unstyled">
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Username and Password</strong>
				<span class="text-secondary">-</span>
				<span style="color:#79dfc1;">Traditional authentication using a username and password combination.</span>
			</li>
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Authentication via Google Gmail Account</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Sign in with your Google Gmail account.</span>
			</li>
	        <li class="text-info">
				<i class="fa-solid fa-circle-check me-1"></i>
				<strong style="color:#ffe69c;">Authentication via Microsoft Email Account</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Sign in with your Microsoft email account.</span>
			</li>
	        <li class="text-info">
				<i class="fa-regular fa-circle me-1" style="color:#dee2e6;"></i>
				<strong style="color:#ffe69c;">Passkey Authentication</strong> 
				<span class="text-secondary">-</span>  
				<span style="color:#79dfc1;">Secure authentication using passkeys for enhanced security.</span>
			</li>
	    </ul>
	    <p style="color:#dee2e6;">Choose the authentication method that best suits your needs for a seamless and secure experience on Switchboard App.</p>
		
		<div class="accordion accordion-flush" id="accordionAuthInfo">
               <div class="accordion-item">
                 <h2 class="accordion-header">
                   <button class="accordion-button collapsed" type="button" style="color: #ffffff; 
                        background-image: linear-gradient(15deg, #114c63 0%, #65c9c8 100%); border-color: #0a5977;" 
                        data-bs-toggle="collapse" data-bs-target="#flush-collapseAuthInfo" aria-expanded="false" aria-controls="flush-collapseAuthInfo">
                     <i class="fa-solid fa-circle-info me-2"></i> Important Information
                   </button>
                 </h2>
                 <div id="flush-collapseAuthInfo" class="accordion-collapse collapse" data-bs-parent="#accordionAuthInfo">
                   <div class="accordion-body">
                        <p>
                            Passkey authentication offers enhanced security and eliminates the need for remembering 
                            passwords, giving you a safer and more streamlined way to access your account.
                        </p>
                        <p class="mb-0">
                            Once you have successfully set up and established passkey authentication, you will 
                            have the option to remove your password from the system. This option ensures a more 
                            secure and convenient login experience without needing a traditional password.
                        </p>
                   </div>
                 </div>
               </div>
            </div>';
}

echo $data;

mysqli_close($dbc);
?>

<script>
function closeAccordion() {
	var accordionCollapse = new bootstrap.Collapse(document.getElementById("flush-collapseOptPassRemove"), {
		toggle: false
	});
	accordionCollapse.hide();
}
</script>
	
<script>
$(document).ready(function() {
	$('#removeAccountPasswordBtn').prop('disabled', true);
	$('#listGroupRadioGridLeft').on('change', function() {
		if ($(this).prop('checked')) {
			$('#removeAccountPasswordBtn').prop('disabled', true);
		}
	});
	$('#listGroupRadioGridRight').on('change', function() {
		if ($(this).prop('checked')) {
			$('#removeAccountPasswordBtn').prop('disabled', false);
		}
	});
});
</script>