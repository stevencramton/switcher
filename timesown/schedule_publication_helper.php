<?php
function checkSchedulePublication($dbc, $tenant_id, $start_date, $end_date = null) {
    if ($end_date === null) {
        $end_date = $start_date;
    }
    
    $query = "
        SELECT sp.*, 
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
    $publication_info = null;
    
    foreach ($publications as $pub) {
        if ($pub['is_published'] == 1) {
            $is_published = true;
            $publication_info = $pub;
            break;
        }
    }
    
    return [
        'is_published' => $is_published,
        'publications' => $publications,
        'active_publication' => $publication_info
    ];
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