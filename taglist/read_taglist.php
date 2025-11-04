<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('admin_developer')) {
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['id'])) {
    $data = '';
	$query = "SELECT id, question_title, question_note, answer_title, answer_note FROM tagslist";
    $stmt = mysqli_prepare($dbc, $query);
    
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $question_title, $question_note, $answer_title, $answer_note);
        
        while (mysqli_stmt_fetch($stmt)) {
            $data .= '<script>
                        $(document).ready(function(){
                            $("#card_' . $id . '").flip();
                        });
                      </script>
                      <div class="mb-3" id="card_' . $id . '"> 
                          <div class="front"> 
                              <div class="p-4 bg-white border rounded-3 shadow-sm">
                                  <h2>' . htmlspecialchars($question_title) . '</h2>
                                  <hr>
                                  <p>' . htmlspecialchars($question_note) . '</p>
                              </div>
                          </div> 
                          <div class="back mb-3">
                              <div class="card p-4 bg-dark text-white border rounded-3 shadow-sm">
                                  <div class="card-header">
                                      <h2>' . htmlspecialchars($answer_title) . '</h2>
                                      <hr class="mb-0">
                                  </div>
                                  <div class="card-body">
                                      <p class="mb-3">' . htmlspecialchars($answer_note) . '</p>
                                  </div>
                                  <div class="card-footer w-100">
                                      <button type="button" class="btn btn-outline-info"><i class="fa-solid fa-arrow-left-long"></i> Back</button>
                                      <button type="button" class="btn btn-outline-info float-end">Next <i class="fa-solid fa-arrow-right-long"></i></button>
                                  </div>
                              </div> 
                          </div>
                      </div>';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }

    $data .= '<table class="table table-bordered table-hover">
                <thead class="dark-gray">
                    <tr>
                        <th class="text-center">ID</th>
                        <th class="text-center">Q Title</th>
                        <th class="text-center">Q Note</th>
                        <th class="text-center">A Title</th>
                        <th class="text-center">A Note</th>
                        <th class="text-center">Result</th>
                    </tr>
                </thead>
                <tbody>';

    $query = "SELECT id, question_title, question_note, answer_title, answer_note FROM tagslist";
    $stmt = mysqli_prepare($dbc, $query);
    
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $question_title, $question_note, $answer_title, $answer_note);
        
        while (mysqli_stmt_fetch($stmt)) {
          	$result_icon = ($question_note == $answer_note) ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-circle-xmark text-danger"></i>';
            
         	$data .= '<tr>
                        <td class="align-middle text-center">' . htmlspecialchars($id) . '</td>
                        <td class="align-middle text-center" style="cursor:pointer">' . htmlspecialchars($question_title) . '</td>
                        <td class="align-middle text-center" style="cursor:pointer">' . htmlspecialchars($question_note) . '</td>
                        <td class="align-middle text-center" style="cursor:pointer">' . htmlspecialchars($answer_title) . '</td>
                        <td class="align-middle text-center" style="cursor:pointer">' . htmlspecialchars($answer_note) . '</td>
                        <td class="align-middle text-center" style="cursor:pointer">' . $result_icon . '</td>
                      </tr>';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement.";
    }
	$data .= '</tbody></table>';
	echo $data;
}
mysqli_close($dbc);