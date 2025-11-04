<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (isset($_POST['inquiry_id'])) {
    $inquiry_id = mysqli_real_escape_string($dbc, strip_tags($_POST['inquiry_id']));
    
  	$nominee_stats_query = "SELECT 
        sn.assignment_id,
        sn.assignment_user,
        sn.assignment_agency,
        u.first_name,
        u.last_name,
        u.profile_pic
    FROM spotlight_nominee sn
    JOIN users u ON sn.assignment_user = u.user
    WHERE sn.question_id = '$inquiry_id'
    ORDER BY u.first_name ASC, u.last_name ASC";

    $nominee_result = mysqli_query($dbc, $nominee_stats_query);
    $total_nominees = mysqli_num_rows($nominee_result);

	$agencies = array();
    $nominees_list = array();

    if ($total_nominees > 0) {
        while ($nominee_row = mysqli_fetch_assoc($nominee_result)) {
            $agency = !empty($nominee_row['assignment_agency']) ? $nominee_row['assignment_agency'] : 'Unassigned';
            
            if (!isset($agencies[$agency])) {
                $agencies[$agency] = 0;
            }
            $agencies[$agency]++;
            
            $nominees_list[] = array(
                'name' => $nominee_row['first_name'] . ' ' . $nominee_row['last_name'],
                'agency' => $agency,
                'profile_pic' => $nominee_row['profile_pic']
            );
        }
    }

    $unique_agencies = count($agencies);
	?>
	
	<div class="shadow-sm bg-white rounded border mt-3">
        <div class="accordion accordion-flush" id="nomineesStatsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#nomineesStats" aria-expanded="false" aria-controls="nomineesStats">
                        <span class="btn btn-success btn-sm me-3" style="width:40px;" disabled>
                            <i class="fa-solid fa-users text-white"></i>
                        </span>
                        <span class="w-75">
                            <strong class="dark-gray">Current Nominees</strong> 
                            <span class="text-secondary">View all users currently nominated for this spotlight</span>
                        </span>
                        <span class="badge bg-primary ms-auto me-3"><?php echo $total_nominees; ?></span>
                    </button>
                </h2>
                <div id="nomineesStats" class="accordion-collapse collapse" data-bs-parent="#nomineesStatsAccordion">
                    <div class="accordion-body">
                        <?php if ($total_nominees > 0): ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-secondary mb-2"><i class="fa-solid fa-users-gear me-2"></i>Overview</h6>
                                    <div class="mb-1"><span class="fw-bold">Total Nominees:</span> <span class="text-secondary"><?php echo $total_nominees; ?> people</span></div>
                                    <div class="mb-1"><span class="fw-bold">Agencies:</span> <span class="text-secondary"><?php echo $unique_agencies; ?> different</span></div>
                                    <div class="mb-0"><span class="fw-bold">Average per Agency:</span> <span class="text-secondary"><?php echo round($total_nominees / $unique_agencies, 1); ?> nominees</span></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-secondary mb-2"><i class="fa-solid fa-building me-2"></i>Agency Breakdown</h6>
                                    <?php 
                                    $agency_count = 0;
                                    foreach ($agencies as $agency => $count): 
                                        if ($agency_count < 3): // Show only first 3 agencies
                                    ?>
                                    <div class="mb-1">
                                        <span class="fw-bold"><?php echo htmlspecialchars($agency); ?>:</span> 
                                        <span class="text-secondary"><?php echo $count; ?> <?php echo ($count == 1) ? 'nominee' : 'nominees'; ?></span>
                                    </div>
                                    <?php 
                                        $agency_count++;
                                        endif;
                                    endforeach; 
                                    if (count($agencies) > 3): 
                                    ?>
                                    <div class="mb-0"><small class="text-muted">+<?php echo (count($agencies) - 3); ?> more agencies</small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded p-3">
                                    <h6 class="text-secondary mb-2"><i class="fa-solid fa-crown me-2"></i>Recent Nominees</h6>
                                    <?php 
                                    $recent_count = 0;
                                    foreach (array_slice($nominees_list, -3, 3, true) as $nominee): 
                                        if ($recent_count < 3):
                                    ?>
                                    <div class="mb-1 d-flex align-items-center">
                                        <?php if (!empty($nominee['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($nominee['profile_pic']); ?>" class="rounded-circle me-2" width="20" height="20" alt="Profile">
                                        <?php else: ?>
                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                            <i class="fa-solid fa-user text-white" style="font-size: 10px;"></i>
                                        </div>
                                        <?php endif; ?>
                                        <span class="text-secondary small"><?php echo htmlspecialchars($nominee['name']); ?></span>
                                    </div>
                                    <?php 
                                        $recent_count++;
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>

                   	 	<div class="mt-3">
                            <h6 class="text-secondary mb-3"><i class="fa-solid fa-list-ul me-2"></i>All Nominees</h6>
                            <div class="row g-2">
                                <?php foreach ($nominees_list as $nominee): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="d-flex align-items-center p-2 bg-white border rounded shadow-sm">
                                        <?php if (!empty($nominee['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($nominee['profile_pic']); ?>" class="rounded-circle me-3" width="35" height="35" alt="Profile">
                                        <?php else: ?>
                                        <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fa-solid fa-user text-white"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small"><?php echo htmlspecialchars($nominee['name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($nominee['agency']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php else: ?>
                    	<div class="text-center py-4">
                            <i class="fa-solid fa-user-plus text-muted mb-3" style="font-size: 3rem;"></i>
                            <h6 class="text-muted">No Nominees Added Yet</h6>
                            <p class="text-muted small mb-0">Use the Add Nominees section above to start adding nominees to this spotlight.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>