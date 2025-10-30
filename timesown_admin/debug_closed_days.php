<?php
// Create this file as ajax/timesown_admin/debug_closed_days.php

session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';
include 'closed_days_helper.php';

header('Content-Type: application/json');

if (!checkRole('timesown_admin')) {
    echo json_encode(['error' => 'Insufficient privileges']);
    exit();
}

$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
$date = isset($_GET['date']) ? $_GET['date'] : '2025-12-25';

// Test 1: Check if closed_days table exists
$table_check = "SHOW TABLES LIKE 'to_closed_days'";
$result = mysqli_query($dbc, $table_check);
$table_exists = mysqli_num_rows($result) > 0;

// Test 2: Check if there are any closed days in the database
$count_query = "SELECT COUNT(*) as count FROM to_closed_days WHERE tenant_id = ?";
$stmt = mysqli_prepare($dbc, $count_query);
mysqli_stmt_bind_param($stmt, 'i', $tenant_id);
mysqli_stmt_execute($stmt);
$count_result = mysqli_stmt_get_result($stmt);
$count_data = mysqli_fetch_assoc($count_result);

// Test 3: Get closed days for the specific date
$closed_query = "SELECT * FROM to_closed_days WHERE tenant_id = ? AND date = ?";
$stmt = mysqli_prepare($dbc, $closed_query);
mysqli_stmt_bind_param($stmt, 'is', $tenant_id, $date);
mysqli_stmt_execute($stmt);
$closed_result = mysqli_stmt_get_result($stmt);
$closed_day = mysqli_fetch_assoc($closed_result);

// Test 4: Test the helper function
$closed_days = getClosedDaysForDateRange($dbc, $tenant_id, $date, $date);

// Test 5: Test the isDateClosed function
$date_closed_info = isDateClosed($dbc, $tenant_id, $date);

echo json_encode([
    'debug' => true,
    'tenant_id' => $tenant_id,
    'test_date' => $date,
    'table_exists' => $table_exists,
    'total_closed_days_count' => $count_data['count'],
    'specific_date_closed_day' => $closed_day,
    'helper_function_result' => $closed_days,
    'is_date_closed_result' => $date_closed_info
]);
?>