<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('admin_developer')){
    header("Location:index.php?msg1");
    exit();
}

$qstring = '';

if (isset($_POST['importConfirm'])) {
    
	$csvMimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );
    
    if (!empty($_FILES['import_file']['name']) && in_array($_FILES['import_file']['type'], $csvMimes)) {
        
        if (is_uploaded_file($_FILES['import_file']['tmp_name'])) {
			$csvFile = fopen($_FILES['import_file']['tmp_name'], 'r');

            if (!$csvFile) {
                $qstring = '?status=err';
            } else {
            	fgetcsv($csvFile);

               	while (($line = fgetcsv($csvFile)) !== FALSE) {

                    date_default_timezone_set("America/New_York");
                    $share_updated_date = date('Y-m-d');
                    $share_updated_time = date('H:i:s');
                    $share_updated_by = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
                    $share_mapping = "//" . $line[4] . ".plymouth.edu/" . $line[1];
                    $share_notes = mysqli_real_escape_string($dbc, $line[5]);

                  	$prevQuery = "SELECT share_drive_name FROM shares WHERE share_drive_name = ?";
                    $stmt = mysqli_prepare($dbc, $prevQuery);
                    mysqli_stmt_bind_param($stmt, 's', $line[1]);
                    mysqli_stmt_execute($stmt);
                    $prevResult = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($prevResult) > 0) {
						$updateQuery = "UPDATE shares SET share_id = ?, share_drive_name = ?, share_ad_name = ?, share_mapping = ?, share_server = ?, share_notes = ?, share_type = ?, share_updated_date = ?, share_updated_time = ?, share_updated_by = ? WHERE share_drive_name = ?";
                        $updateStmt = mysqli_prepare($dbc, $updateQuery);
                        mysqli_stmt_bind_param($updateStmt, 'issssssssss', $line[0], $line[1], $line[2], $share_mapping, $line[4], $line[5], $line[6], $share_updated_date, $share_updated_time, $share_updated_by, $line[1]);
                        mysqli_stmt_execute($updateStmt);

                        if (mysqli_stmt_affected_rows($updateStmt) == 0) {
                      	  	$qstring = '?status=err_update';
                        }

                        mysqli_stmt_close($updateStmt);
                    } else {
                  	  	$insertQuery = "INSERT INTO shares (share_id, share_drive_name, share_ad_name, share_mapping, share_server, share_notes, share_type, share_updated_date, share_updated_time, share_updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = mysqli_prepare($dbc, $insertQuery);
                        mysqli_stmt_bind_param($insertStmt, 'isssssssss', $line[0], $line[1], $line[2], $share_mapping, $line[4], $share_notes, $line[6], $share_updated_date, $share_updated_time, $share_updated_by);
                        mysqli_stmt_execute($insertStmt);

                        if (mysqli_stmt_affected_rows($insertStmt) == 0) {
                       	 	$qstring = '?status=err_insert';
                        }
						mysqli_stmt_close($insertStmt);
                    }
					mysqli_stmt_close($stmt);
                }

            	fclose($csvFile);
				$qstring = '?status=succ';
            }
        } else {
            $qstring = '?status=err';
        }
    } else {
        $qstring = '?status=invalid_file';
    }
}
header("Location: ../../shares.php" . $qstring);
?>