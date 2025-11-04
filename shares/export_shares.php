<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('admin_developer')){
    header("Location:index.php?msg1");
    exit();
}

if (isset($_SESSION['id'])){
    $type = mysqli_real_escape_string($dbc, strip_tags($_POST['type']));

    function cleanData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
    }

    $colnames = [
        'share_id','share_drive_name','share_ad_name','share_mapping','share_server','share_notes','share_type'
    ];

    function map_colnames($input) {
        global $colnames;
        return isset($colnames[$input]) ? $colnames[$input] : $input;
    }

    if($type == "blank"){
        $filename = "share_drives_blank.csv";
    } else {
        $filename = "shares_drives.csv";
    }

    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Type: text/csv");

    $out = fopen("php://output", 'w');

    $flag = false;

    if($type != "blank"){
        $query = "SELECT share_id,share_drive_name,share_ad_name,share_mapping,share_server,share_notes,share_type FROM shares";
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while($row = mysqli_fetch_assoc($result)){
            if(!$flag) {
                $firstline = array_map(__NAMESPACE__ . '\map_colnames', array_keys($row));
                fputcsv($out, $firstline, ',', '"', '\\');
                $flag = true;
            }
            array_walk($row, __NAMESPACE__ . '\cleanData');
            fputcsv($out, array_values($row), ',', '"', '\\');
        }

    } else if ($type == "blank"){
        $last_id_query = "SELECT share_id FROM shares ORDER BY share_id DESC LIMIT 1";
        $last_id_stmt = mysqli_prepare($dbc, $last_id_query);
        mysqli_stmt_execute($last_id_stmt);
        $last_id_result = mysqli_stmt_get_result($last_id_stmt);
        $last_id = null;

        if($last_id_row = mysqli_fetch_array($last_id_result)){
            $last_id = $last_id_row['share_id'];
        }

        mysqli_stmt_close($last_id_stmt);
        $plus_one_id = $last_id ? $last_id + 1 : 1;
        $plus_one_id_array = array($plus_one_id);
        $firstline = array_map(__NAMESPACE__ . '\map_colnames', array_keys($colnames));
        fputcsv($out, $firstline, ',', '"', '\\');
        fputcsv($out, array_values($plus_one_id_array), ',', '"', '\\');
    }
    fclose($out);
}
exit;