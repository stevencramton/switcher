<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['switch_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to create a ticket.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['id'];
    $is_admin = checkRole('admin_developer');
    
    // Sanitize inputs
    $title = mysqli_real_escape_string($dbc, trim($_POST['title']));
    $description = mysqli_real_escape_string($dbc, trim($_POST['description']));
    $ticket_type = mysqli_real_escape_string($dbc, $_POST['ticket_type']);
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($ticket_type)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }
    
    // Get default status (Open)
    $status_query = "SELECT status_id FROM ticket_status WHERE status_name = 'Open' LIMIT 1";
    $status_result = mysqli_query($dbc, $status_query);
    $status_id = mysqli_fetch_assoc($status_result)['status_id'];
    
    // Get priority (default to Medium if not admin)
    if ($is_admin && isset($_POST['priority_id'])) {
        $priority_id = (int)$_POST['priority_id'];
    } else {
        $priority_query = "SELECT priority_id FROM ticket_priority WHERE priority_name = 'Medium' LIMIT 1";
        $priority_result = mysqli_query($dbc, $priority_query);
        $priority_id = mysqli_fetch_assoc($priority_result)['priority_id'];
    }
    
    // Get assigned_to if admin
    $assigned_to = null;
    if ($is_admin && isset($_POST['assigned_to']) && !empty($_POST['assigned_to'])) {
        $assigned_to = (int)$_POST['assigned_to'];
    }
    
    // Generate unique ticket number
    $ticket_number = generateTicketNumber($dbc);
    
    // Start transaction
    mysqli_begin_transaction($dbc);
    
    try {
        // Insert ticket
        $insert_query = "INSERT INTO tickets 
                        (ticket_number, title, description, ticket_type, status_id, priority_id, created_by, assigned_to)
                        VALUES 
                        ('$ticket_number', '$title', '$description', '$ticket_type', $status_id, $priority_id, $user_id, " . 
                        ($assigned_to ? $assigned_to : "NULL") . ")";
        
        if (!mysqli_query($dbc, $insert_query)) {
            throw new Exception("Failed to create ticket");
        }
        
        $ticket_id = mysqli_insert_id($dbc);
        
        // Log activity
        $activity_query = "INSERT INTO ticket_activity 
                          (ticket_id, user_id, activity_type, new_value)
                          VALUES 
                          ($ticket_id, $user_id, 'created', 'Ticket created')";
        
        mysqli_query($dbc, $activity_query);
        
        // If assigned, log that too
        if ($assigned_to) {
            $assigned_query = "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = $assigned_to";
            $assigned_result = mysqli_query($dbc, $assigned_query);
            $assigned_name = mysqli_fetch_assoc($assigned_result)['name'];
            
            $assign_activity = "INSERT INTO ticket_activity 
                               (ticket_id, user_id, activity_type, new_value)
                               VALUES 
                               ($ticket_id, $user_id, 'assigned', '$assigned_name')";
            mysqli_query($dbc, $assign_activity);
        }
        
        mysqli_commit($dbc);
        
        // Return success with ticket info
        echo json_encode([
            'success' => true,
            'ticket_id' => $ticket_id,
            'ticket_number' => $ticket_number,
            'ticket_url' => 'ticket_view.php?id=' . $ticket_id,
            'message' => 'Ticket created successfully!'
        ]);
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($dbc);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create ticket. Please try again.'
        ]);
        exit();
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

function generateTicketNumber($dbc) {
    // Format: TKT-YYYYMMDD-XXXX
    $date_prefix = date('Ymd');
    
    // Get the last ticket number for today
    $query = "SELECT ticket_number FROM tickets 
              WHERE ticket_number LIKE 'TKT-$date_prefix-%' 
              ORDER BY ticket_id DESC LIMIT 1";
    $result = mysqli_query($dbc, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $last_ticket = mysqli_fetch_assoc($result)['ticket_number'];
        $last_number = (int)substr($last_ticket, -4);
        $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_number = '0001';
    }
    
    return "TKT-$date_prefix-$new_number";
}
?>