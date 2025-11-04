<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('poll_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

$data = '<div class="row mb-2">
            <div class="col-md-8">
                <select class="selectpicker" id="poll_user_assignment" multiple name="poll_user_assignment[]" placeholder="User Select" title="Please select at least one user" data-selected-text-format="count" data-actions-box="true" multiple>';

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
    
    $inquiry_id = htmlspecialchars(strip_tags($_POST['inquiry_id']), ENT_QUOTES, 'UTF-8');
    $user = htmlspecialchars(strip_tags($_SESSION['user']), ENT_QUOTES, 'UTF-8');

    if (checkRole('poll_admin')) {
        $query = "SELECT * FROM users WHERE account_delete != '1' ORDER BY first_name ASC";
        
        if ($stmt = mysqli_prepare($dbc, $query)) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_array($result)) {
                
                $user = htmlspecialchars(strip_tags($row['user']), ENT_QUOTES, 'UTF-8');
                $first_name = htmlspecialchars(strip_tags($row['first_name']), ENT_QUOTES, 'UTF-8');
                $last_name = htmlspecialchars(strip_tags($row['last_name']), ENT_QUOTES, 'UTF-8');
                
                $data .= '<option value="' . $user . '">' . $first_name . ' ' . $last_name . '</option>';
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    $data .= '</select>
                <input type="hidden" id="assign_poll_enrollment_hidden_id" value="' . $inquiry_id . '">
            </div>';
    
    $data .= '<div class="col-md-4">    
                <button type="button" class="btn btn-light-gray btn-lg w-100 shadow-sm" id="assign-poll-user" onclick="assignPollUser(' . htmlspecialchars($inquiry_id, ENT_QUOTES, 'UTF-8') . ');">
                    <i class="fa-solid fa-user-plus"></i> Assign
                </button>
            </div>
        </div>';
}

echo $data;

mysqli_close($dbc);
?>

<script>
VirtualSelect.init({  
	ele: '#poll_user_assignment'
});
</script>