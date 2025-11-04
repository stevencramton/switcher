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

if (isset($_POST['selected_values'])) {
	$ids = $_POST['selected_values'];
    $type = $_POST['type'] ?? '';
    $category = strip_tags($_POST['category']);
    $multi = $_POST['multi'];
    $note = strip_tags($_POST['note'] ?? '');
	$ids = array_map('intval', explode(',', $ids));

    if ($multi == "false") {

        if ($type == "person") {
			$first_name = strip_tags($_POST['first_name']);
            $last_name = strip_tags($_POST['last_name']);
            $department = strip_tags($_POST['department']);
            $extension = strip_tags($_POST['extension']);
            $email = strip_tags($_POST['email']);
            $area_location = strip_tags($_POST['area_location']);
            $area_agency = strip_tags($_POST['area_agency']);

         	if (isset($_POST['cell'])) {
                $cell = strip_tags($_POST['cell']);
                $query = "UPDATE switchboard_contacts 
                          SET first_name = ?, last_name = ?, department = ?, extension = ?, cell = ?, area_location = ?, area_agency = ?, email = ?, switchboard_note = ?, switchboard_cat_id = ? 
                          WHERE switchboard_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
                $types = 'sssssssssi' . str_repeat('i', count($ids));
                $params = array_merge([$types], [$first_name, $last_name, $department, $extension, $cell, $area_location, $area_agency, $email, $note, $category], $ids);
            } else {
             	$query = "UPDATE switchboard_contacts 
                          SET first_name = ?, last_name = ?, department = ?, extension = ?, area_location = ?, area_agency = ?, email = ?, switchboard_note = ?, switchboard_cat_id = ? 
                          WHERE switchboard_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
                $types = 'ssssssssi' . str_repeat('i', count($ids));
                $params = array_merge([$types], [$first_name, $last_name, $department, $extension, $area_location, $area_agency, $email, $note, $category], $ids);
            }

            $stmt = mysqli_prepare($dbc, $query);
            if ($stmt === false) {
                die("Query error.");
            }
            mysqli_stmt_bind_param($stmt, ...$params);

        } else if ($type == "department") {

            $department = strip_tags($_POST['department']);
            $extension = strip_tags($_POST['extension']);
            $area_location = strip_tags($_POST['area_location']);
            $area_agency = strip_tags($_POST['area_agency']);
            $email = strip_tags($_POST['email']);

          	if (isset($_POST['cell'])) {
                $cell = strip_tags($_POST['cell']);
                $query = "UPDATE switchboard_contacts 
                          SET department = ?, extension = ?, cell = ?, area_location = ?, area_agency = ?, email = ?, switchboard_note = ?, switchboard_cat_id = ? 
                          WHERE switchboard_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
                $types = 'sssssssi' . str_repeat('i', count($ids));
                $params = array_merge([$types], [$department, $extension, $cell, $area_location, $area_agency, $email, $note, $category], $ids);
            } else {
              	$query = "UPDATE switchboard_contacts 
                          SET department = ?, extension = ?, area_location = ?, area_agency = ?, email = ?, switchboard_note = ?, switchboard_cat_id = ? 
                          WHERE switchboard_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
                $types = 'ssssssi' . str_repeat('i', count($ids));
                $params = array_merge([$types], [$department, $extension, $area_location, $area_agency, $email, $note, $category], $ids);
            }

            $stmt = mysqli_prepare($dbc, $query);
            if ($stmt === false) {
                die("Query error.");
            }
            mysqli_stmt_bind_param($stmt, ...$params);
        }

    } else if ($multi == "true") {

        $department = strip_tags($_POST['department']);
        $area_location = strip_tags($_POST['area_location']);
        $area_agency = strip_tags($_POST['area_agency']);

        $query = "UPDATE switchboard_contacts 
                  SET switchboard_cat_id = ?, department = ?, area_location = ?, area_agency = ? 
                  WHERE switchboard_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";

        $stmt = mysqli_prepare($dbc, $query);
        if ($stmt === false) {
            die("Query error.");
        }
        $types = 'issi' . str_repeat('i', count($ids));
        $params = array_merge([$types], [$category, $department, $area_location, $area_agency], $ids);
        mysqli_stmt_bind_param($stmt, ...$params);
    }

    if (!$stmt) {
        echo("Error description.");
    } else {
        mysqli_stmt_execute($stmt) or die("database error.");
        mysqli_stmt_close($stmt);
    }
}
mysqli_close($dbc);