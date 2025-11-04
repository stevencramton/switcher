<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('verse_view')) {
    header("Location: ../../index.php?msg1");
    exit();
}

$data = '<div class="form-floating">
    <select class="form-select" name="edit_verse_note_group" id="edit_verse_note_group" aria-label="Floating label select edit verse">';

$query = "SELECT * FROM verse_groups ORDER BY verse_group_display_order ASC";

$stmt = mysqli_prepare($dbc, $query);
if ($stmt === false) {
    exit("Failed to prepare statement.");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result === false) {
    exit("Failed to get result.");
}

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
    	$verse_group_id = htmlspecialchars($row['verse_group_id']);
        $verse_group_name = htmlspecialchars($row['verse_group_name']);
		$data .= '<option value="' . $verse_group_id . '">' . $verse_group_name . '</option>';
    }
    $data .= '</select><label for="edit_verse_note_group">Verse Group</label></div>';
} else {
  	$data .= '<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2)" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
</svg>
<p class="one success">Records empty!</p>
<p class="complete">Verse Groups not found!</p>';
}

echo $data;

mysqli_stmt_close($stmt);
mysqli_close($dbc);