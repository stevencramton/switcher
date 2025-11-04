<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('my_links_view')) {
    header("Location:../../index.php?msg1");
    exit();
}

$required_fields = ['id', 'my_link_new_tab', 'my_link_favorite', 'my_link_name', 'my_link_description', 'my_link_url', 'my_link_image', 'my_link_protocol'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        http_response_code(400);
        die("Missing parameter: $field");
    }
}

$id = mysqli_real_escape_string($dbc, strip_tags($_POST['id']));
$my_link_new_tab = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_new_tab']));
$my_link_favorite = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_favorite']));
$my_link_name = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_name']));
$my_link_description = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_description']));
$my_link_url = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_url']));
$my_link_image = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_image']));
$my_link_protocol = mysqli_real_escape_string($dbc, strip_tags($_POST['my_link_protocol']));

$query = "UPDATE my_links SET 
            my_link_name = ?, 
            my_link_description = ?, 
            my_link_url = ?, 
            my_link_image = ?, 
            my_link_favorite = ?, 
            my_link_new_tab = ?, 
            my_link_protocol = ? 
          WHERE my_link_id = ?";

$stmt = mysqli_prepare($dbc, $query);
if ($stmt === false) {
    die('Error.');
}
mysqli_stmt_bind_param($stmt, "sssssssi", 
    $my_link_name, 
    $my_link_description, 
    $my_link_url, 
    $my_link_image, 
    $my_link_favorite, 
    $my_link_new_tab, 
    $my_link_protocol, 
    $id);

$execute = mysqli_stmt_execute($stmt);
if ($execute === false) {
    die('Error.');
}
mysqli_stmt_close($stmt);
mysqli_close($dbc);
?>