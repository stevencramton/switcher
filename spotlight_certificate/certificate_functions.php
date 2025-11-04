<?php
function isSpotlightWinner($inquiry_id, $user, $dbc) {
    $winner_query = "
        SELECT 
            sn.assignment_user,
            COUNT(sb.answer_id) as vote_count
        FROM spotlight_nominee sn
        LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = ?
        WHERE sn.question_id = ? AND sn.assignment_user = ?
        GROUP BY sn.assignment_user
        HAVING vote_count > 0";
    
    $stmt = mysqli_prepare($dbc, $winner_query);
    mysqli_stmt_bind_param($stmt, 'iis', $inquiry_id, $inquiry_id, $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user_votes = mysqli_fetch_assoc($result)['vote_count'];
        
      	$max_votes_query = "
            SELECT MAX(vote_count) as max_votes
            FROM (
                SELECT COUNT(sb.answer_id) as vote_count
                FROM spotlight_nominee sn
                LEFT JOIN spotlight_ballot sb ON sn.assignment_id = sb.answer_id AND sb.question_id = ?
                WHERE sn.question_id = ?
                GROUP BY sn.assignment_user
                HAVING vote_count > 0
            ) as vote_counts";
        
        $stmt2 = mysqli_prepare($dbc, $max_votes_query);
        mysqli_stmt_bind_param($stmt2, 'ii', $inquiry_id, $inquiry_id);
        mysqli_stmt_execute($stmt2);
        $max_result = mysqli_stmt_get_result($stmt2);
        
        if ($max_result && mysqli_num_rows($max_result) > 0) {
            $max_votes = mysqli_fetch_assoc($max_result)['max_votes'];
            mysqli_stmt_close($stmt2);
            mysqli_stmt_close($stmt);
            return $user_votes == $max_votes;
        }
        mysqli_stmt_close($stmt2);
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function getUserSpotlightWins($user, $dbc) {
    $wins = array();
    
    // Check if award_type column exists for backward compatibility
    $column_check_query = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_type'";
    $column_result = mysqli_query($dbc, $column_check_query);
    $award_type_exists = mysqli_num_rows($column_result) > 0;
    
    if ($award_type_exists) {
        // Use new award system - only get closed spotlights with certificate awards
        $query = "SELECT inquiry_id, inquiry_name, inquiry_closing, showcase_end_date, award_type
                  FROM spotlight_inquiry 
                  WHERE inquiry_status = 'Closed' AND award_type = 'certificate'
                  ORDER BY inquiry_closing DESC";
    } else {
        // Fall back to old system - use certificate_eligible field
        $query = "SELECT inquiry_id, inquiry_name, inquiry_closing, showcase_end_date, certificate_eligible
                  FROM spotlight_inquiry 
                  WHERE inquiry_status = 'Closed' AND certificate_eligible = 1
                  ORDER BY inquiry_closing DESC";
    }
    
    $result = mysqli_query($dbc, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (isSpotlightWinner($row['inquiry_id'], $user, $dbc)) {
                $wins[] = array(
                    'inquiry_id' => $row['inquiry_id'],
                    'inquiry_name' => $row['inquiry_name'],
                    'inquiry_closing' => $row['inquiry_closing'],
                    'showcase_end_date' => $row['showcase_end_date']
                );
            }
        }
    }
    
    return $wins;
}

function generateCertificateHash($inquiry_id, $user) {
    return hash('sha256', $inquiry_id . '_' . $user . '_' . time() . '_spotlight_cert');
}

function getCertificateRecord($inquiry_id, $user, $dbc) {
    // First check if this spotlight is configured for certificate awards
    $award_check_query = "
        SELECT award_type, certificate_eligible 
        FROM spotlight_inquiry 
        WHERE inquiry_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $award_check_query);
    if (!$stmt) {
        error_log('Failed to prepare award check query: ' . mysqli_error($dbc));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $award_config = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$award_config) {
        error_log("Spotlight $inquiry_id not found when checking award configuration");
        return false;
    }
    
    // Check if award_type column exists (for backward compatibility)
    $column_check_query = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_type'";
    $column_result = mysqli_query($dbc, $column_check_query);
    $award_type_exists = mysqli_num_rows($column_result) > 0;
    
    // Determine if certificates are enabled for this spotlight
    $certificates_enabled = false;
    
    if ($award_type_exists) {
        // Use new award system
        $certificates_enabled = ($award_config['award_type'] === 'certificate');
    } else {
        // Fall back to old certificate_eligible field
        $certificates_enabled = ($award_config['certificate_eligible'] == 1);
    }
    
    // If certificates are not enabled for this spotlight, return false
    if (!$certificates_enabled) {
        error_log("Certificates not enabled for spotlight $inquiry_id (award_type: " . 
                 ($award_config['award_type'] ?? 'null') . ", certificate_eligible: " . 
                 ($award_config['certificate_eligible'] ?? 'null') . ")");
        return false;
    }
    
    // Check if certificate already exists
    $check_query = "SELECT certificate_hash, created_date, download_count, last_downloaded 
                    FROM spotlight_certificates 
                    WHERE inquiry_id = ? AND winner_user = ?";
    $stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($stmt, 'is', $inquiry_id, $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $cert = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $cert;
    }
    
    mysqli_stmt_close($stmt);
    
    // Verify user is actually a winner before creating certificate
    if (!isSpotlightWinner($inquiry_id, $user, $dbc)) {
        error_log("User $user is not a winner of spotlight $inquiry_id, not creating certificate");
        return false;
    }
    
    // Create new certificate record
    $hash = generateCertificateHash($inquiry_id, $user);
    $insert_query = "INSERT INTO spotlight_certificates (inquiry_id, winner_user, certificate_hash) 
                     VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $insert_query);
    mysqli_stmt_bind_param($stmt, 'iss', $inquiry_id, $user, $hash);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        error_log("Created new certificate for user $user in spotlight $inquiry_id");
        return array(
            'certificate_hash' => $hash,
            'created_date' => date('Y-m-d H:i:s'),
            'download_count' => 0,
            'last_downloaded' => null
        );
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function trackCertificateDownload($certificate_hash, $dbc) {
    $update_query = "UPDATE spotlight_certificates 
                     SET download_count = download_count + 1, last_downloaded = NOW()
                     WHERE certificate_hash = ?";
    $stmt = mysqli_prepare($dbc, $update_query);
    mysqli_stmt_bind_param($stmt, 's', $certificate_hash);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

function verifyCertificateHash($hash, $dbc) {
    $query = "SELECT sc.inquiry_id, sc.winner_user, sc.created_date,
                     si.inquiry_name, si.inquiry_closing, si.showcase_end_date,
                     u.first_name, u.last_name, u.display_title, u.display_agency
              FROM spotlight_certificates sc
              JOIN spotlight_inquiry si ON sc.inquiry_id = si.inquiry_id
              JOIN users u ON sc.winner_user = u.user
              WHERE sc.certificate_hash = ?";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 's', $hash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $cert_details = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $cert_details;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

function formatAchievementText($inquiry_name, $user_name, $award_date) {
	$formatted_date = date('F j, Y', strtotime($award_date));
    
    return "This certificate recognizes {$user_name} for achieving outstanding recognition " .
           "in the {$inquiry_name} Showcase. Through dedication, excellence, and " .
           "commitment to service, {$user_name} has demonstrated the values that make our organization stronger. " .
           "This achievement reflects not only individual excellence but also the positive impact made on colleagues and the broader community we serve.";
}

/**
 * Check if spotlight is configured for certificate awards
 * Helper function for the new award system
 */
function isSpotlightCertificateEligible($inquiry_id, $dbc) {
    $query = "
        SELECT award_type, certificate_eligible 
        FROM spotlight_inquiry 
        WHERE inquiry_id = ?
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $inquiry_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$data) {
        return false;
    }
    
    // Check if award_type column exists
    $column_check_query = "SHOW COLUMNS FROM spotlight_inquiry LIKE 'award_type'";
    $column_result = mysqli_query($dbc, $column_check_query);
    $award_type_exists = mysqli_num_rows($column_result) > 0;
    
    if ($award_type_exists) {
        // Use new award system
        return ($data['award_type'] === 'certificate');
    } else {
        // Fall back to old system
        return ($data['certificate_eligible'] == 1);
    }
}
?>