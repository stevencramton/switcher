<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_maritime')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', 'Signal Reports & Analytics');
include 'templates/header.php';

// Get date range defaults (last 30 days)
$default_end = date('Y-m-d');
$default_start = date('Y-m-d', strtotime('-30 days'));

// Fetch docks for filter dropdown
$docks_query = "SELECT dock_id, dock_name, dock_icon, dock_color FROM lh_docks WHERE is_active = 1 ORDER BY dock_order ASC";
$docks_result = mysqli_query($dbc, $docks_query);

// Fetch sea states for filter dropdown
$states_query = "SELECT sea_state_id, sea_state_name, sea_state_color, is_closed_resolution FROM lh_sea_states WHERE is_active = 1 ORDER BY sea_state_order ASC";
$states_result = mysqli_query($dbc, $states_query);

// Fetch priorities for filter dropdown
$priorities_query = "SELECT priority_id, priority_name, priority_color FROM lh_priorities WHERE is_active = 1 ORDER BY priority_order ASC";
$priorities_result = mysqli_query($dbc, $priorities_query);

// Fetch services for filter dropdown
$services_query = "SELECT service_id, service_name, service_color FROM lh_services WHERE is_active = 1 ORDER BY service_order ASC";
$services_result = mysqli_query($dbc, $services_query);

// Fetch users for multi-select filter (active users who have created signals)
$users_query = "SELECT DISTINCT u.id, u.first_name, u.last_name 
                FROM users u 
                INNER JOIN lh_signals s ON s.sent_by = u.id 
                WHERE u.account_delete = 0 
                ORDER BY u.first_name, u.last_name";
$users_result = mysqli_query($dbc, $users_query);

// Fetch keepers for multi-select filter
$keepers_query = "SELECT DISTINCT u.id, u.first_name, u.last_name 
                  FROM users u 
                  INNER JOIN lh_signals s ON s.keeper_assigned = u.id 
                  WHERE u.account_delete = 0 
                  ORDER BY u.first_name, u.last_name";
$keepers_result = mysqli_query($dbc, $keepers_query);
?>

<script> $(document).ready(function(){ $(".page-wrapper").addClass("pinned"); }); </script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Select2 for multi-select -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.reports-wrapper {
    background: #f7f8fa;
    min-height: calc(100vh - 120px);
}

.page-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    border-radius: 12px;
    padding: 24px 32px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title i { font-size: 32px; opacity: 0.9; }

.page-subtitle {
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    margin-top: 4px;
}

.header-actions { display: flex; gap: 12px; }

.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-export:hover {
    background: rgba(255,255,255,0.25);
    color: white;
    border-color: rgba(255,255,255,0.5);
}

/* Filters Panel */
.filters-panel {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.filters-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-title i { color: #6b7280; }

.quick-range-btns { display: flex; gap: 8px; flex-wrap: wrap; }

.quick-range-btn {
    padding: 6px 14px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-range-btn:hover { background: #e5e7eb; color: #374151; }

.quick-range-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
}

.filter-group { display: flex; flex-direction: column; gap: 6px; }

.filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-control {
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: #f9fafb;
    color: #1f2937;
    transition: all 0.2s;
}

.filter-control:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-apply-filters {
    padding: 10px 24px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    align-self: flex-end;
}

.btn-apply-filters:hover {
    background: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.total::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.stat-card.open::before { background: linear-gradient(90deg, #10b981, #34d399); }
.stat-card.closed::before { background: linear-gradient(90deg, #6b7280, #9ca3af); }
.stat-card.avg-time::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.stat-card.high-priority::before { background: linear-gradient(90deg, #ef4444, #f87171); }

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
}

.stat-card.total .stat-icon { background: #eff6ff; color: #3b82f6; }
.stat-card.open .stat-icon { background: #ecfdf5; color: #10b981; }
.stat-card.closed .stat-icon { background: #f3f4f6; color: #6b7280; }
.stat-card.avg-time .stat-icon { background: #fffbeb; color: #f59e0b; }
.stat-card.high-priority .stat-icon { background: #fef2f2; color: #ef4444; }

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: #1f2937;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label { font-size: 13px; color: #6b7280; font-weight: 500; }

/* Report Tabs */
.report-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.report-tab {
    padding: 12px 20px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.report-tab:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #374151;
}

.report-tab.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.report-tab i { font-size: 16px; }

/* Tab Content Sections */
.tab-section { display: none; }
.tab-section.active { display: block; }

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

@media (max-width: 1200px) {
    .charts-grid { grid-template-columns: 1fr; }
}

.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.chart-card.full-width { grid-column: span 2; }

@media (max-width: 1200px) {
    .chart-card.full-width { grid-column: span 1; }
}

.chart-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-title i { color: #6b7280; }

.chart-actions { display: flex; gap: 8px; }

.chart-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: white;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 12px;
}

.chart-action-btn:hover { background: #f3f4f6; color: #374151; }

.chart-body {
    padding: 20px 24px;
    position: relative;
    min-height: 300px;
}

.chart-container { position: relative; height: 280px; }

/* Data Table in Tab */
.data-table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 24px;
}

.data-table-header {
    padding: 16px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.data-table-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #6b7280;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    text-transform: uppercase;
}

.data-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    color: #374151;
}

.data-table tbody tr:hover { background: #f9fafb; }

.color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.progress-bar-wrapper {
    background: #e5e7eb;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
    min-width: 100px;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s;
}

/* Detailed Reports Table */
.reports-table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
    margin-bottom: 24px;
}

.table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.table-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.table-search {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.table-search input {
    padding: 8px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    width: 250px;
    background: #f9fafb;
}

.table-search input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
}

.reports-table {
    width: 100%;
    border-collapse: collapse;
}

.reports-table thead th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #6b7280;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.reports-table thead th:hover { background: #f3f4f6; color: #374151; }

.reports-table thead th i { margin-left: 6px; font-size: 10px; opacity: 0.5; }
.reports-table thead th.sorted i { opacity: 1; color: #3b82f6; }

.reports-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
    color: #374151;
}

.reports-table tbody tr:hover { background: #f9fafb; }
.reports-table tbody tr:last-child td { border-bottom: none; }

.dock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    background: #f3f4f6;
}

.dock-badge i { font-size: 11px; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.user-cell { display: flex; align-items: center; gap: 10px; }

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
}

.user-name { font-weight: 500; }

/* No Data State */
.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.no-data i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
.no-data p { font-size: 16px; margin: 0; }

/* Pagination */
.table-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    flex-wrap: wrap;
    gap: 12px;
}

.pagination-info { font-size: 13px; color: #6b7280; }
.pagination-buttons { display: flex; gap: 4px; }

.page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 6px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-btn:hover:not(:disabled) { background: #f3f4f6; border-color: #d1d5db; }
.page-btn.active { background: #3b82f6; border-color: #3b82f6; color: white; }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Signal Type Badge */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
}
.type-badge.feedback { background: #fef3c7; color: #92400e; }
.type-badge.feature_request { background: #dbeafe; color: #1e40af; }
.type-badge.bug_report { background: #fee2e2; color: #991b1b; }
.type-badge.other { background: #f3f4f6; color: #374151; }

/* Select2 Custom Styling */
.select2-container--default .select2-selection--multiple {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    min-height: 42px;
    padding: 4px 8px;
}
.select2-container--default .select2-selection--multiple:focus,
.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: #3b82f6;
    border: none;
    border-radius: 4px;
    color: white;
    padding: 4px 8px;
    margin: 2px;
    font-size: 12px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white;
    margin-right: 6px;
    font-weight: bold;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #fee2e2;
    background: transparent;
}
.select2-dropdown {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: #3b82f6;
}
.select2-container--default .select2-search--inline .select2-search__field {
    margin-top: 4px;
    font-size: 14px;
}
.select2-container { width: 100% !important; }
.filter-group.wide { grid-column: span 2; }
@media (max-width: 768px) { .filter-group.wide { grid-column: span 1; } }
</style>

<main class="page-content pt-2">
    <div class="tab-content">
        <?php include 'templates/alerts.php'; ?>
        <?php include 'templates/breadcrumb.php'; ?>
        <?php include 'templates/search_results_tab.php'; ?>
        <div id="main_tab" class="tab-pane fade in active show">
            <div class="container-fluid fluid-top p-3">
                <div class="row">
                    <div class="col-lg-12">
                        
                        <div class="reports-wrapper">
                            <!-- Page Header -->
                            <div class="page-header">
                                <div>
                                    <h1 class="page-title">
                                        <i class="fa-solid fa-chart-line"></i>
                                        Signal Reports & Analytics
                                    </h1>
                                    <p class="page-subtitle">Comprehensive insights into your support operations</p>
                                </div>
                                <div class="header-actions">
                                    <button class="btn-export" onclick="exportReport('pdf')">
                                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                                    </button>
                                    <button class="btn-export" onclick="exportReport('csv')">
                                        <i class="fa-solid fa-file-csv"></i> Export CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Filters Panel -->
                            <div class="filters-panel">
                                <div class="filters-header">
                                    <div class="filters-title">
                                        <i class="fa-solid fa-filter"></i>
                                        Report Filters
                                    </div>
                                    <div class="quick-range-btns">
                                        <button class="quick-range-btn" data-range="7">Last 7 Days</button>
                                        <button class="quick-range-btn active" data-range="30">Last 30 Days</button>
                                        <button class="quick-range-btn" data-range="90">Last 90 Days</button>
                                        <button class="quick-range-btn" data-range="365">Last Year</button>
                                        <button class="quick-range-btn" data-range="all">All Time</button>
                                    </div>
                                </div>
                                <div class="filters-row">
                                    <div class="filter-group">
                                        <label class="filter-label">Start Date</label>
                                        <input type="date" id="filter-start-date" class="filter-control" value="<?php echo $default_start; ?>">
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label">End Date</label>
                                        <input type="date" id="filter-end-date" class="filter-control" value="<?php echo $default_end; ?>">
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label">Dock</label>
                                        <select id="filter-dock" class="filter-control">
                                            <option value="">All Docks</option>
                                            <?php if ($docks_result) { while ($dock = mysqli_fetch_assoc($docks_result)): ?>
                                            <option value="<?php echo $dock['dock_id']; ?>"><?php echo htmlspecialchars($dock['dock_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label">Status</label>
                                        <select id="filter-status" class="filter-control">
                                            <option value="">All Statuses</option>
                                            <option value="open">Open Only</option>
                                            <option value="closed">Closed Only</option>
                                            <?php if ($states_result) { mysqli_data_seek($states_result, 0); while ($state = mysqli_fetch_assoc($states_result)): ?>
                                            <option value="<?php echo $state['sea_state_id']; ?>"><?php echo htmlspecialchars($state['sea_state_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label">Priority</label>
                                        <select id="filter-priority" class="filter-control">
                                            <option value="">All Priorities</option>
                                            <?php if ($priorities_result) { while ($priority = mysqli_fetch_assoc($priorities_result)): ?>
                                            <option value="<?php echo $priority['priority_id']; ?>"><?php echo htmlspecialchars($priority['priority_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label">Service</label>
                                        <select id="filter-service" class="filter-control">
                                            <option value="">All Services</option>
                                            <?php if ($services_result) { while ($service = mysqli_fetch_assoc($services_result)): ?>
                                            <option value="<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group wide">
                                        <label class="filter-label">Created By (Users)</label>
                                        <select id="filter-users" class="filter-control-multi" multiple="multiple">
                                            <?php if ($users_result) { while ($user = mysqli_fetch_assoc($users_result)): ?>
                                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group wide">
                                        <label class="filter-label">Assigned To (Keepers)</label>
                                        <select id="filter-keepers" class="filter-control-multi" multiple="multiple">
                                            <option value="unassigned">Unassigned</option>
                                            <?php if ($keepers_result) { while ($keeper = mysqli_fetch_assoc($keepers_result)): ?>
                                            <option value="<?php echo $keeper['id']; ?>"><?php echo htmlspecialchars($keeper['first_name'] . ' ' . $keeper['last_name']); ?></option>
                                            <?php endwhile; } ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <button class="btn-apply-filters" id="apply-filters">
                                            <i class="fa-solid fa-arrows-rotate"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="stats-grid" id="stats-grid">
                                <div class="stat-card total">
                                    <div class="stat-icon"><i class="fa-solid fa-signal"></i></div>
                                    <div class="stat-value" id="stat-total">0</div>
                                    <div class="stat-label">Total Signals</div>
                                </div>
                                <div class="stat-card open">
                                    <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
                                    <div class="stat-value" id="stat-open">0</div>
                                    <div class="stat-label">Open Signals</div>
                                </div>
                                <div class="stat-card closed">
                                    <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                                    <div class="stat-value" id="stat-closed">0</div>
                                    <div class="stat-label">Closed Signals</div>
                                </div>
                                <div class="stat-card avg-time">
                                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                                    <div class="stat-value" id="stat-avg-time">0h</div>
                                    <div class="stat-label">Avg Resolution Time</div>
                                </div>
                                <div class="stat-card high-priority">
                                    <div class="stat-icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
                                    <div class="stat-value" id="stat-high-priority">0</div>
                                    <div class="stat-label">High Priority Open</div>
                                </div>
                            </div>

                            <!-- Report Tabs -->
                            <div class="report-tabs">
                                <button class="report-tab active" data-tab="overview">
                                    <i class="fa-solid fa-gauge-high"></i> Overview
                                </button>
                                <button class="report-tab" data-tab="by-dock">
                                    <i class="fa-solid fa-anchor"></i> By Dock
                                </button>
                                <button class="report-tab" data-tab="by-status">
                                    <i class="fa-solid fa-list-check"></i> By Status
                                </button>
                                <button class="report-tab" data-tab="by-service">
                                    <i class="fa-solid fa-concierge-bell"></i> By Service
                                </button>
                                <button class="report-tab" data-tab="by-type">
                                    <i class="fa-solid fa-tag"></i> By Type
                                </button>
                                <button class="report-tab" data-tab="by-user">
                                    <i class="fa-solid fa-users"></i> By User
                                </button>
                            </div>

                            <!-- Overview Tab -->
                            <div id="tab-overview" class="tab-section active">
                                <div class="charts-grid">
                                    <div class="chart-card full-width">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-chart-area"></i> Signals Over Time</div>
                                            <div class="chart-actions">
                                                <button class="chart-action-btn" onclick="toggleChartType('timeline')" title="Toggle Chart Type">
                                                    <i class="fa-solid fa-chart-bar"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-timeline"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-chart-pie"></i> Status Distribution</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-status"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-anchor"></i> Signals by Dock</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-docks"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-flag"></i> Priority Breakdown</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-priority"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-user-shield"></i> Keeper Performance</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-keepers"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- By Dock Tab -->
                            <div id="tab-by-dock" class="tab-section">
                                <div class="charts-grid">
                                    <div class="chart-card full-width">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-anchor"></i> Signals by Dock</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-docks-detail"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-table-card">
                                    <div class="data-table-header">
                                        <div class="data-table-title">Dock Breakdown</div>
                                    </div>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Dock</th>
                                                <th>Total Signals</th>
                                                <th>Open</th>
                                                <th>Closed</th>
                                                <th>% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dock-table-body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- By Status Tab -->
                            <div id="tab-by-status" class="tab-section">
                                <div class="charts-grid">
                                    <div class="chart-card full-width">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-list-check"></i> Status Distribution</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-status-detail"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-table-card">
                                    <div class="data-table-header">
                                        <div class="data-table-title">Status Breakdown</div>
                                    </div>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th>Count</th>
                                                <th>% of Total</th>
                                                <th>Distribution</th>
                                            </tr>
                                        </thead>
                                        <tbody id="status-table-body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- By Service Tab -->
                            <div id="tab-by-service" class="tab-section">
                                <div class="charts-grid">
                                    <div class="chart-card full-width">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-concierge-bell"></i> Signals by Service</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-service"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-table-card">
                                    <div class="data-table-header">
                                        <div class="data-table-title">Service Breakdown</div>
                                    </div>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Service</th>
                                                <th>Total Signals</th>
                                                <th>Open</th>
                                                <th>Closed</th>
                                                <th>% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="service-table-body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- By Type Tab -->
                            <div id="tab-by-type" class="tab-section">
                                <div class="charts-grid">
                                    <div class="chart-card full-width">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-tag"></i> Signal Types Distribution</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-type"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-table-card">
                                    <div class="data-table-header">
                                        <div class="data-table-title">Signal Type Breakdown</div>
                                    </div>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Count</th>
                                                <th>% of Total</th>
                                                <th>Distribution</th>
                                            </tr>
                                        </thead>
                                        <tbody id="type-table-body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- By User Tab -->
                            <div id="tab-by-user" class="tab-section">
                                <div class="charts-grid">
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-users"></i> Top Signal Creators</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-users"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="chart-card">
                                        <div class="chart-header">
                                            <div class="chart-title"><i class="fa-solid fa-user-shield"></i> Keeper Performance</div>
                                        </div>
                                        <div class="chart-body">
                                            <div class="chart-container"><canvas id="chart-keepers-detail"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="data-table-card">
                                    <div class="data-table-header">
                                        <div class="data-table-title">Top Signal Creators</div>
                                    </div>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Signals Created</th>
                                                <th>% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="user-table-body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Detailed Table (always visible) -->
                            <div class="reports-table-card">
                                <div class="table-header">
                                    <div class="table-title">
                                        <i class="fa-solid fa-table"></i>
                                        Detailed Signal Report
                                    </div>
                                    <div class="table-search">
                                        <input type="text" id="table-search" placeholder="Search signals...">
                                        <select id="table-page-size" class="filter-control" style="width: auto; padding: 8px 14px;">
                                            <option value="10">10 per page</option>
                                            <option value="25" selected>25 per page</option>
                                            <option value="50">50 per page</option>
                                            <option value="100">100 per page</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="reports-table">
                                        <thead>
                                            <tr>
                                                <th data-sort="signal_number">Signal # <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="title">Title <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="dock_name">Dock <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="sea_state_name">Status <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="service_name">Service <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="signal_type">Type <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="sender_name">Created By <i class="fa-solid fa-sort"></i></th>
                                                <th data-sort="sent_date">Created <i class="fa-solid fa-sort"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody id="reports-table-body"></tbody>
                                    </table>
                                </div>
                                <div class="table-pagination">
                                    <div class="pagination-info" id="pagination-info">Showing 0 - 0 of 0 signals</div>
                                    <div class="pagination-buttons" id="pagination-buttons"></div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'templates/footer.php'; ?>
</main>

<script>
// Chart instances
let chartTimeline, chartStatus, chartDocks, chartPriority, chartKeepers;
let chartDocksDetail, chartStatusDetail, chartService, chartType, chartUsers, chartKeepersDetail;

// Current filters and pagination
let currentFilters = {
    start_date: '<?php echo $default_start; ?>',
    end_date: '<?php echo $default_end; ?>',
    dock_id: '',
    status: '',
    priority_id: '',
    service_id: '',
    user_ids: '',
    keeper_ids: '',
    search: ''
};

let currentPage = 1, pageSize = 25, sortColumn = 'sent_date', sortDirection = 'DESC', totalRecords = 0;

$(document).ready(function() {
    // Initialize Select2 for multi-select dropdowns
    $('#filter-users').select2({
        placeholder: 'Select users...',
        allowClear: true,
        width: '100%'
    });
    
    $('#filter-keepers').select2({
        placeholder: 'Select keepers...',
        allowClear: true,
        width: '100%'
    });
    
    initializeCharts();
    loadReportData();
    
    // Quick range buttons
    $('.quick-range-btn').on('click', function() {
        $('.quick-range-btn').removeClass('active');
        $(this).addClass('active');
        const range = $(this).data('range');
        const endDate = new Date();
        let startDate = new Date();
        if (range === 'all') { startDate = new Date('2020-01-01'); }
        else { startDate.setDate(endDate.getDate() - parseInt(range)); }
        $('#filter-start-date').val(formatDate(startDate));
        $('#filter-end-date').val(formatDate(endDate));
        applyFilters();
    });
    
    $('#apply-filters').on('click', applyFilters);
    $('#filter-dock, #filter-status, #filter-priority, #filter-service').on('change', applyFilters);
    $('#filter-users, #filter-keepers').on('change', applyFilters);
    $('#filter-start-date, #filter-end-date').on('change', function() {
        $('.quick-range-btn').removeClass('active');
        applyFilters();
    });
    
    // Table search
    let searchTimeout;
    $('#table-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentFilters.search = $('#table-search').val();
            currentPage = 1;
            loadTableData();
        }, 300);
    });
    
    $('#table-page-size').on('change', function() { pageSize = parseInt($(this).val()); currentPage = 1; loadTableData(); });
    
    // Table sorting
    $('.reports-table thead th[data-sort]').on('click', function() {
        const column = $(this).data('sort');
        if (sortColumn === column) { sortDirection = sortDirection === 'ASC' ? 'DESC' : 'ASC'; }
        else { sortColumn = column; sortDirection = 'ASC'; }
        $('.reports-table thead th').removeClass('sorted').find('i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).addClass('sorted').find('i').removeClass('fa-sort').addClass(sortDirection === 'ASC' ? 'fa-sort-up' : 'fa-sort-down');
        loadTableData();
    });
    
    // Report tabs
    $('.report-tab').on('click', function() {
        const tab = $(this).data('tab');
        $('.report-tab').removeClass('active');
        $(this).addClass('active');
        $('.tab-section').removeClass('active');
        $('#tab-' + tab).addClass('active');
        loadTabData(tab);
    });
});

function formatDate(date) { return date.toISOString().split('T')[0]; }

function applyFilters() {
    currentFilters.start_date = $('#filter-start-date').val();
    currentFilters.end_date = $('#filter-end-date').val();
    currentFilters.dock_id = $('#filter-dock').val();
    currentFilters.status = $('#filter-status').val();
    currentFilters.priority_id = $('#filter-priority').val();
    currentFilters.service_id = $('#filter-service').val();
    
    // Get Select2 multi-select values
    var selectedUsers = $('#filter-users').val();
    var selectedKeepers = $('#filter-keepers').val();
    
    // Convert arrays to comma-separated strings, handle null/undefined
    currentFilters.user_ids = (selectedUsers && selectedUsers.length > 0) ? selectedUsers.join(',') : '';
    currentFilters.keeper_ids = (selectedKeepers && selectedKeepers.length > 0) ? selectedKeepers.join(',') : '';
    
    // Debug logging - check browser console
    console.log('Applied Filters:', {
        user_ids: currentFilters.user_ids,
        keeper_ids: currentFilters.keeper_ids,
        selectedUsers: selectedUsers,
        selectedKeepers: selectedKeepers
    });
    
    currentPage = 1;
    loadReportData();
}

function loadReportData() {
    loadStats();
    loadChartData();
    loadTableData();
    const activeTab = $('.report-tab.active').data('tab');
    if (activeTab !== 'overview') { loadTabData(activeTab); }
}

function loadTabData(tab) {
    if (tab === 'by-dock') { loadDockDetails(); }
    else if (tab === 'by-status') { loadStatusDetails(); }
    else if (tab === 'by-service') { loadServiceDetails(); }
    else if (tab === 'by-type') { loadTypeDetails(); }
    else if (tab === 'by-user') { loadUserDetails(); }
}

function loadStats() {
    $.ajax({
        url: 'ajax/lighthouse_reports/get_report_data.php',
        method: 'GET',
        data: { type: 'stats', ...currentFilters },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#stat-total').text(stats.total || 0);
                $('#stat-open').text(stats.open || 0);
                $('#stat-closed').text(stats.closed || 0);
                $('#stat-high-priority').text(stats.high_priority || 0);
                const avgHours = parseFloat(stats.avg_resolution_hours) || 0;
                let timeDisplay = avgHours < 1 ? Math.round(avgHours * 60) + 'm' : (avgHours < 24 ? avgHours.toFixed(1) + 'h' : (avgHours / 24).toFixed(1) + 'd');
                $('#stat-avg-time').text(timeDisplay);
            } else {
                console.error('Stats Error:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Stats AJAX Error:', status, error, xhr.responseText);
        }
    });
}

function initializeCharts() {
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, font: { size: 12 } } } }
    };
    
    chartTimeline = new Chart(document.getElementById('chart-timeline'), {
        type: 'line',
        data: { labels: [], datasets: [
            { label: 'Created', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.4 },
            { label: 'Closed', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 }
        ]},
        options: { ...defaultOptions, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f3f4f6' } } } }
    });
    
    chartStatus = new Chart(document.getElementById('chart-status'), { type: 'doughnut', data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderWidth: 0 }] }, options: { ...defaultOptions, cutout: '65%' } });
    chartDocks = new Chart(document.getElementById('chart-docks'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Signals', data: [], backgroundColor: [], borderRadius: 6 }] }, options: { ...defaultOptions, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } } });
    chartPriority = new Chart(document.getElementById('chart-priority'), { type: 'polarArea', data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] }, options: { ...defaultOptions, scales: { r: { beginAtZero: true } } } });
    chartKeepers = new Chart(document.getElementById('chart-keepers'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Assigned', data: [], backgroundColor: '#3b82f6', borderRadius: 6 }, { label: 'Closed', data: [], backgroundColor: '#10b981', borderRadius: 6 }] }, options: { ...defaultOptions, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } } });
    
    // Detail charts
    chartDocksDetail = new Chart(document.getElementById('chart-docks-detail'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Open', data: [], backgroundColor: '#3b82f6', borderRadius: 6 }, { label: 'Closed', data: [], backgroundColor: '#10b981', borderRadius: 6 }] }, options: { ...defaultOptions, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } } });
    chartStatusDetail = new Chart(document.getElementById('chart-status-detail'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Signals', data: [], backgroundColor: [], borderRadius: 6 }] }, options: { ...defaultOptions, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } } });
    chartService = new Chart(document.getElementById('chart-service'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Open', data: [], backgroundColor: '#3b82f6', borderRadius: 6 }, { label: 'Closed', data: [], backgroundColor: '#10b981', borderRadius: 6 }] }, options: { ...defaultOptions, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } } });
    chartType = new Chart(document.getElementById('chart-type'), { type: 'doughnut', data: { labels: [], datasets: [{ data: [], backgroundColor: ['#f59e0b', '#3b82f6', '#ef4444', '#6b7280'], borderWidth: 0 }] }, options: { ...defaultOptions, cutout: '65%' } });
    chartUsers = new Chart(document.getElementById('chart-users'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Signals Created', data: [], backgroundColor: '#8b5cf6', borderRadius: 6 }] }, options: { ...defaultOptions, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } } });
    chartKeepersDetail = new Chart(document.getElementById('chart-keepers-detail'), { type: 'bar', data: { labels: [], datasets: [{ label: 'Assigned', data: [], backgroundColor: '#3b82f6', borderRadius: 6 }, { label: 'Closed', data: [], backgroundColor: '#10b981', borderRadius: 6 }] }, options: { ...defaultOptions, scales: { x: { grid: { display: false } }, y: { beginAtZero: true } } } });
}

function loadChartData() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'timeline', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartTimeline.data.labels = r.data.labels; chartTimeline.data.datasets[0].data = r.data.created; chartTimeline.data.datasets[1].data = r.data.closed; chartTimeline.update(); } }
    });
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_status', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartStatus.data.labels = r.data.labels; chartStatus.data.datasets[0].data = r.data.values; chartStatus.data.datasets[0].backgroundColor = r.data.colors; chartStatus.update(); } }
    });
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_dock', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartDocks.data.labels = r.data.labels; chartDocks.data.datasets[0].data = r.data.values; chartDocks.data.datasets[0].backgroundColor = r.data.colors; chartDocks.update(); } }
    });
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_priority', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartPriority.data.labels = r.data.labels; chartPriority.data.datasets[0].data = r.data.values; chartPriority.data.datasets[0].backgroundColor = r.data.colors; chartPriority.update(); } }
    });
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_keeper', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartKeepers.data.labels = r.data.labels; chartKeepers.data.datasets[0].data = r.data.assigned; chartKeepers.data.datasets[1].data = r.data.closed; chartKeepers.update(); } }
    });
}

function loadDockDetails() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_dock_detail', ...currentFilters }, dataType: 'json',
        success: function(r) {
            if (r.success) {
                chartDocksDetail.data.labels = r.data.labels;
                chartDocksDetail.data.datasets[0].data = r.data.open;
                chartDocksDetail.data.datasets[1].data = r.data.closed;
                chartDocksDetail.update();
                let html = '', total = r.data.values.reduce((a,b) => a+b, 0);
                r.data.labels.forEach((label, i) => {
                    const pct = total > 0 ? ((r.data.values[i] / total) * 100).toFixed(1) : 0;
                    html += `<tr><td><span class="color-dot" style="background:${r.data.colors[i]}"></span>${escapeHtml(label)}</td><td>${r.data.values[i]}</td><td>${r.data.open[i]}</td><td>${r.data.closed[i]}</td><td>${pct}%</td></tr>`;
                });
                $('#dock-table-body').html(html || '<tr><td colspan="5" class="text-center">No data</td></tr>');
            }
        }
    });
}

function loadStatusDetails() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_status', ...currentFilters }, dataType: 'json',
        success: function(r) {
            if (r.success) {
                chartStatusDetail.data.labels = r.data.labels;
                chartStatusDetail.data.datasets[0].data = r.data.values;
                chartStatusDetail.data.datasets[0].backgroundColor = r.data.colors;
                chartStatusDetail.update();
                let html = '', total = r.data.values.reduce((a,b) => a+b, 0);
                r.data.labels.forEach((label, i) => {
                    const pct = total > 0 ? ((r.data.values[i] / total) * 100).toFixed(1) : 0;
                    html += `<tr><td><span class="color-dot" style="background:${r.data.colors[i]}"></span>${escapeHtml(label)}</td><td>${r.data.values[i]}</td><td>${pct}%</td><td><div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width:${pct}%;background:${r.data.colors[i]}"></div></div></td></tr>`;
                });
                $('#status-table-body').html(html || '<tr><td colspan="4" class="text-center">No data</td></tr>');
            }
        }
    });
}

function loadServiceDetails() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_service', ...currentFilters }, dataType: 'json',
        success: function(r) {
            if (r.success) {
                chartService.data.labels = r.data.labels;
                chartService.data.datasets[0].data = r.data.open;
                chartService.data.datasets[1].data = r.data.closed;
                chartService.update();
                let html = '', total = r.data.values.reduce((a,b) => a+b, 0);
                r.data.labels.forEach((label, i) => {
                    const pct = total > 0 ? ((r.data.values[i] / total) * 100).toFixed(1) : 0;
                    html += `<tr><td><span class="color-dot" style="background:${r.data.colors[i]}"></span>${escapeHtml(label)}</td><td>${r.data.values[i]}</td><td>${r.data.open[i]}</td><td>${r.data.closed[i]}</td><td>${pct}%</td></tr>`;
                });
                $('#service-table-body').html(html || '<tr><td colspan="5" class="text-center">No data</td></tr>');
            }
        }
    });
}

function loadTypeDetails() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_signal_type', ...currentFilters }, dataType: 'json',
        success: function(r) {
            if (r.success) {
                chartType.data.labels = r.data.labels;
                chartType.data.datasets[0].data = r.data.values;
                chartType.data.datasets[0].backgroundColor = r.data.colors;
                chartType.update();
                let html = '', total = r.data.values.reduce((a,b) => a+b, 0);
                r.data.labels.forEach((label, i) => {
                    const pct = total > 0 ? ((r.data.values[i] / total) * 100).toFixed(1) : 0;
                    html += `<tr><td><span class="type-badge ${label.toLowerCase().replace(' ', '_')}">${escapeHtml(label)}</span></td><td>${r.data.values[i]}</td><td>${pct}%</td><td><div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width:${pct}%;background:${r.data.colors[i]}"></div></div></td></tr>`;
                });
                $('#type-table-body').html(html || '<tr><td colspan="4" class="text-center">No data</td></tr>');
            }
        }
    });
}

function loadUserDetails() {
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_user', ...currentFilters }, dataType: 'json',
        success: function(r) {
            if (r.success) {
                chartUsers.data.labels = r.data.labels;
                chartUsers.data.datasets[0].data = r.data.values;
                chartUsers.update();
                let html = '', total = r.data.values.reduce((a,b) => a+b, 0);
                r.data.labels.forEach((label, i) => {
                    const pct = total > 0 ? ((r.data.values[i] / total) * 100).toFixed(1) : 0;
                    html += `<tr><td><div class="user-cell"><div class="user-avatar">${getInitials(label)}</div><span class="user-name">${escapeHtml(label)}</span></div></td><td>${r.data.values[i]}</td><td>${pct}%</td></tr>`;
                });
                $('#user-table-body').html(html || '<tr><td colspan="3" class="text-center">No data</td></tr>');
            }
        }
    });
    $.ajax({ url: 'ajax/lighthouse_reports/get_report_data.php', method: 'GET', data: { type: 'by_keeper', ...currentFilters }, dataType: 'json',
        success: function(r) { if (r.success) { chartKeepersDetail.data.labels = r.data.labels; chartKeepersDetail.data.datasets[0].data = r.data.assigned; chartKeepersDetail.data.datasets[1].data = r.data.closed; chartKeepersDetail.update(); } }
    });
}

function loadTableData() {
    $.ajax({
        url: 'ajax/lighthouse_reports/get_report_data.php',
        method: 'GET',
        data: { type: 'table', page: currentPage, page_size: pageSize, sort_column: sortColumn, sort_direction: sortDirection, ...currentFilters },
        dataType: 'json',
        success: function(response) { 
            if (response.success) { 
                totalRecords = response.total; 
                renderTable(response.data); 
                renderPagination(); 
            } else {
                console.error('Table Error:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Table AJAX Error:', status, error, xhr.responseText);
        }
    });
}

function renderTable(signals) {
    let html = '';
    if (signals.length === 0) {
        html = '<tr><td colspan="8" class="text-center py-5"><div class="no-data"><i class="fa-solid fa-inbox"></i><p>No signals found</p></div></td></tr>';
    } else {
        signals.forEach(function(s) {
            const statusStyle = 'background:' + s.sea_state_color + '20;color:' + s.sea_state_color;
            const dockStyle = s.dock_color ? 'background:' + s.dock_color + '15;color:' + s.dock_color : '';
            const typeClass = (s.signal_type || 'other').toLowerCase().replace(' ', '_');
            const typeLabel = formatType(s.signal_type);
            html += `<tr onclick="window.location='lighthouse_keeper_view.php?id=${s.signal_id}'" style="cursor:pointer;">`;
            html += `<td><strong>${escapeHtml(s.signal_number)}</strong></td>`;
            html += `<td>${escapeHtml(truncate(s.title, 35))}</td>`;
            html += `<td>${s.dock_name ? `<span class="dock-badge" style="${dockStyle}"><i class="${s.dock_icon || 'fa-solid fa-anchor'}"></i> ${escapeHtml(s.dock_name)}</span>` : '<span class="text-muted">—</span>'}</td>`;
            html += `<td><span class="status-badge" style="${statusStyle}"><i class="${s.sea_state_icon || 'fa-solid fa-circle'}"></i> ${escapeHtml(s.sea_state_name)}</span></td>`;
            html += `<td>${s.service_name ? `<span class="dock-badge" style="background:${s.service_color}15;color:${s.service_color}"><i class="${s.service_icon || 'fa-solid fa-concierge-bell'}"></i> ${escapeHtml(s.service_name)}</span>` : '<span class="text-muted">—</span>'}</td>`;
            html += `<td><span class="type-badge ${typeClass}">${typeLabel}</span></td>`;
            html += `<td><div class="user-cell"><div class="user-avatar">${getInitials(s.sender_name)}</div><span class="user-name">${escapeHtml(s.sender_name)}</span></div></td>`;
            html += `<td>${s.sent_date_formatted}</td>`;
            html += `</tr>`;
        });
    }
    $('#reports-table-body').html(html);
}

function formatType(type) {
    if (!type) return 'Other';
    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function renderPagination() {
    const totalPages = Math.ceil(totalRecords / pageSize);
    const start = (currentPage - 1) * pageSize + 1;
    const end = Math.min(currentPage * pageSize, totalRecords);
    $('#pagination-info').text('Showing ' + (totalRecords > 0 ? start : 0) + ' - ' + end + ' of ' + totalRecords + ' signals');
    let html = `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
    let startPage = Math.max(1, currentPage - 2), endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);
    if (startPage > 1) { html += '<button class="page-btn" onclick="goToPage(1)">1</button>'; if (startPage > 2) html += '<span class="page-btn" style="border:none">...</span>'; }
    for (let i = startPage; i <= endPage; i++) { html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`; }
    if (endPage < totalPages) { if (endPage < totalPages - 1) html += '<span class="page-btn" style="border:none">...</span>'; html += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`; }
    html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    $('#pagination-buttons').html(html);
}

function goToPage(page) { if (page < 1 || page > Math.ceil(totalRecords / pageSize)) return; currentPage = page; loadTableData(); }
function toggleChartType(name) { if (name === 'timeline') { chartTimeline.config.type = chartTimeline.config.type === 'line' ? 'bar' : 'line'; chartTimeline.update(); } }
function exportReport(format) { window.open('ajax/lighthouse_reports/export_report.php?' + new URLSearchParams({ format, ...currentFilters }).toString(), '_blank'); }
function escapeHtml(text) { if (!text) return ''; const d = document.createElement('div'); d.textContent = text; return d.innerHTML; }
function truncate(str, len) { return !str ? '' : (str.length > len ? str.substring(0, len) + '...' : str); }
function getInitials(name) { if (!name) return '?'; const p = name.split(' '); return p.length >= 2 ? (p[0][0] + p[p.length-1][0]).toUpperCase() : name.substring(0,2).toUpperCase(); }
</script>