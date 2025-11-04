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

if (isset($_SESSION['id'])) {
    
    $data = '';

    if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
		$inquiry_id = strip_tags($_POST['inquiry_id']);
     	$query = "SELECT * FROM poll_inquiry WHERE inquiry_id = ?";
	} else {
    	$inquiry_id = strip_tags($_POST['inquiry_id']);
     	$query = "SELECT * FROM poll_inquiry WHERE inquiry_id = ?";
    }

    if ($stmt = mysqli_prepare($dbc, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $inquiry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
      
            while ($row = mysqli_fetch_assoc($result)) {
              	$inquiry_author = htmlspecialchars(strip_tags($row['inquiry_author']));
                $inquiry_creation_date = htmlspecialchars(strip_tags($row['inquiry_creation_date']));
                $inquiry_name = htmlspecialchars(strip_tags($row['inquiry_name']));
                $inquiry_image = htmlspecialchars(strip_tags($row['inquiry_image']));
                $inquiry_question = htmlspecialchars(strip_tags($row['inquiry_question']));
                $inquiry_info = htmlspecialchars(strip_tags($row['inquiry_info']));
                
                $data ='<div class="d-flex align-items-center text-break mb-3">
                            <div class="flex-shrink-0">
                                <img src="'.$inquiry_image.'" alt="" class="img-fluid img-thumbnail rounded-circle shadow-sm" style="width:90px;" id="read_audit_profile_pic">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-5">
                                    <div class="dark-gray fw-bold mt-3">'.$inquiry_question.'</div>
                                    <p><small class="text-secondary"><i>'.$inquiry_info.'</i></small></p>
                                    <input type="hidden" name="" id="" value="">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">';
                                
                $query_two = "SELECT * FROM poll_response WHERE question_id = ?";

                if ($stmt_two = mysqli_prepare($dbc, $query_two)) {
                    mysqli_stmt_bind_param($stmt_two, "s", $inquiry_id);
                    mysqli_stmt_execute($stmt_two);
                    $result_two = mysqli_stmt_get_result($stmt_two);

                    if (mysqli_num_rows($result_two) > 0) {
          
                        while ($row_two = mysqli_fetch_assoc($result_two)) {
                          	$response_id = htmlspecialchars(strip_tags($row_two['response_id']));
                            $response_answer = htmlspecialchars(strip_tags($row_two['response_answer']));
                            $response_info = htmlspecialchars(strip_tags($row_two['response_info']));
                            
                            $data .='<div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
                                        <div class="position-relative">
                                            <input type="radio" class="form-check-input position-absolute top-50 end-0 me-3 fs-5" name="" id="preview_'.$response_id.'" value="'.$response_id.'" disabled>
                                            <label class="list-group-item py-3 pe-5" for="preview_'.$response_id.'" style="border-radius: 0.5rem;">
                                                <strong class="fw-semibold">'.$response_answer.'</strong>
                                                <span class="d-block small opacity-75">'.$response_info.'</span>
                                            </label>
                                        </div>
                                     </div>';
                        
                        }
                    
                    } else {
                        
                        $data .='<div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
                                    <div class="position-relative">
                                        <h6 class="bg-hot shadow-sm rounded border p-3 mb-0"> 
                                            <span class="" style="line-height: 30px;"> 
                                                <i class="fa-solid fa-circle-question"></i> This poll does not contain any answers.
                                            </span>
                                        </h6>
                                    </div>
                                 </div>';
                    
                    }
                    mysqli_stmt_close($stmt_two);
                }
            }
        } else {
            $data .= '<div class="row">
                        <div class="col-md-8">
                            <div class="list-group list-group-radio d-grid gap-2 border-0 mb-2">
                                <div class="position-relative">
                                    <h6 class="bg-hot shadow-sm rounded border p-3 mb-0"> 
                                        <span class="" style="line-height: 30px;"> 
                                        <i class="fa-solid fa-circle-question"></i> This poll does not contain any questions.
                                        </span>
                                    </h6>
                                </div>
                             </div>';
        }
        
        $data .= '<hr style="border-top: 1px dashed red;">
                    <div class="mt-2">
                        <button type="button" class="btn btn-primary btn-lg shadow w-100" id="" disabled="">
                            <i class="fa-regular fa-circle-check"></i> Submit
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="h-100 p-4 bg-white border rounded-3 shadow-sm mb-3">
                        <h5 class="dark-gray"><i class="fa-solid fa-square-poll-horizontal"></i> Poll Details</h5>
                        <hr class="">
                        <div class="mb-2 mt-0"> 
                            <span class="fw-bold">Poll</span>: <span class="text-secondary" id="">'.$inquiry_name.'</span> <br>
                            <span class="fw-bold">Info</span>: <span class="text-secondary" id="">'.$inquiry_info.'</span> <br>
                            <span class="fw-bold">Date</span>: <span class="text-secondary" id="">'.$inquiry_creation_date.'</span> <br>
                            <span class="fw-bold">Author</span>: <span class="text-secondary" id="">'.$inquiry_author.'</span> <br>
                        </div>
                        <hr>'; 
                        
        $query_enrollment_count = "SELECT * FROM poll_assignment WHERE poll_id = ?";

        if ($stmt_enrollment = mysqli_prepare($dbc, $query_enrollment_count)) {
            mysqli_stmt_bind_param($stmt_enrollment, "s", $inquiry_id);
            mysqli_stmt_execute($stmt_enrollment);
            $enrollment_results = mysqli_stmt_get_result($stmt_enrollment);
            $enrolled_users = mysqli_num_rows($enrollment_results);
            mysqli_stmt_close($stmt_enrollment);
        }
        
        $query_ballot_count = "SELECT * FROM poll_ballot WHERE question_id = ?";

        if ($stmt_ballot = mysqli_prepare($dbc, $query_ballot_count)) {
            mysqli_stmt_bind_param($stmt_ballot, "s", $inquiry_id);
            mysqli_stmt_execute($stmt_ballot);
            $ballot_results = mysqli_stmt_get_result($stmt_ballot);
            $ballot_votes = mysqli_num_rows($ballot_results);
            mysqli_stmt_close($stmt_ballot);
        }
        
        if (empty($enrolled_users)) {
         	$participation_rate = 0;
            $participation_rate = $participation_rate * 100;
            $percentage_rate = number_format($participation_rate, 2, '.', '');
            
        } else {
         	$participation_rate = $ballot_votes / $enrolled_users;
            $participation_rate = $participation_rate * 100;
            $percentage_rate = number_format($participation_rate, 2, '.', '');
            
        }
        
        $data .= '<div class="mt-0">
                    <span class="fw-bold">Assigned</span>: <span class="text-secondary" id="">'.$enrolled_users.'</span> <br>
                    <span class="fw-bold">Responses</span>: <span class="text-secondary" id="">'.$ballot_votes.'</span> <br>
                    <span class="fw-bold">Participation</span>: <span class="text-secondary" id="">'.$percentage_rate.'%</span> <br>
                </div>
                <hr class="">
            <div>
            <img src="'.$inquiry_image.'" class="profile-photo me-2"> 
            <strong class="mb-3">'.$inquiry_name.' <small class="text-secondary float-end mt-2">'.$percentage_rate.'%</small></strong>
            <div class="progress mt-2" style="height: 18px;">
                <div class="progress-bar progress-bar-striped bg-secondary" role="progressbar" aria-label="Example with label" style="width: '.$percentage_rate.'%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            </div>
        </div>
    </div>
</div>';

        echo $data;

    }
    mysqli_stmt_close($stmt);
}

mysqli_close($dbc);