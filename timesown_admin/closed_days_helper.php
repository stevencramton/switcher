<?php
// Helper functions to add closed days functionality to existing schedule endpoints

function getClosedDaysForDateRange($dbc, $tenant_id, $start_date, $end_date) {
    $query = "SELECT date, type, title, notes, allow_shifts 
              FROM to_closed_days 
              WHERE tenant_id = ? AND date BETWEEN ? AND ? 
              ORDER BY date ASC";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $closed_days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $closed_days[$row['date']] = [
            'type' => $row['type'],
            'title' => $row['title'],
            'notes' => $row['notes'],
            'allow_shifts' => (bool)$row['allow_shifts']
        ];
    }
    
    return $closed_days;
}

function isDateClosed($dbc, $tenant_id, $date) {
    $query = "SELECT type, title, notes, allow_shifts 
              FROM to_closed_days 
              WHERE tenant_id = ? AND date = ?";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'is', $tenant_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return [
            'type' => $row['type'],
            'title' => $row['title'],
            'notes' => $row['notes'],
            'allow_shifts' => (bool)$row['allow_shifts']
        ];
    }
    
    return false;
}

function addClosedDaysToScheduleData($dbc, $tenant_id, &$schedule_data, $start_date, $end_date) {
    // Get closed days for the date range
    $closed_days = getClosedDaysForDateRange($dbc, $tenant_id, $start_date, $end_date);
    
    // Add closed days to the schedule data
    $schedule_data['closed_days'] = $closed_days;
    
    // Mark dates as closed/holiday in the schedule data
    if (isset($schedule_data['dates'])) {
        foreach ($schedule_data['dates'] as $date => &$date_data) {
            if (isset($closed_days[$date])) {
                $date_data['is_closed'] = true;
                $date_data['closed_info'] = $closed_days[$date];
            }
        }
    }
}

// Enhanced function to validate if shifts should be allowed on a closed day
function validateShiftOnClosedDay($dbc, $tenant_id, $date) {
    $closed_info = isDateClosed($dbc, $tenant_id, $date);
    
    if ($closed_info) {
        if (!$closed_info['allow_shifts']) {
            return [
                'allowed' => false,
                'message' => "Cannot create shifts on {$closed_info['type']} day: {$closed_info['title']}",
                'closed_info' => $closed_info
            ];
        } else {
            return [
                'allowed' => true,
                'warning' => "This date is marked as {$closed_info['type']}: {$closed_info['title']}",
                'closed_info' => $closed_info
            ];
        }
    }
    
    return ['allowed' => true];
}

// Function to get closed days with shift counts for reporting
function getClosedDaysWithShiftCounts($dbc, $tenant_id, $start_date, $end_date) {
    $query = "SELECT cd.date, cd.type, cd.title, cd.notes, cd.allow_shifts,
                     COUNT(s.id) as shift_count
              FROM to_closed_days cd
              LEFT JOIN to_shifts s ON cd.date = s.shift_date AND cd.tenant_id = s.tenant_id
              WHERE cd.tenant_id = ? AND cd.date BETWEEN ? AND ?
              GROUP BY cd.id
              ORDER BY cd.date ASC";
    
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'iss', $tenant_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $closed_days = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $closed_days[] = [
            'date' => $row['date'],
            'type' => $row['type'],
            'title' => $row['title'],
            'notes' => $row['notes'],
            'allow_shifts' => (bool)$row['allow_shifts'],
            'shift_count' => (int)$row['shift_count'],
            'has_conflicts' => !$row['allow_shifts'] && $row['shift_count'] > 0
        ];
    }
    
    return $closed_days;
}
?>