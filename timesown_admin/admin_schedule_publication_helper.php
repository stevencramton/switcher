<?php
function checkSchedulePublication($dbc, $tenant_id, $start_date, $end_date = null) {
    if ($end_date === null) {
        $end_date = $start_date;
    }
    
    $query = "
        SELECT sp.id, sp.tenant_id, sp.start_date, sp.end_date, sp.is_published, sp.published_by, sp.unpublished_by, sp.notes, sp.created_at, sp.updated_at,
               DATE_FORMAT(DATE_SUB(COALESCE(sp.published_at, sp.created_at), INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as published_at,
               DATE_FORMAT(DATE_SUB(sp.unpublished_at, INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as unpublished_at,
               pub_user.first_name as published_by_first, 
               pub_user.last_name as published_by_last,
               unpub_user.first_name as unpublished_by_first, 
               unpub_user.last_name as unpublished_by_last
        FROM to_schedule_publication sp
        LEFT JOIN users pub_user ON sp.published_by = pub_user.id
        LEFT JOIN users unpub_user ON sp.unpublished_by = unpub_user.id
        WHERE sp.tenant_id = ? 
        AND sp.start_date <= ? 
        AND sp.end_date >= ?
        ORDER BY sp.start_date, sp.end_date
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $end_date, $start_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $publications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $publications[] = $row;
    }
    
	$is_published = false;
    $published_ranges = [];
    $active_publication = null;
    
    foreach ($publications as $pub) {
        if ($pub['is_published'] == 1) {
            $is_published = true;
            $published_ranges[] = $pub;
            if ($active_publication === null) {
                $active_publication = $pub;
            }
        }
    }
    
    return [
        'is_published' => $is_published,
        'publications' => $publications,
        'active_publication' => $active_publication,
        'published_ranges' => $published_ranges,
        'published_count' => count($published_ranges)
    ];
}

function getTenantPublications($dbc, $tenant_id) {
    $query = "
        SELECT sp.id, sp.tenant_id, sp.start_date, sp.end_date, sp.is_published, sp.published_by, sp.unpublished_by, sp.notes, sp.created_at, sp.updated_at,
               DATE_FORMAT(DATE_SUB(COALESCE(sp.published_at, sp.created_at), INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as published_at,
               DATE_FORMAT(DATE_SUB(sp.unpublished_at, INTERVAL 4 HOUR), '%Y-%m-%d %H:%i:%s') as unpublished_at,
               pub_user.first_name as published_by_first, 
               pub_user.last_name as published_by_last,
               unpub_user.first_name as unpublished_by_first, 
               unpub_user.last_name as unpublished_by_last
        FROM to_schedule_publication sp
        LEFT JOIN users pub_user ON sp.published_by = pub_user.id
        LEFT JOIN users unpub_user ON sp.unpublished_by = unpub_user.id
        WHERE sp.tenant_id = ?
        ORDER BY sp.start_date DESC, sp.created_at DESC
    ";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $publications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $publications[] = $row;
    }
    
    return $publications;
}

function publishSchedule($dbc, $tenant_id, $start_date, $end_date, $user_id, $notes = null) {
    try {
        mysqli_autocommit($dbc, false);
        
     	$overlap_query = "
            SELECT id FROM to_schedule_publication
            WHERE tenant_id = ? 
            AND is_published = 1
            AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
        ";
        
        $stmt = mysqli_prepare($dbc, $overlap_query);
        mysqli_stmt_bind_param($stmt, 'issss', $tenant_id, $start_date, $start_date, $end_date, $end_date);
        mysqli_stmt_execute($stmt);
        $overlap_result = mysqli_stmt_get_result($stmt);
        
      	while ($row = mysqli_fetch_assoc($overlap_result)) {
            $update_query = "
                UPDATE to_schedule_publication 
                SET is_published = 0, unpublished_by = ?, unpublished_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'iii', $user_id, $row['id'], $tenant_id);
            mysqli_stmt_execute($stmt);
        }
        
      	$insert_query = "
            INSERT INTO to_schedule_publication 
            (tenant_id, start_date, end_date, is_published, published_by, published_at, notes)
            VALUES (?, ?, ?, 1, ?, CONVERT_TZ(NOW(), 'UTC', 'America/New_York'), ?)
        ";
        
        $stmt = mysqli_prepare($dbc, $insert_query);
        mysqli_stmt_bind_param($stmt, 'issis', $tenant_id, $start_date, $end_date, $user_id, $notes);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create publication record');
        }
        
        $publication_id = mysqli_insert_id($dbc);
        
      	$audit_query = "
            INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'PUBLISH', 'to_schedule_publication', ?, ?, ?, ?, CONVERT_TZ(NOW(), 'UTC', 'America/New_York'))
        ";
        
        $new_values = json_encode([
            'tenant_id' => $tenant_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'published_by' => $user_id,
            'notes' => $notes
        ]);
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = mysqli_prepare($dbc, $audit_query);
        mysqli_stmt_bind_param($stmt, 'iissss', 
            $tenant_id, $user_id, $publication_id, 
            $new_values, $ip_address, $user_agent
        );
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);
        
        return ['success' => true, 'message' => 'Schedule published successfully', 'publication_id' => $publication_id];
        
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        
        // Log the actual error details for debugging (server-side only)
        error_log('Publish Schedule Helper Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        
        // Return generic error message to client (no sensitive information)
        return ['success' => false, 'message' => 'Error publishing schedule'];
    }
}

function unpublishSchedule($dbc, $tenant_id, $start_date, $end_date, $user_id, $notes = null) {
    try {
        mysqli_autocommit($dbc, false);
        
      	$overlap_query = "
            SELECT id FROM to_schedule_publication
            WHERE tenant_id = ? 
            AND is_published = 1
            AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
        ";
        
        $stmt = mysqli_prepare($dbc, $overlap_query);
        mysqli_stmt_bind_param($stmt, 'issss', $tenant_id, $start_date, $start_date, $end_date, $end_date);
        mysqli_stmt_execute($stmt);
        $overlap_result = mysqli_stmt_get_result($stmt);
        
        $updated_records = 0;
        while ($row = mysqli_fetch_assoc($overlap_result)) {
            $update_query = "
                UPDATE to_schedule_publication 
                SET is_published = 0, unpublished_by = ?, unpublished_at = CONVERT_TZ(NOW(), 'UTC', 'America/New_York'),
                    notes = CASE 
                        WHEN notes IS NULL THEN ?
                        WHEN ? IS NULL THEN notes
                        ELSE CONCAT(notes, '\n\nUnpublished: ', ?)
                    END
                WHERE id = ? AND tenant_id = ?
            ";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'isssii', $user_id, $notes, $notes, $notes, $row['id'], $tenant_id);
            mysqli_stmt_execute($stmt);
            $updated_records++;
            
            $audit_query = "
                INSERT INTO to_audit_log (tenant_id, user_id, action, table_name, record_id, new_values, ip_address, user_agent, created_at)
                VALUES (?, ?, 'UNPUBLISH', 'to_schedule_publication', ?, ?, ?, ?, NOW())
            ";
            
            $new_values = json_encode([
                'tenant_id' => $tenant_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'unpublished_by' => $user_id,
                'notes' => $notes
            ]);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = mysqli_prepare($dbc, $audit_query);
            mysqli_stmt_bind_param($stmt, 'iissss', 
                $tenant_id, $user_id, $row['id'], 
                $new_values, $ip_address, $user_agent
            );
            mysqli_stmt_execute($stmt);
        }
        
        if ($updated_records === 0) {
            mysqli_rollback($dbc);
            mysqli_autocommit($dbc, true);
            return ['success' => false, 'message' => 'No published schedules found for the specified date range'];
        }
        
        mysqli_commit($dbc);
        mysqli_autocommit($dbc, true);
        
        return ['success' => true, 'message' => "Successfully unpublished {$updated_records} schedule record(s)"];
        
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        mysqli_autocommit($dbc, true);
        
        // Log the actual error details for debugging (server-side only)
        error_log('Unpublish Schedule Helper Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        
        // Return generic error message to client (no sensitive information)
        return ['success' => false, 'message' => 'Error unpublishing schedule'];
    }
}

function formatPublicationStatus($publication_info) {
    if (!$publication_info['is_published']) {
        return '<span class="badge bg-warning text-dark">Not Published</span>';
    }
    
    $active_pub = $publication_info['active_publication'];
    $published_by = $active_pub['published_by_first'] . ' ' . $active_pub['published_by_last'];
    $published_at = date('M j, Y g:i A', strtotime($active_pub['published_at']));
    
    return '<span class="badge bg-success">Published</span><br>' .
           '<small class="text-muted">by ' . htmlspecialchars($published_by) . '<br>' .
           'on ' . $published_at . '</small>';
}
?>