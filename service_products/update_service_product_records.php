<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('service_products_manage')) {
    header("Location:../../index.php?msg1");
	exit();
}

if (isset($_POST['product_id'])) {
	$product_id = mysqli_real_escape_string($dbc, strip_tags($_POST['product_id']));
	$old_product_group_query = "SELECT * FROM service_products WHERE product_id = '$product_id'";
	$old_product_group_result = mysqli_query($dbc, $old_product_group_query);
	$row = mysqli_fetch_array($old_product_group_result);

		$old_product_service_group = mysqli_real_escape_string($dbc, strip_tags($row['product_service_group']));
		$old_product_service_group_name = mysqli_real_escape_string($dbc, strip_tags($row['product_name']));
		$old_product_service_group_info = mysqli_real_escape_string($dbc, strip_tags($row['product_info']));
		$old_product_private = mysqli_real_escape_string($dbc, strip_tags($row['product_private']));
		
		if ($old_product_private == 1) {
			$old_product_private = 'Private';
		} else if ($old_product_private == 0) {
			$old_product_private = 'Viewable';
		}
		
		$old_product_tags = mysqli_real_escape_string($dbc, strip_tags($row['product_tags']));
		$old_product_affiliation_usnh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_usnh']));
		$old_product_affiliation_unh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh']));
		$old_product_affiliation_manch = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_manch']));
		$old_product_affiliation_law = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_law']));
		$old_product_affiliation_psu = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_psu']));
		$old_product_affiliation_ksc = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_ksc']));
		
		if ($old_product_affiliation_usnh == 1) {
			$old_product_affiliation_usnh = 'USNH';
		} else if ($old_product_affiliation_usnh == 0) {
			$old_product_affiliation_usnh = '';
		}
		
		if ($old_product_affiliation_unh == 1) {
			$old_product_affiliation_unh = 'UNH';
		} else if ($old_product_affiliation_unh == 0) {
			$old_product_affiliation_unh = '';
		}
		
		if ($old_product_affiliation_manch == 1) {
			$old_product_affiliation_manch = 'UNH Manch';
		} else if ($old_product_affiliation_manch == 0) {
			$old_product_affiliation_manch = '';
		}
		
		if ($old_product_affiliation_law == 1) {
			$old_product_affiliation_law = 'UNH Law';
		} else if ($old_product_affiliation_law == 0) {
			$old_product_affiliation_law = '';
		}
		
		if ($old_product_affiliation_psu == 1) {
			$old_product_affiliation_psu = 'PSU';
		} else if ($old_product_affiliation_psu == 0) {
			$old_product_affiliation_psu = '';
		}
		
		if ($old_product_affiliation_ksc == 1) {
			$old_product_affiliation_ksc = 'KSC';
		} else if ($old_product_affiliation_ksc == 0) {
			$old_product_affiliation_ksc = '';
		}
		
		$affliation_old = $old_product_affiliation_usnh . ' ' . $old_product_affiliation_unh . ' ' . $old_product_affiliation_manch . ' ' . $old_product_affiliation_law . ' ' . $old_product_affiliation_psu . ' ' . $old_product_affiliation_ksc;
		
		$old_service_group_query = "SELECT * FROM service_groups WHERE group_id = '$old_product_service_group'";
		$old_service_group_result = mysqli_query($dbc, $old_service_group_query);
		$row = mysqli_fetch_array($old_service_group_result);

		$old_service_group_name = mysqli_real_escape_string($dbc, strip_tags($row['group_name']));
	
	$product_name = mysqli_real_escape_string($dbc, strip_tags($_POST['product_name']));
	$product_info = mysqli_real_escape_string($dbc, $_POST['product_info']);
	$product_private = mysqli_real_escape_string($dbc, strip_tags($_POST['product_private']));
	$product_steps = mysqli_real_escape_string($dbc, $_POST['product_steps']);
	
	$affiliation_unh = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_unh']));
	$affiliation_psu = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_psu']));
	$affiliation_ksc = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_ksc']));
	$affiliation_usnh = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_usnh']));
	$affiliation_unh_manch = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_unh_manch']));
	$affiliation_unh_law = mysqli_real_escape_string($dbc, strip_tags($_POST['affiliation_unh_law']));
	$product_tags = mysqli_real_escape_string($dbc, strip_tags($_POST['product_tags']));
	$product_edited_by = mysqli_real_escape_string($dbc, strip_tags($_SESSION['display_name']));
 	$product_date_edited = date_default_timezone_set("America/New_York");
  	$product_date_edited = date('m-d-Y g:i A');
	$product_service_group = mysqli_real_escape_string($dbc, strip_tags($_POST['product_service_group']));
	
	$query = "UPDATE service_products SET product_name = '$product_name', product_info = '$product_info', product_private = '$product_private', product_steps = '$product_steps', affiliation_unh = '$affiliation_unh', affiliation_psu = '$affiliation_psu', affiliation_ksc = '$affiliation_ksc', affiliation_usnh = '$affiliation_usnh', affiliation_unh_manch = '$affiliation_unh_manch', affiliation_unh_law = '$affiliation_unh_law', product_tags = '$product_tags', product_edited_by = '$product_edited_by', product_date_edited = '$product_date_edited', product_service_group = '$product_service_group' WHERE product_id = '$product_id'";
    
	if (!$result = mysqli_query($dbc, $query)) {
   	 	$response = "failure";
        exit();
	} else {
		$response = "success";
	}

	$new_product_group_query = "SELECT * FROM service_products WHERE product_id = '$product_id'";
	$new_product_group_result = mysqli_query($dbc, $new_product_group_query);
	$row = mysqli_fetch_array($new_product_group_result);
	$new_product_service_group = mysqli_real_escape_string($dbc, strip_tags($row['product_service_group']));
	$new_product_service_group_info = mysqli_real_escape_string($dbc, strip_tags($row['product_info']));
	$new_product_private = mysqli_real_escape_string($dbc, strip_tags($row['product_private']));
		
	if ($new_product_private == 1) {
		$new_product_private = 'Private';
	} else if ($new_product_private == 0) {
		$new_product_private = 'Viewable';
	}

	$new_product_tags = mysqli_real_escape_string($dbc, strip_tags($row['product_tags']));
	$new_product_affiliation_usnh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_usnh']));
	$new_product_affiliation_unh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh']));
	$new_product_affiliation_manch = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_manch']));
	$new_product_affiliation_law = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_law']));
	$new_product_affiliation_psu = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_psu']));
	$new_product_affiliation_ksc = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_ksc']));
		
	if ($new_product_affiliation_usnh == 1) {
		$new_product_affiliation_usnh = 'USNH';
	} else if ($new_product_affiliation_usnh == 0) {
		$new_product_affiliation_usnh = '';
	}
	if ($new_product_affiliation_unh == 1) {
		$new_product_affiliation_unh = 'UNH';
	} else if ($new_product_affiliation_unh == 0) {
		$new_product_affiliation_unh = '';
	}
	if ($new_product_affiliation_manch == 1) {
		$new_product_affiliation_manch = 'UNH Manch';
	} else if ($new_product_affiliation_manch == 0) {
		$new_product_affiliation_manch = '';
	}
	if ($new_product_affiliation_law == 1) {
		$new_product_affiliation_law = 'UNH Law';
	} else if ($new_product_affiliation_law == 0) {
		$new_product_affiliation_law = '';
	}
	if ($new_product_affiliation_psu == 1) {
		$new_product_affiliation_psu = 'PSU';
	} else if ($new_product_affiliation_psu == 0) {
		$new_product_affiliation_psu = '';
	}
	if ($new_product_affiliation_ksc == 1) {
		$new_product_affiliation_ksc = 'KSC';
	} else if ($new_product_affiliation_ksc == 0) {
		$new_product_affiliation_ksc = '';
	}
		
	$affliation_new = $new_product_affiliation_usnh . ' ' . $new_product_affiliation_unh . ' ' . $new_product_affiliation_manch . ' ' . $new_product_affiliation_law . ' ' . $new_product_affiliation_psu . ' ' . $new_product_affiliation_ksc;
	$new_service_group_query = "SELECT * FROM service_groups WHERE group_id = '$new_product_service_group'";
	$new_service_group_result = mysqli_query($dbc, $new_service_group_query);
	$row = mysqli_fetch_array($new_service_group_result);

	$new_service_group_name = mysqli_real_escape_string($dbc, strip_tags($row['group_name']));

	echo json_encode($response);

	$audit_user = mysqli_real_escape_string($dbc, strip_tags($_SESSION['user']));
	$audit_first_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['first_name']));
	$audit_last_name = mysqli_real_escape_string($dbc, strip_tags($_SESSION['last_name']));
	$audit_profile_pic = mysqli_real_escape_string($dbc, strip_tags($_SESSION['profile_pic']));
	$switch_id = mysqli_real_escape_string($dbc, strip_tags($_SESSION['switch_id']));
	$audit_date = date_default_timezone_set("America/New_York");
	$audit_date = date('m-d-Y g:i A');
	$audit_action_tag = '<span class="badge bg-audit-edit shadow-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Updated Service Group Product</span>';
	$audit_action = 'Updated Service Group Product';
	$audit_ip = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REMOTE_ADDR']));
	$audit_source = mysqli_real_escape_string($dbc, strip_tags($_SERVER['REQUEST_URI']));
	$audit_domain = mysqli_real_escape_string($dbc, strip_tags($_SERVER['SERVER_NAME']));
	
	if (strcmp($old_product_service_group_name, $product_name) == 0) {
		$product_name_change = '';
	} else {
		$product_name_change = '<span class="dark-gray fw-bold">Service Group Product Name</span>:' . ' ' . $old_product_service_group_name . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $product_name . '<br>';
	}
	
	if (strcmp($old_product_service_group_info, $new_product_service_group_info) == 0) {
		$product_info_change = '';
	} else {
		$product_info_change = '<span class="dark-gray fw-bold">Service Group Product Info</span>:' . ' ' . $old_product_service_group_info . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_product_service_group_info . '<br>';
	}
	
	if (strcmp($old_product_private, $new_product_private) == 0) {
		$product_private_change = '';
	} else {
		$product_private_change = '<span class="dark-gray fw-bold">Service Group Product Info Status</span>:' . ' ' . $old_product_private . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_product_private . '<br>';
	}
	
	if (strcmp($affliation_old, $affliation_new) == 0) {
		$product_affiliation_change = '';
	} else {
		$product_affiliation_change = '<span class="dark-gray fw-bold">Service Group Product Affiliation</span>:' . ' ' . $affliation_old . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $affliation_new . '<br>';
	}
	
	if (strcmp($old_product_tags, $new_product_tags) == 0) {
		$product_tags_change = '';
	} else {
		$product_tags_change = '<span class="dark-gray fw-bold">Service Group Product Tags</span>:' . ' ' . $old_product_tags . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_product_tags . '<br>';
	}
	
	if (strcmp($old_service_group_name, $new_service_group_name) == 0) {
		$product_assignment_change = '';
	} else {
		$product_assignment_change = '<span class="dark-gray fw-bold">Service Group Product Assignment</span></b>:' . ' ' . $old_service_group_name . ' ' . '<span class="dark-gray fw-bold">to</span>:' . ' ' . $new_service_group_name . '<br>';
	}
	
	$audit_detailed_action = $product_name_change . $product_info_change . $product_private_change . $product_affiliation_change . $product_tags_change . $product_assignment_change;
	
	if (empty($audit_detailed_action)) {
	  $audit_detailed_action = 'No modifications were made.';
	} else {
		$audit_detailed_action = preg_replace('/(<br>)+$/', '', $audit_detailed_action);
	}
	
	$product_name = (strlen($product_name) > 30) ? substr($product_name, 0, 30).'...' : $product_name;

	$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES ('$audit_profile_pic', '$audit_first_name', '$audit_last_name', '$audit_user', '$switch_id', '$audit_date', '$audit_action_tag', '$audit_action', '$product_name', '$audit_detailed_action', '$audit_ip', '$audit_source', '$audit_domain')";
	$audit_result = mysqli_query($dbc, $audit_query);
	confirmQuery($audit_result);

}
mysqli_close($dbc);