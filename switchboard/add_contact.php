<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_contacts')) {
    header("Location:../../index.php?msg1");
    exit();
}

if(isset($_POST['type'])){
	$type = $_POST['type'];
	$category = $_POST['category'];
	$type = mysqli_real_escape_string($dbc, strip_tags($type));
	$category = mysqli_real_escape_string($dbc, strip_tags($category));
	$switchboard_cat_name = '';
	
	if ($category != "none") {
		$switch_cat_query = "SELECT switchboard_cat_name FROM switchboard_categories WHERE switchboard_cat_id = ?";
		$stmt = mysqli_prepare($dbc, $switch_cat_query);
		mysqli_stmt_bind_param($stmt, "i", $category);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $switchboard_cat_name);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
	}

	if ($category == "none") {
		$contact_cat = 0;
	} else {
		$contact_cat = $category;
	}

	if ($type == "person") {
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		$department = $_POST['department'];
		$extension = $_POST['extension'];
		$cell = $_POST['cell'];
		$email = $_POST['email'];
		$note = $_POST['note'];
		$area_agency = $_POST['area_agency'];
		$area_location = $_POST['area_location'];

		$query = "INSERT INTO switchboard_contacts (first_name, last_name, department, extension, cell, area_location, area_agency, email, switchboard_note, switchboard_cat_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
		$stmt = mysqli_prepare($dbc, $query);
		mysqli_stmt_bind_param($stmt, "sssssssssi", $first_name, $last_name, $department, $extension, $cell, $area_location, $area_agency, $email, $note, $contact_cat);

		if (mysqli_stmt_execute($stmt)) {
			$audit_user = $_SESSION['user'];
			$audit_first_name = $_SESSION['first_name'];
			$audit_last_name = $_SESSION['last_name'];
			$audit_profile_pic = $_SESSION['profile_pic'];
			$switch_id = $_SESSION['switch_id'];
			$audit_date = date('m-d-Y g:i A');
			$audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-solid fa-circle-check"></i> Created Switchboard Contact </span>';
			$audit_action = 'Created Switchboard Contact (Person)';
			$audit_ip = $_SERVER['REMOTE_ADDR'];
			$audit_source = $_SERVER['REQUEST_URI'];
			$audit_domain = $_SERVER['SERVER_NAME'];
			$audit_detailed_action = '<span class="dark-gray fw-bold">Category</span>: ' . htmlspecialchars($switchboard_cat_name) . '<br>' 
			. '<span class="dark-gray fw-bold">Contact</span>: ' . htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name);
			
			$first_name_short = (strlen($first_name) > 30) ? substr($first_name, 0, 30) . '...' : $first_name;

			$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt_audit = mysqli_prepare($dbc, $audit_query);
			mysqli_stmt_bind_param($stmt_audit, "sssssssssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $first_name_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
			mysqli_stmt_execute($stmt_audit);
			mysqli_stmt_close($stmt_audit);
		} else {
			echo "Error description.";
		}

		mysqli_stmt_close($stmt);

	} else if ($type == "department") {
    
		$department = $_POST['department'];
		$extension = $_POST['extension'];
		$cell = $_POST['cell'];
		$area_location = $_POST['area_location'];
		$area_agency = $_POST['area_agency'];
		$email = $_POST['email'];
		$note = $_POST['note'];

		$query = "INSERT INTO switchboard_contacts (department, extension, cell, area_location, area_agency, email, switchboard_note, switchboard_cat_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
		$stmt = mysqli_prepare($dbc, $query);
		mysqli_stmt_bind_param($stmt, "sssssssi", $department, $extension, $cell, $area_location, $area_agency, $email, $note, $contact_cat);

		if (mysqli_stmt_execute($stmt)) {
			// Audit Trail
			$audit_user = $_SESSION['user'];
			$audit_first_name = $_SESSION['first_name'];
			$audit_last_name = $_SESSION['last_name'];
			$audit_profile_pic = $_SESSION['profile_pic'];
			$switch_id = $_SESSION['switch_id'];
			$audit_date = date('m-d-Y g:i A');
			$audit_action_tag = '<span class="badge bg-audit-primary-ghost shadow-sm"><i class="fa-solid fa-circle-check"></i> Created Switchboard Contact </span>';
			$audit_action = 'Created Switchboard Contact (Department)';
			$audit_ip = $_SERVER['REMOTE_ADDR'];
			$audit_source = $_SERVER['REQUEST_URI'];
			$audit_domain = $_SERVER['SERVER_NAME'];
			$audit_detailed_action = '<span class="dark-gray fw-bold">Category</span>: ' . htmlspecialchars($switchboard_cat_name) . '<br>' 
			. '<span class="dark-gray fw-bold">Contact</span>: ' . htmlspecialchars($department);
			
			$department_short = (strlen($department) > 30) ? substr($department, 0, 30) . '...' : $department;

			$audit_query = "INSERT INTO audit_trail (audit_profile_pic, audit_first_name, audit_last_name, audit_user, switch_id, audit_date, audit_action_tag, audit_action, audit_summary, audit_detailed_action, audit_ip, audit_source, audit_domain) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt_audit = mysqli_prepare($dbc, $audit_query);
			mysqli_stmt_bind_param($stmt_audit, "sssssssssssss", $audit_profile_pic, $audit_first_name, $audit_last_name, $audit_user, $switch_id, $audit_date, $audit_action_tag, $audit_action, $department_short, $audit_detailed_action, $audit_ip, $audit_source, $audit_domain);
			mysqli_stmt_execute($stmt_audit);
			mysqli_stmt_close($stmt_audit);
		} else {
			echo "Error description.";
		}

		mysqli_stmt_close($stmt);
	}

	mysqli_close($dbc);
}