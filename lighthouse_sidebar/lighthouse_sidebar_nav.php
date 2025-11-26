<?php
/**
 * AJAX endpoint to return the lighthouse sidebar navigation HTML
 * This allows dynamic refresh of sidebar navigation when docks/sea states are modified
 * 
 * Location: ajax/lighthouse_sidebar/read_sidebar_nav.php
 */
session_start();
date_default_timezone_set('America/New_York');
include '../../mysqli_connect.php';
include '../../templates/functions.php';

// Security checks
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

if (!isset($_SESSION['id'])){
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$html = '';

// Harbor section (for lighthouse_harbor role)
if (checkRole('lighthouse_harbor')) {
    $html .= '<li class="header-menu menu-left">
        <span>Lighthouse</span>
    </li>
    <li class="sidebar-dropdown left-search">
        <a href="javascript:void(0);">
            <i class="fa-solid fa-compass"></i>
            <span>Safe Harbor</span>
        </a>
        <div class="sidebar-submenu">
            <ul>
                <li>
                    <a href="lighthouse_harbor.php"> My Signals
                        <span class="badge rounded-pill bg-secondary ms-auto" id="count-my-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_harbor.php?filter=closed"> My Closed Signals
                        <span class="badge rounded-pill bg-secondary ms-auto" id="count-my-closed-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>';
}

// Keeper's Watch section (for lighthouse_keeper role)
if (checkRole('lighthouse_keeper')) {
    // Fetch active docks
    $docks_query = "SELECT dock_id, dock_name, dock_icon, dock_color FROM lh_docks WHERE is_active = 1 ORDER BY dock_order ASC";
    $docks_result = mysqli_query($dbc, $docks_query);
    $docks = [];
    if ($docks_result) {
        while ($dock = mysqli_fetch_assoc($docks_result)) {
            $docks[] = $dock;
        }
    }
    
    // Fetch active sea states
    $sea_states_query = "SELECT sea_state_id, sea_state_name, sea_state_color FROM lh_sea_states WHERE is_active = 1 ORDER BY sea_state_order ASC";
    $sea_states_result = mysqli_query($dbc, $sea_states_query);
    $sea_states = [];
    if ($sea_states_result) {
        while ($state = mysqli_fetch_assoc($sea_states_result)) {
            $sea_states[] = $state;
        }
    }
    
    $html .= '<li class="header-menu menu-left">
        <span>Keeper\'s Watch</span>
    </li>
    
    <li class="sidebar-dropdown left-search">
        <a href="javascript:void(0);">
            <i class="fa-solid fa-list"></i>
            <span>Quick Access</span>
        </a>
        <div class="sidebar-submenu">
            <ul>
                <li>
                    <a href="lighthouse_keeper.php">
                        <span>View All Signals</span>
                        <span class="badge rounded-pill bg-secondary ms-auto" id="quick-all-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_keeper.php?filter=assigned">
                        <span>My Assigned Signals</span>
                        <span class="badge rounded-pill bg-secondary ms-auto" id="quick-assigned-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_keeper.php?filter=unassigned">
                        <span>Unassigned Queue</span>
                        <span class="badge rounded-pill bg-secondary ms-auto" id="quick-unassigned-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_keeper.php?filter=closed">
                        <span>View All Closed</span>
                        <span class="badge rounded-pill bg-secondary ms-auto" id="quick-closed-signals" style="font-size: 10px;">0</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>';
    
    // Dock navigation items
    foreach ($docks as $dock) {
        $dock_id = $dock['dock_id'];
        $dock_name = htmlspecialchars($dock['dock_name']);
        $dock_icon = htmlspecialchars($dock['dock_icon']);
        
        $html .= '<li class="sidebar-dropdown left-search">
            <a href="javascript:void(0);">
                <i class="' . $dock_icon . '"></i>
                <span>' . $dock_name . '</span>
                <span class="badge rounded-pill bg-secondary ms-auto" id="dock-count-' . $dock_id . '" style="font-size: 10px;">0</span>
            </a>
            <div class="sidebar-submenu">
                <ul>
                    <li class="submenu-header">
                        <span style="font-size: 11px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Sea States</span>
                    </li>
                    <li>
                        <a href="lighthouse_keeper.php?dock=' . $dock_id . '">
                            <span>View All</span>
                            <span class="badge rounded-pill bg-primary text-light ms-auto" id="dock-viewall-count-' . $dock_id . '" style="font-size: 10px;">0</span>
                        </a>
                    </li>';
        
        foreach ($sea_states as $state) {
            $state_id = $state['sea_state_id'];
            $state_name = htmlspecialchars($state['sea_state_name']);
            
            $html .= '<li>
                <a href="lighthouse_keeper.php?dock=' . $dock_id . '&state=' . $state_id . '">
                    <span>' . $state_name . '</span>
                    <span class="badge rounded-pill bg-secondary text-light ms-auto" id="dock-state-count-' . $dock_id . '-' . $state_id . '" style="font-size: 9px;">0</span>
                </a>
            </li>';
        }
        
        $html .= '</ul>
            </div>
        </li>';
    }
}

// Maritime Management section (for lighthouse_maritime role)
if (checkRole('lighthouse_maritime')) {
    $html .= '<li class="header-menu menu-left">
        <span>Maritime</span>
    </li>
    <li class="sidebar-dropdown left-search">
        <a href="javascript:void(0);">
            <i class="fa-solid fa-anchor"></i>
            <span>Management</span>
        </a>
        <div class="sidebar-submenu">
            <ul>
                <li>
                    <a href="lighthouse_maritime_docks.php">
                        <span>The Docks</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_maritime_status.php">
                        <span>Sea States</span>
                    </a>
                </li>
                <li>
                    <a href="lighthouse_maritime_priority.php">
                        <span>Priority Levels</span>
                    </a>
                </li>';
    
    if (checkRole('lighthouse_services')) {
        $html .= '<li>
            <a href="lighthouse_maritime_services.php">
                <span>Harbor Services</span>
            </a>
        </li>';
    }
    
    if (checkRole('lighthouse_captain')) {
        $html .= '<li>
            <a href="lighthouse_maritime_captain.php">
                <span>Captains Log</span>
            </a>
        </li>';
    }
    
    if (checkRole('lighthouse_reports')) {
        $html .= '<li>
            <a href="lighthouse_maritime_reports.php">
                <span>Signal Reports</span>
            </a>
        </li>';
    }
    
    $html .= '</ul>
        </div>
    </li>';
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html
]);
?>