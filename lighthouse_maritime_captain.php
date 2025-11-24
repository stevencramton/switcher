<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_captain')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', "Captain's Log");
include 'templates/header.php';
?>

<script> $(document).ready(function(){ $(".page-wrapper").addClass("pinned"); }); </script>

<style>
.page-header {
    background: white;
    border-radius: 6px;
    padding: 20px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title i {
    color: #8b5a2b;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-export:hover {
    background: #2563eb;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-refresh {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-refresh:hover {
    background: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Filters Section */
.filters-container {
    background: white;
    border-radius: 6px;
    padding: 20px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.filters-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    min-width: 160px;
    background: white;
    color: #1f2937;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-group input[type="date"] {
    min-width: 140px;
}

.btn-filter {
    padding: 8px 16px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-filter:hover {
    background: #4f46e5;
}

.btn-clear {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-clear:hover {
    background: #e5e7eb;
    color: #1f2937;
}

/* Log Container */
.log-container {
    background: white;
    border-radius: 6px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.results-info {
    font-size: 13px;
    color: #6b7280;
}

/* Log Entry Styles */
.log-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.log-entry {
    display: flex;
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    transition: all 0.2s;
}

.log-entry:hover {
    background: #ffffff;
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.log-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 18px;
    color: white;
}

.log-icon.signal { background: #3b82f6; }
.log-icon.configuration { background: #8b5cf6; }
.log-icon.authentication { background: #10b981; }
.log-icon.system { background: #6b7280; }
.log-icon.error { background: #ef4444; }

.log-content {
    flex: 1;
    min-width: 0;
}

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.log-event-type {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
}

.log-timestamp {
    font-size: 12px;
    color: #9ca3af;
    white-space: nowrap;
}

.log-description {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 8px;
}

.log-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 12px;
    color: #6b7280;
}

.log-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.log-meta-item i {
    font-size: 11px;
    color: #9ca3af;
}

.log-reference {
    background: #dbeafe;
    color: #1d4ed8;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
}

.log-reference:hover {
    background: #bfdbfe;
}

.log-changes {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #e5e7eb;
}

.log-change-old {
    background: #fef2f2;
    color: #991b1b;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: line-through;
}

.log-change-arrow {
    color: #9ca3af;
    font-size: 12px;
}

.log-change-new {
    background: #ecfdf5;
    color: #065f46;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
    color: #9ca3af;
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    flex-wrap: wrap;
    gap: 16px;
}

.pagination-info {
    font-size: 14px;
    color: #6b7280;
}

.pagination-controls {
    display: flex;
    gap: 8px;
}

.pagination-btn {
    padding: 8px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
    background: #f9fafb;
    border-color: #d1d5db;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

/* Loading State */
.loading-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6b7280;
}

.loading-overlay i {
    font-size: 32px;
    margin-right: 12px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Stats Cards */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.stat-icon.green { background: linear-gradient(135deg, #10b981, #047857); }
.stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }

.stat-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    line-height: 1;
}

.stat-content p {
    font-size: 13px;
    color: #6b7280;
    margin: 4px 0 0 0;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filters-row {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
    }
    
    .log-entry {
        flex-direction: column;
    }
    
    .log-header {
        flex-direction: column;
    }
}
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
                        
        <!-- Page Header -->
        <div class="page-header mb-4">
            <h1 class="page-title">
                <i class="fa-solid fa-scroll"></i>
                Captain's Log
            </h1>
            <div class="header-actions">
                <button class="btn-refresh" onclick="clRefreshLog()">
                    <i class="fa-solid fa-rotate"></i> Refresh
                </button>
                <button class="btn-export" onclick="clExportLog()">
                    <i class="fa-solid fa-download"></i> Export CSV
                </button>
            </div>
        </div>
        
        <!-- Statistics Row -->
        <div class="stats-row" id="cl-stats-row">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-list"></i>
                </div>
                <div class="stat-content">
                    <h3 id="stat-total">0</h3>
                    <p>Total Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fa-solid fa-signal"></i>
                </div>
                <div class="stat-content">
                    <h3 id="stat-signals">0</h3>
                    <p>Signal Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-cog"></i>
                </div>
                <div class="stat-content">
                    <h3 id="stat-config">0</h3>
                    <p>Config Changes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3 id="stat-today">0</h3>
                    <p>Today's Events</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-container">
            <div class="filters-row">
                <div class="filter-group">
                    <label>Event Category</label>
                    <select id="cl-filter-category">
                        <option value="">All Categories</option>
                        <option value="signal">Signal Events</option>
                        <option value="configuration">Configuration</option>
                        <option value="authentication">Authentication</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Event Type</label>
                    <select id="cl-filter-type">
                        <option value="">All Types</option>
                        <optgroup label="Signal Events">
                            <option value="signal_created">Signal Created</option>
                            <option value="signal_updated">Signal Updated</option>
                            <option value="signal_deleted">Signal Deleted</option>
                            <option value="status_changed">Status Changed</option>
                            <option value="priority_changed">Priority Changed</option>
                            <option value="dock_changed">Dock Changed</option>
                            <option value="keeper_assigned">Keeper Assigned</option>
                            <option value="keeper_unassigned">Keeper Unassigned</option>
                            <option value="update_added">Update Added</option>
                            <option value="comment_added">Comment Added</option>
                            <option value="attachment_uploaded">Attachment Uploaded</option>
                        </optgroup>
                        <optgroup label="Bulk Operations">
                            <option value="bulk_status_change">Bulk Status Change</option>
                            <option value="bulk_assignment">Bulk Assignment</option>
                            <option value="bulk_dock_move">Bulk Dock Move</option>
                            <option value="bulk_delete">Bulk Delete</option>
                        </optgroup>
                        <optgroup label="Configuration">
                            <option value="dock_created">Dock Created</option>
                            <option value="dock_updated">Dock Updated</option>
                            <option value="dock_deleted">Dock Deleted</option>
                            <option value="sea_state_created">Sea State Created</option>
                            <option value="sea_state_updated">Sea State Updated</option>
                            <option value="priority_created">Priority Created</option>
                            <option value="priority_updated">Priority Updated</option>
                            <option value="service_created">Service Created</option>
                            <option value="service_updated">Service Updated</option>
                        </optgroup>
                        <optgroup label="Authentication">
                            <option value="user_login">User Login</option>
                            <option value="user_logout">User Logout</option>
                            <option value="user_login_failed">Login Failed</option>
                        </optgroup>
                    </select>
                </div>
                <div class="filter-group">
                    <label>User</label>
                    <select id="cl-filter-user">
                        <option value="">All Users</option>
                        <!-- Populated dynamically -->
                    </select>
                </div>
                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" id="cl-filter-from">
                </div>
                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" id="cl-filter-to">
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="cl-filter-search" placeholder="Search logs...">
                </div>
                <div class="filter-group" style="flex-direction: row; gap: 8px; align-items: flex-end;">
                    <button class="btn-filter" onclick="clApplyFilters()">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <button class="btn-clear" onclick="clClearFilters()">
                        <i class="fa-solid fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Log Container -->
        <div class="log-container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fa-solid fa-clipboard-list me-2"></i>Activity Log
                </h2>
                <span class="results-info" id="cl-results-info">Loading...</span>
            </div>
            
            <div id="cl-log-list" class="log-list">
                <div class="loading-overlay">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Loading captain's log...</span>
                </div>
            </div>
            
            <div class="pagination-container" id="cl-pagination" style="display: none;">
                <div class="pagination-info" id="cl-pagination-info"></div>
                <div class="pagination-controls" id="cl-pagination-controls"></div>
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
// Captain's Log State
let clCurrentPage = 1;
let clPerPage = 25;
let clTotalPages = 1;
let clTotalRecords = 0;

// Get event icon based on category
function clGetEventIcon(category) {
    const icons = {
        'signal': 'fa-solid fa-signal',
        'configuration': 'fa-solid fa-cog',
        'authentication': 'fa-solid fa-shield-halved',
        'system': 'fa-solid fa-server',
        'error': 'fa-solid fa-exclamation-triangle'
    };
    return icons[category] || 'fa-solid fa-circle';
}

// Format event type for display
function clFormatEventType(eventType) {
    return eventType
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

// Format relative time
function clFormatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSecs < 60) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
        hour: 'numeric',
        minute: '2-digit'
    });
}

// Build log entry HTML
function clBuildLogEntry(entry) {
    const icon = clGetEventIcon(entry.event_category);
    const eventTypeDisplay = clFormatEventType(entry.event_type);
    const relativeTime = clFormatRelativeTime(entry.created_date);
    
    let referenceHtml = '';
    if (entry.entity_reference) {
        if (entry.entity_type === 'signal' && entry.entity_id) {
            referenceHtml = `<a href="lighthouse_keeper_view.php?id=${entry.entity_id}" class="log-reference">${clEscapeHtml(entry.entity_reference)}</a>`;
        } else {
            referenceHtml = `<span class="log-reference">${clEscapeHtml(entry.entity_reference)}</span>`;
        }
    }
    
    let changesHtml = '';
    if (entry.old_value && entry.new_value && entry.old_value !== entry.new_value) {
        changesHtml = `
            <div class="log-changes">
                <span class="log-change-old">${clEscapeHtml(clTruncate(entry.old_value, 50))}</span>
                <span class="log-change-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                <span class="log-change-new">${clEscapeHtml(clTruncate(entry.new_value, 50))}</span>
            </div>
        `;
    }
    
    let description = entry.new_value || entry.old_value || '';
    if (entry.details) {
        try {
            const details = JSON.parse(entry.details);
            if (details.title) description = details.title;
            if (details.count) description += ` (${details.count} items)`;
        } catch (e) {
            // Use new_value as fallback
        }
    }
    
    return `
        <div class="log-entry">
            <div class="log-icon ${entry.event_category}">
                <i class="${icon}"></i>
            </div>
            <div class="log-content">
                <div class="log-header">
                    <span class="log-event-type">${eventTypeDisplay}</span>
                    <span class="log-timestamp" title="${entry.created_date_formatted}">${relativeTime}</span>
                </div>
                <div class="log-description">
                    ${clEscapeHtml(clTruncate(description, 150))}
                </div>
                <div class="log-meta">
                    <span class="log-meta-item">
                        <i class="fa-solid fa-user"></i>
                        ${clEscapeHtml(entry.user_name || 'System')}
                    </span>
                    ${entry.entity_type ? `
                        <span class="log-meta-item">
                            <i class="fa-solid fa-tag"></i>
                            ${clEscapeHtml(entry.entity_type)}
                        </span>
                    ` : ''}
                    ${referenceHtml ? `
                        <span class="log-meta-item">
                            ${referenceHtml}
                        </span>
                    ` : ''}
                    ${entry.ip_address ? `
                        <span class="log-meta-item">
                            <i class="fa-solid fa-globe"></i>
                            ${clEscapeHtml(entry.ip_address)}
                        </span>
                    ` : ''}
                </div>
                ${changesHtml}
            </div>
        </div>
    `;
}

// Build empty state
function clBuildEmptyState() {
    return `
        <div class="empty-state">
            <i class="fa-solid fa-scroll"></i>
            <h3>No Log Entries Found</h3>
            <p>No activity matching your filters has been recorded yet.</p>
        </div>
    `;
}

// Build pagination
function clBuildPagination() {
    if (clTotalPages <= 1) {
        $('#cl-pagination').hide();
        return;
    }
    
    $('#cl-pagination').show();
    $('#cl-pagination-info').text(`Showing ${((clCurrentPage - 1) * clPerPage) + 1}-${Math.min(clCurrentPage * clPerPage, clTotalRecords)} of ${clTotalRecords} entries`);
    
    let html = '';
    
    // Previous button
    html += `<button class="pagination-btn" onclick="clGoToPage(${clCurrentPage - 1})" ${clCurrentPage === 1 ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-left"></i>
    </button>`;
    
    // Page numbers
    const startPage = Math.max(1, clCurrentPage - 2);
    const endPage = Math.min(clTotalPages, clCurrentPage + 2);
    
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="clGoToPage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-btn" style="border: none; cursor: default;">...</span>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === clCurrentPage ? 'active' : ''}" onclick="clGoToPage(${i})">${i}</button>`;
    }
    
    if (endPage < clTotalPages) {
        if (endPage < clTotalPages - 1) {
            html += `<span class="pagination-btn" style="border: none; cursor: default;">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="clGoToPage(${clTotalPages})">${clTotalPages}</button>`;
    }
    
    // Next button
    html += `<button class="pagination-btn" onclick="clGoToPage(${clCurrentPage + 1})" ${clCurrentPage === clTotalPages ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-right"></i>
    </button>`;
    
    $('#cl-pagination-controls').html(html);
}

// Go to page
function clGoToPage(page) {
    if (page < 1 || page > clTotalPages) return;
    clCurrentPage = page;
    clLoadLog();
}

// Load log entries
function clLoadLog() {
    const params = new URLSearchParams({
        page: clCurrentPage,
        per_page: clPerPage,
        category: $('#cl-filter-category').val(),
        event_type: $('#cl-filter-type').val(),
        user_id: $('#cl-filter-user').val(),
        from_date: $('#cl-filter-from').val(),
        to_date: $('#cl-filter-to').val(),
        search: $('#cl-filter-search').val()
    });
    
    $('#cl-log-list').html(`
        <div class="loading-overlay">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Loading captain's log...</span>
        </div>
    `);
    
    $.ajax({
        url: 'ajax/lh_captains_log/read_captains_log.php?' + params.toString(),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                clTotalRecords = response.total;
                clTotalPages = response.total_pages;
                
                // Update stats
                if (response.stats) {
                    $('#stat-total').text(response.stats.total || 0);
                    $('#stat-signals').text(response.stats.signals || 0);
                    $('#stat-config').text(response.stats.config || 0);
                    $('#stat-today').text(response.stats.today || 0);
                }
                
                // Update results info
                $('#cl-results-info').text(`${clTotalRecords} entries found`);
                
                if (response.data && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(entry => {
                        html += clBuildLogEntry(entry);
                    });
                    $('#cl-log-list').html(html);
                } else {
                    $('#cl-log-list').html(clBuildEmptyState());
                }
                
                clBuildPagination();
            } else {
                $('#cl-log-list').html(`
                    <div class="empty-state">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <h3>Error Loading Log</h3>
                        <p>${response.message || 'An unexpected error occurred.'}</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load captain\'s log:', error);
            $('#cl-log-list').html(`
                <div class="empty-state">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <h3>Connection Error</h3>
                    <p>Failed to connect to the server. Please try again.</p>
                </div>
            `);
        }
    });
}

// Load users for filter
function clLoadUsers() {
    $.ajax({
        url: 'ajax/lh_captains_log/get_log_users.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                let html = '<option value="">All Users</option>';
                response.data.forEach(user => {
                    html += `<option value="${user.id}">${clEscapeHtml(user.name)}</option>`;
                });
                $('#cl-filter-user').html(html);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load users:', error);
        }
    });
}

// Apply filters
function clApplyFilters() {
    clCurrentPage = 1;
    clLoadLog();
}

// Clear filters
function clClearFilters() {
    $('#cl-filter-category').val('');
    $('#cl-filter-type').val('');
    $('#cl-filter-user').val('');
    $('#cl-filter-from').val('');
    $('#cl-filter-to').val('');
    $('#cl-filter-search').val('');
    clCurrentPage = 1;
    clLoadLog();
}

// Refresh log
function clRefreshLog() {
    clLoadLog();
}

// Export log to CSV
function clExportLog() {
    const params = new URLSearchParams({
        category: $('#cl-filter-category').val(),
        event_type: $('#cl-filter-type').val(),
        user_id: $('#cl-filter-user').val(),
        from_date: $('#cl-filter-from').val(),
        to_date: $('#cl-filter-to').val(),
        search: $('#cl-filter-search').val(),
        export: 'csv'
    });
    
    window.location.href = 'ajax/lh_captains_log/export_captains_log.php?' + params.toString();
}

// Utility functions
function clEscapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function clTruncate(str, maxLength) {
    if (!str) return '';
    if (str.length <= maxLength) return str;
    return str.substring(0, maxLength) + '...';
}

// Initialize
$(document).ready(function() {
    console.log("Captain's Log: Initializing...");
    
    // Load initial data
    clLoadUsers();
    clLoadLog();
    
    // Enter key to apply filters
    $('#cl-filter-search').on('keypress', function(e) {
        if (e.which === 13) {
            clApplyFilters();
        }
    });
    
    // Auto-apply category filter when changed
    $('#cl-filter-category').on('change', function() {
        clApplyFilters();
    });
});
</script>