<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

ob_start();

if (!checkRole('spotlight_admin')){
    ob_clean();
    echo "Unauthorized access";
    exit();
}

if (!isset($_SESSION['id'])) {
    ob_clean();
    echo "Session not found";
    exit();
}

if (!isset($_POST['inquiry_id'])) {
    ob_clean();
    echo "No inquiry ID provided";
    exit();
}

$inquiry_id = mysqli_real_escape_string($dbc, $_POST['inquiry_id']);

$spotlight_query = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_type'";
$column_check = mysqli_query($dbc, $spotlight_query);
$award_type_exists = mysqli_num_rows($column_check) > 0;

if ($award_type_exists) {
    $spotlight_query = "SELECT inquiry_name, inquiry_status, certificate_eligible, award_type, award_settings FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
} else {
    $spotlight_query = "SELECT inquiry_name, inquiry_status, certificate_eligible FROM spotlight_inquiry WHERE inquiry_id = '$inquiry_id'";
}

$spotlight_result = mysqli_query($dbc, $spotlight_query);
$spotlight_data = mysqli_fetch_assoc($spotlight_result);
$spotlight_name = htmlspecialchars($spotlight_data['inquiry_name'] ?? 'Unknown Spotlight');
$spotlight_status = $spotlight_data['inquiry_status'] ?? '';
$certificate_eligible = $spotlight_data['certificate_eligible'] ?? 0;
$current_award_type = $award_type_exists ? ($spotlight_data['award_type'] ?? 'none') : ($certificate_eligible ? 'certificate' : 'none');

$award_config = getSpotlightAwardConfig($inquiry_id, $dbc);
$current_badge_id = $award_config['award_settings']['badge_id'] ?? null;

$winners = [];
if ($spotlight_status === 'Closed') {
    $winner_query = "
        SELECT 
            sn.assignment_user,
            u.first_name,
            u.last_name,
            u.display_name,
            u.display_agency,
            COUNT(sb.ballot_id) as vote_count
        FROM spotlight_nominee sn
        LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id
        LEFT JOIN users u ON sn.assignment_user = u.user
        WHERE sn.question_id = '$inquiry_id'
        GROUP BY sn.assignment_user, sn.assignment_id, u.first_name, u.last_name, u.display_name, u.display_agency
        ORDER BY vote_count DESC
    ";
    
    $winner_result = mysqli_query($dbc, $winner_query);
    if (!$winner_result) {
        ob_clean();
        echo "Database error: " . mysqli_error($dbc);
        exit();
    }
    
    $max_votes = 0;
    $all_nominees = [];
    
    while ($row = mysqli_fetch_assoc($winner_result)) {
        $all_nominees[] = $row;
        if ($row['vote_count'] > $max_votes) {
            $max_votes = $row['vote_count'];
        }
    }
    
    foreach ($all_nominees as $nominee) {
        if ($nominee['vote_count'] == $max_votes && $max_votes > 0) {
            $winners[] = $nominee;
        }
    }
}

$certificate_query = "
    SELECT 
        sc.*,
        u.first_name,
        u.last_name,
        u.display_name,
        u.display_agency
    FROM spotlight_certificates sc
    LEFT JOIN users u ON sc.winner_user = u.user
    WHERE sc.inquiry_id = '$inquiry_id'
    ORDER BY sc.created_date DESC
";

$certificate_result = mysqli_query($dbc, $certificate_query);
$existing_certificates = [];

if ($certificate_result) {
    while ($cert = mysqli_fetch_assoc($certificate_result)) {
        $existing_certificates[] = $cert;
    }
}

$badge_query = "
    SELECT 
        sb.spotlight_badge_id,
        sb.inquiry_id,
        sb.badge_id,
        sb.winner_user,
        sb.assigned_by,
        sb.assigned_date,
        sb.assignment_type,
        b.name as badge_name,
        b.description as badge_description,
        u.first_name,
        u.last_name,
        u.display_name,
        u.display_agency
    FROM spotlight_badges sb
    LEFT JOIN badges b ON sb.badge_id = b.id
    LEFT JOIN users u ON sb.winner_user = u.user
    WHERE sb.inquiry_id = '$inquiry_id'
    ORDER BY sb.assigned_date DESC
";
$badge_result = mysqli_query($dbc, $badge_query);
$existing_badges = [];
if ($badge_result) {
    while ($badge = mysqli_fetch_assoc($badge_result)) {
        $existing_badges[] = $badge;
    }
}

ob_clean();
?>

<input type="hidden" id="awards_inquiry_id" value="<?php echo $inquiry_id; ?>" />

<div class="row gx-3 mt-3">
 	<div class="col-md-6">
        <table class="table mb-2">
            <thead class="table-light">
                <tr>
                    <th scope="col"><i class="fa-solid fa-trophy"></i> Award Configuration</th>
                </tr>
            </thead>
        </table>
        
        <div class="mb-3">
            <?php if ($award_type_exists): ?>
                <form id="award-config-form">
                    <div class="mb-3">
                        <label for="award_type" class="form-label fw-bold">Award Type</label>
                        <select class="form-select" id="award_type" name="award_type">
                            <option value="none" <?php echo $current_award_type === 'none' ? 'selected' : ''; ?>>No Award</option>
                            <option value="certificate" <?php echo $current_award_type === 'certificate' ? 'selected' : ''; ?>>Certificate Only</option>
                            <option value="badge" <?php echo $current_award_type === 'badge' ? 'selected' : ''; ?>>Badge Only</option>
                            <option value="both" <?php echo $current_award_type === 'both' ? 'selected' : ''; ?>>Certificate and Badge</option>
                            <option value="gem" disabled>Gem (Coming Soon)</option>
                        </select>
                        <div class="form-text">
                            Select the type of award winners will receive for this spotlight.
                        </div>
                    </div>
                    
                    <div id="certificate-settings" class="mb-3" style="display: <?php echo in_array($current_award_type, ['certificate', 'both']) ? 'block' : 'none'; ?>;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Winners will receive a downloadable PDF certificate upon spotlight completion.
                        </div>
                    </div>
                    
					<div id="badge-settings" class="mb-3" style="display: <?php echo in_array($current_award_type, ['badge', 'both']) ? 'block' : 'none'; ?>;">
					    <label class="form-label fw-bold">Select Badge</label>
    
					  	<input type="hidden" id="badge_id" name="badge_id" value="<?php echo $current_badge_id; ?>">
    
					 	<div class="card">
					        <div class="card-header">
					            <h6 class="mb-0">
					                <i class="fas fa-award me-2"></i>Choose Badge Design
					                <small class="text-muted ms-2">Click on a badge to select it for spotlight awards</small>
					            </h6>
					        </div>
					        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
					            <div id="badge-selector-content">
					                <div class="text-center py-4">
					                    <div class="spinner-border text-primary" role="status">
					                        <span class="visually-hidden">Loading badges...</span>
					                    </div>
					                    <p class="mt-2 text-muted">Loading available badges...</p>
					                </div>
					            </div>
					        </div>
					    </div>
    
					    <div class="alert alert-info mt-2">
					        <i class="fas fa-info-circle me-2"></i>
					        Winners will automatically receive the selected badge upon spotlight completion.
					    </div>
					</div>
                    
                    <button type="button" class="btn btn-orange" onclick="updateAwardSettings()">
                        <i class="fa-solid fa-cloud"></i> Save Award Settings
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-database me-2"></i>
                    <strong>Database Update Required</strong><br>
                    To use the new award system, please run the database migration script to add the award_type column.
                    <br><br>
                    Current setting: <?php echo $certificate_eligible ? 'Certificate Eligible' : 'No Awards'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

	<div class="col-md-6">
        <table class="table mb-2">
            <thead class="table-light">
                <tr>
                    <th scope="col"><i class="fa-solid fa-medal"></i> Award Management</th>
                </tr>
            </thead>
        </table>
        
        <div class="mb-3">
            <?php if ($spotlight_status !== 'Closed'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-2"></i>
                    Award management is only available for closed spotlights with determined winners.
                    <br><small class="text-muted">Current Status: <strong><?php echo htmlspecialchars($spotlight_status); ?></strong></small>
                </div>
            <?php elseif (empty($winners)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No winners determined for this spotlight yet.
                </div>
            <?php elseif ($current_award_type === 'none'): ?>
                <div class="alert alert-secondary">
                    <i class="fas fa-ban me-2"></i>
                    No awards configured for this spotlight.
                </div>
            <?php else: ?>
                
      		<?php if (in_array($current_award_type, ['certificate', 'both'])): ?>
          	  	<h6 class="fw-bold text-primary mb-3">
              	  	<i class="fas fa-certificate me-2"></i>Certificate Management
           	 	</h6>
                    
             	<div class="mb-3">
               	 	<label class="form-label fw-bold">Certificate Awards for Winners (<?php echo count($winners); ?>):</label>
                  		<?php foreach ($winners as $winner): ?>
                            <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($winner['display_name'] ?: ($winner['first_name'] . ' ' . $winner['last_name'])); ?></strong>
                                    <?php if ($winner['display_agency']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($winner['display_agency']); ?></small>
                                    <?php endif; ?>
                                    <br><small class="text-info"><?php echo $winner['vote_count']; ?> votes</small>
                                </div>
                                <div>
                                    <?php 
                                    $has_certificate = false;
                                    foreach ($existing_certificates as $cert) {
                                        if ($cert['winner_user'] === $winner['assignment_user']) {
                                            $has_certificate = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($has_certificate): ?>
                                        <span class="badge bg-success">Certificate Issued</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="generateCertificate('<?php echo $winner['assignment_user']; ?>')">
                                            <i class="fas fa-certificate me-1"></i>Generate Certificate
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
         	   	<?php if (in_array($current_award_type, ['badge', 'both'])): ?>
                    <h6 class="fw-bold text-success mb-3">
                        <i class="fas fa-award me-2"></i>Badge Management
                    </h6>
                    
                  	<div class="mb-3">
                        <label class="form-label fw-bold">Badge Awards for Winners (<?php echo count($winners); ?>):</label>
                        <?php if ($current_badge_id): ?>
                            <?php foreach ($winners as $winner): ?>
                                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($winner['display_name'] ?: ($winner['first_name'] . ' ' . $winner['last_name'])); ?></strong>
                                        <?php if ($winner['display_agency']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($winner['display_agency']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-info"><?php echo $winner['vote_count']; ?> votes</small>
                                    </div>
                                    <div>
                                        <?php 
                                        $has_badge = false;
                                        foreach ($existing_badges as $badge) {
                                            if ($badge['winner_user'] === $winner['assignment_user']) {
                                                $has_badge = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if ($has_badge): ?>
                                            <span class="badge bg-success">Badge Assigned</span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="assignBadge('<?php echo $winner['assignment_user']; ?>')">
                                                <i class="fas fa-award me-1"></i>Assign Badge
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Please select a badge in the Award Configuration section to enable badge assignment.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($existing_certificates)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Issued Certificates (<?php echo count($existing_certificates); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Issue Date</th>
                                <th>Downloads</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_certificates as $cert): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['display_name'] ?: ($cert['first_name'] . ' ' . $cert['last_name'])); ?></strong>
                                        <?php if ($cert['display_agency']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($cert['display_agency']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-info">@<?php echo htmlspecialchars($cert['winner_user']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($cert['created_date'])); ?>
                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($cert['created_date'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $cert['download_count']; ?> downloads</span>
                                        <?php if ($cert['last_downloaded']): ?>
                                            <br><small class="text-muted">Last: <?php echo date('M j', strtotime($cert['last_downloaded'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="previewCertificate('<?php echo $cert['certificate_hash']; ?>')"
                                                    title="Preview Certificate">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" 
                                                    onclick="regenerateCertificate('<?php echo $cert['winner_user']; ?>')"
                                                    title="Regenerate Certificate">
                                                <i class="fas fa-refresh"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="revokeCertificate('<?php echo $cert['certificate_id']; ?>')"
                                                    title="Revoke Certificate">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($existing_badges)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-award me-2"></i>Assigned Badges (<?php echo count($existing_badges); ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Badge</th>
                                <th>Assignment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_badges as $badge): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($badge['display_name'] ?: ($badge['first_name'] . ' ' . $badge['last_name'])); ?></strong>
                                        <?php if ($badge['display_agency']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($badge['display_agency']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-info">@<?php echo htmlspecialchars($badge['winner_user']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($badge['badge_name']); ?></strong>
                                        <?php if ($badge['badge_description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($badge['badge_description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($badge['assigned_date'])); ?>
                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($badge['assigned_date'])); ?></small>
                                        <br><small class="text-info"><?php echo ucfirst($badge['assignment_type']); ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="revokeBadge('<?php echo $badge['spotlight_badge_id']; ?>')"
                                                title="Revoke Badge">
                                            <i class="fas fa-trash me-1"></i>Revoke
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function loadBadgeSelector() {
	const inquiryId = $('#awards_inquiry_id').val();
	const currentBadgeId = $('#badge_id').val();
    
	$('#badge-selector-content').html(`
	        <div class="text-center py-4">
	            <div class="spinner-border text-primary" role="status">
	                <span class="visually-hidden">Loading badges...</span>
	            </div>
	            <p class="mt-2 text-muted">Loading available badges...</p>
	        </div>
	    `);
    
	    $.post('ajax/spotlight_admin/read_available_badges_for_assignment.php', {
	        inquiry_id: inquiryId,
	        current_badge_id: currentBadgeId
	    }, function(response) {
	        $('#badge-selector-content').html(response);
	    }).fail(function(xhr, status, error) {
	        console.error('Badge loading error:', error);
	        $('#badge-selector-content').html(`
	            <div class="alert alert-danger">
	                <i class="fas fa-exclamation-triangle me-2"></i>
	                Error loading badges. Please refresh the page.
	                <br><small>Error: ${error}</small>
	            </div>
	        `);
	    });
	}

	function clearBadgeSelector() {
	    $('#badge-selector-content').html('');
	}

	$('#award_type').on('change', function() {
	    const selectedType = $(this).val();
    
	    $('#certificate-settings').hide();
	    $('#badge-settings').hide();
    
	    if (selectedType === 'certificate' || selectedType === 'both') {
	        $('#certificate-settings').show();
	    }
	    if (selectedType === 'badge' || selectedType === 'both') {
	        $('#badge-settings').show();
	     	loadBadgeSelector();
	    } else {
	     	clearBadgeSelector();
	    }
	});

	function refreshAwardsContent() {
	    const selectedAwardType = $('#award_type').val();
    
	 	if (selectedAwardType === 'badge' || selectedAwardType === 'both') {
	        if ($('#badge-settings').is(':visible')) {
	            loadBadgeSelector();
	        }
	    }
	}

	$(document).ready(function() {
		setTimeout(function() {
	        if ($('#badge-settings').is(':visible')) {
	            loadBadgeSelector();
	        }
	    }, 100);
	});
	
function updateAwardSettings() {
    const inquiryId = $('#awards_inquiry_id').val();
    const awardType = $('#award_type').val();
    const badgeId = $('#badge_id').val();
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    button.disabled = true;
    
    let postData = {
        inquiry_id: inquiryId,
        award_type: awardType
    };
    
    if (awardType === 'badge' || awardType === 'both') {
        postData.badge_id = badgeId;
    }
    
    $.post('ajax/spotlight_admin/update_award_settings.php', postData, function(response) {
        let result;
        if (typeof response === 'string') {
            response = response.trim();
            try {
                result = JSON.parse(response);
            } catch (e) {
                showToast('Error parsing server response: ' + e.message, 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.status === 'success') {
            showToast(result.message || 'Award settings updated successfully', 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error updating award settings', 'error');
        }
    }).fail(function(xhr, status, error) {
        showToast('Network error occurred: ' + error, 'error');
    }).always(function() {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function generateCertificate(winnerUser) {
    const inquiryId = $('#awards_inquiry_id').val();
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generating...';
    button.disabled = true;
    
    $.post('ajax/spotlight_admin/generate_certificate.php', {
        inquiry_id: inquiryId,
        winner_user: winnerUser
    }, function(response) {
        let result;
        if (typeof response === 'string') {
            try {
                result = JSON.parse(response);
            } catch (e) {
                showToast('Error parsing server response', 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.status === 'success') {
            showToast(result.message || 'Certificate generated successfully', 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error generating certificate', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', error);
        showToast('Network error occurred: ' + error, 'error');
    }).always(function() {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function assignBadge(winnerUser) {
    const inquiryId = $('#awards_inquiry_id').val();
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Assigning...';
    button.disabled = true;
    
    $.post('ajax/spotlight_admin/assign_badge.php', {
        inquiry_id: inquiryId,
        winner_user: winnerUser
    }, function(response) {
        let result;
        if (typeof response === 'string') {
            try {
                result = JSON.parse(response);
            } catch (e) {
                showToast('Error parsing server response', 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.status === 'success') {
            showToast(result.message || 'Badge assigned successfully', 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error assigning badge', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', error);
        showToast('Network error occurred: ' + error, 'error');
    }).always(function() {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function regenerateCertificate(winnerUser) {
    if (!confirm('Are you sure you want to regenerate this certificate? The old certificate link will no longer work.')) {
        return;
    }
    
    const inquiryId = $('#awards_inquiry_id').val();
    
    $.post('ajax/spotlight_admin/certificate_actions.php', {
        action: 'regenerate',
        inquiry_id: inquiryId,
        winner_user: winnerUser
    }, function(response) {
        let result;
        if (typeof response === 'string') {
            try {
                result = JSON.parse(response);
            } catch (e) {
                console.error('JSON parse error:', e);
                showToast('Error parsing server response', 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.success) {
            showToast(result.message, 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error regenerating certificate', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', error);
        showToast('Network error occurred: ' + error, 'error');
    });
}

function revokeCertificate(certificateId) {
    if (!confirm('Are you sure you want to revoke this certificate? This action cannot be undone.')) {
        return;
    }
    
    const inquiryId = $('#awards_inquiry_id').val();
    
    $.post('ajax/spotlight_admin/revoke_certificate.php', {
        certificate_id: certificateId
    }, function(response) {
        let result;
        if (typeof response === 'string') {
            try {
                result = JSON.parse(response);
            } catch (e) {
                console.error('JSON parse error:', e);
                showToast('Error parsing server response', 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.status === 'success') {
            showToast(result.message, 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error revoking certificate', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', error);
        showToast('Network error occurred: ' + error, 'error');
    });
}

function revokeBadge(spotlightBadgeId) {
    if (!confirm('Are you sure you want to revoke this badge? This action cannot be undone.')) {
        return;
    }
    
    const inquiryId = $('#awards_inquiry_id').val();
    
    $.post('ajax/spotlight_admin/revoke_spotlight_badge.php', {
        spotlight_badge_id: spotlightBadgeId
    }, function(response) {
        let result;
        if (typeof response === 'string') {
            try {
                result = JSON.parse(response);
            } catch (e) {
                console.error('JSON parse error:', e);
                showToast('Error parsing server response', 'error');
                return;
            }
        } else {
            result = response;
        }
        
        if (result.status === 'success') {
            showToast(result.message, 'success');
            
            setTimeout(function() {
                readSpotlightDetails(inquiryId);
            }, 250);
        } else {
            showToast(result.message || 'Error revoking badge', 'error');
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX Error:', error);
        showToast('Network error occurred: ' + error, 'error');
    });
}

function previewCertificate(certificateHash) {
    window.open('spotlight_certificates.php?hash=' + certificateHash, '_blank');
}

$('#award_type').on('change', function() {
    const selectedType = $(this).val();
    
    $('#certificate-settings').hide();
    $('#badge-settings').hide();
    
    if (selectedType === 'certificate' || selectedType === 'both') {
        $('#certificate-settings').show();
    }
    if (selectedType === 'badge' || selectedType === 'both') {
        $('#badge-settings').show();
    }
});

function showToast(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } else {
        alert(message);
    }
}

</script>

<style>
.card {
    transition: box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.85em;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.table th {
    border-top: none;
    font-weight: 600;
}
</style>