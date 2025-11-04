<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_reports')){
    header("Location:../../index.php?msg1");
    exit();
}

$data = '';

if (isset($_POST['inquiry_id']) && $_POST['inquiry_id'] !== "") {
    
    $inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
 	$query_spotlight = "SELECT inquiry_name, inquiry_status FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
    $spotlight_result = mysqli_query($dbc, $query_spotlight);
    $spotlight_info = mysqli_fetch_assoc($spotlight_result);
	$spotlight_name = htmlspecialchars($spotlight_info['inquiry_name']);
    $spotlight_status = $spotlight_info['inquiry_status'];
    
    $data .= '<div class="bg-white rounded shadow-sm border p-4">
                <h5 class="text-primary mb-4">
                    <i class="fa-solid fa-users-viewfinder me-2"></i>Individual Voting Records - ' . $spotlight_name . '
                </h5>';
    
	$query = "
        SELECT 
            sb.ballot_id,
            sb.ballot_user,
            sb.question_id,
            sb.answer_id,
            voter.first_name as voter_first_name,
            voter.last_name as voter_last_name,
            voter.display_agency as voter_agency,
            voter.profile_pic as voter_profile_pic,
            nominee.first_name as nominee_first_name,
            nominee.last_name as nominee_last_name,
            nominee.display_agency as nominee_agency,
            nominee.profile_pic as nominee_profile_pic,
            sn.assignment_user as nominee_username
        FROM spotlight_ballot sb
        JOIN users voter ON sb.ballot_user = voter.user
        JOIN spotlight_nominee sn ON sb.answer_id = sn.assignment_id AND sb.question_id = sn.question_id
        JOIN users nominee ON sn.assignment_user = nominee.user
        WHERE sb.question_id = '$inquiry_id'
        ORDER BY voter.first_name ASC, voter.last_name ASC";
    
    $result = mysqli_query($dbc, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        
		$total_votes = mysqli_num_rows($result);
        
		$nominee_counts = array();
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            $nominee_name = $row['nominee_first_name'] . ' ' . $row['nominee_last_name'];
            if (!isset($nominee_counts[$nominee_name])) {
                $nominee_counts[$nominee_name] = 0;
            }
            $nominee_counts[$nominee_name]++;
        }
        
   	 	arsort($nominee_counts);
        
        $data .= '<div class="row mb-4">
                    <div class="col-md-8">
                        <div class="bg-light rounded p-3">
                            <h6 class="text-secondary mb-3">
                                <i class="fa-solid fa-chart-pie me-1"></i>Vote Summary (' . $total_votes . ' total votes)
                            </h6>
                            <div class="row">';
        
        $counter = 0;
        foreach ($nominee_counts as $nominee => $count) {
            $percentage = round(($count / $total_votes) * 100, 1);
            $progress_color = $counter == 0 ? 'bg-success' : ($counter == 1 ? 'bg-info' : 'bg-secondary');
            
            $data .= '<div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">' . htmlspecialchars($nominee) . '</strong>
                            <small class="text-muted">' . $count . ' votes (' . $percentage . '%)</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ' . $progress_color . '" style="width: ' . $percentage . '%"></div>
                        </div>
                      </div>';
            $counter++;
        }
        
        $data .= '    </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-primary text-white rounded">
                            <div class="h3 mb-1">' . $total_votes . '</div>
                            <div class="small">Total Votes Cast</div>
                        </div>
                    </div>
                  </div>';
        
   		$data .= '<div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="35%">Voter</th>
                                <th width="35%">Voted For</th>
                                <th width="15%">Voter Agency</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
        
   		mysqli_data_seek($result, 0);
        $vote_number = 1;
        
        while ($row = mysqli_fetch_assoc($result)) {
            
            $voter_name = htmlspecialchars($row['voter_first_name'] . ' ' . $row['voter_last_name']);
            $voter_agency = htmlspecialchars($row['voter_agency'] ?: 'N/A');
            $voter_profile_pic = $row['voter_profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg';
            
            $nominee_name = htmlspecialchars($row['nominee_first_name'] . ' ' . $row['nominee_last_name']);
            $nominee_agency = htmlspecialchars($row['nominee_agency'] ?: 'N/A');
            $nominee_profile_pic = $row['nominee_profile_pic'] ?: 'img/profile_pic/default_img/pizza_panda.jpg';
            
            $data .= '<tr>
                        <td class="align-middle">' . $vote_number . '</td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <img src="' . htmlspecialchars($voter_profile_pic) . '" 
                                     class="rounded-circle me-2" width="32" height="32" 
                                     alt="Voter" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                                <div>
                                    <div class="fw-bold">' . $voter_name . '</div>
                                    <small class="text-muted">' . htmlspecialchars($row['ballot_user']) . '</small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <img src="' . htmlspecialchars($nominee_profile_pic) . '" 
                                     class="rounded-circle me-2" width="32" height="32" 
                                     alt="Nominee" onerror="this.src=\'img/profile_pic/default_img/pizza_panda.jpg\'">
                                <div>
                                    <div class="fw-bold">' . $nominee_name . '</div>
                                    <small class="text-muted">' . htmlspecialchars($row['nominee_username']) . '</small>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <span class="badge bg-light text-dark">' . $voter_agency . '</span>
                        </td>
                        <td class="align-middle">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="showVoterProfile(\'' . htmlspecialchars($row['ballot_user']) . '\')"
                                    data-bs-toggle="tooltip" title="View Voter Profile">
                                <i class="fa-solid fa-user"></i>
                            </button>
                        </td>
                      </tr>';
            
            $vote_number++;
        }
        
        $data .= '    </tbody>
                    </table>
                  </div>';
        
		$data .= '<div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                This shows all individual votes cast for this spotlight.
                            </small>
                        </div>
                        <div>
                            <button class="btn btn-success btn-sm me-2" onclick="exportVotingData(' . $inquiry_id . ')">
                                <i class="fa-solid fa-download me-1"></i>Export CSV
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="printVotingReport(' . $inquiry_id . ')">
                                <i class="fa-solid fa-print me-1"></i>Print Report
                            </button>
                        </div>
                    </div>
                  </div>';
    } else {
        $data .= '<div class="text-center py-5">
                    <i class="fa-solid fa-ballot fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Votes Cast Yet</h5>
                    <p class="text-muted">This spotlight hasn\'t received any votes yet.</p>
                  </div>';
    }
    
    $data .= '</div>';
    
} else {
    $data .= '<div class="alert alert-danger">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                Invalid spotlight ID provided.
              </div>';
}

echo $data;

mysqli_close($dbc);
?>