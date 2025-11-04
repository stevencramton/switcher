<?php
// Create this as ajax/timesown_admin/test_closed_days_helper.php

session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

header('Content-Type: application/json');

if (!checkRole('timesown_admin')) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Test if the helper file exists and functions work
$helper_exists = file_exists('closed_days_helper.php');

if ($helper_exists) {
    include 'closed_days_helper.php';
    
    $tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 3;
    $test_date = isset($_GET['date']) ? $_GET['date'] : '2025-12-27';
    
    // Test the functions
    $closed_days = getClosedDaysForDateRange($dbc, $tenant_id, $test_date, $test_date);
    $is_closed = isDateClosed($dbc, $tenant_id, $test_date);
    
    // Test the main function
    $test_response = ['test' => 'data'];
    addClosedDaysToScheduleData($dbc, $tenant_id, $test_response, $test_date, $test_date);
    
    echo json_encode([
        'success' => true,
        'helper_exists' => $helper_exists,
        'functions_work' => function_exists('getClosedDaysForDateRange'),
        'tenant_id' => $tenant_id,
        'test_date' => $test_date,
        'closed_days_result' => $closed_days,
        'is_closed_result' => $is_closed,
        'test_response_after_helper' => $test_response
    ]);
} else {
    echo json_encode([
        'success' => false,
        'helper_exists' => false,
        'error' => 'closed_days_helper.php file not found'
    ]);
}
?>