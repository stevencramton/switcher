<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_maritime')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', 'Maritime Docks');
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
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.btn-add-dock {
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

.btn-add-dock:hover {
    background: #059669;
   	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.search-dock-container {
    background: white;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.search-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-wrapper input {
    flex: 1;
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
    background: #f9fafb;
    transition: all 0.2s;
}

.search-wrapper input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-wrapper button {
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-wrapper button:hover {
    background: #2563eb;
}

.search-wrapper button.clear-mode {
    background: #E91E63;
}

.search-wrapper button.clear-mode:hover {
    background: #d71a5b;
}

.search-results-info {
    font-size: 14px;
    color: #6b7280;
}

.search-results-info strong {
    color: #1f2937;
    font-weight: 600;
}

.docks-container {
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

.drag-hint {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
}

.docks-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dock-item {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
 	transition: all 0.2s ease;
    position: relative;
}

.dock-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #ffffff;
}

.dock-item.ui-sortable-helper {
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    background: white;
    opacity: 0.95;
    cursor: grabbing !important;
    transform: scale(1.02);
    z-index: 1000;
}

.dock-item.ui-sortable-placeholder {
    border: 2px dashed #3b82f6;
    background: linear-gradient(135deg, #eff6ff 25%, transparent 25%, transparent 50%, #eff6ff 50%, #eff6ff 75%, transparent 75%, transparent);
    background-size: 20px 20px;
    visibility: visible !important;
    height: 80px;
    opacity: 0.8;
}

.dock-item.ui-sortable-placeholder .drag-handle,
.dock-item.ui-sortable-placeholder .order-number,
.dock-item.ui-sortable-placeholder .dock-icon-display,
.dock-item.ui-sortable-placeholder .dock-info,
.dock-item.ui-sortable-placeholder .dock-meta {
    visibility: hidden;
}

.drag-handle {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    cursor: grab;
    font-size: 18px;
    transition: all 0.2s;
    border-radius: 4px;
}

.drag-handle:hover {
    color: #3b82f6;
    background: #eff6ff;
}

.drag-handle:active {
    cursor: grabbing;
    color: #2563eb;
}

.dock-icon-display {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.dock-info {
    flex: 1;
    min-width: 0;
}

.dock-name {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.default-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #78350f;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
}

.default-badge i {
    font-size: 10px;
}

.dock-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.dock-meta {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-shrink: 0;
}

.status-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    background: #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s;
}

.toggle-switch.active {
    background: #10b981;
}

.toggle-switch::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch.active::before {
    left: 22px;
}

.status-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
}

.status-toggle.active .status-label {
    color: #10b981;
}

.dock-actions {
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: white;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.btn-action:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #1f2937;
}

.btn-action.default:hover {
    background: #fffbeb;
    border-color: #fcd34d;
    color: #d97706;
}

.btn-action.default.is-default {
    background: #fef3c7;
    border-color: #fbbf24;
    color: #d97706;
}

.btn-action.delete:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h4 {
    font-size: 18px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.modal-content {
    border-radius: 8px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 24px;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
}

.modal-body {
    padding: 24px;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.form-control:focus,
.form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.color-preview {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.input-group-text {
    border-color: #d1d5db;
    background: #f9fafb;
    color: #6b7280;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
}

.input-group-text:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.form-check-input:checked {
    background-color: #10b981;
    border-color: #10b981;
}

.order-number {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

#lh-save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    z-index: 10000;
    font-weight: 600;
}

/* Default dock info box */
.default-dock-info {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fcd34d;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.default-dock-info i {
    font-size: 20px;
    color: #d97706;
}

.default-dock-info-text {
    flex: 1;
}

.default-dock-info-text strong {
    color: #92400e;
}

.default-dock-info-text p {
    margin: 0;
    font-size: 13px;
    color: #78350f;
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
        <div class="page-header mb-3" id="lh-page-header">
	        <div>
	            <h1 class="page-title">
	                <i class="fa-solid fa-anchor" style="color: #4f6e8c;"></i> Manage Docks (Departments)
	            </h1>
	            <p class="mb-0 mt-1" style="font-size: 14px; color: #6b7280;">
	                Configure the docks within the lighthouse system
	            </p>
	        </div>
			<button type="button" class="btn-add-dock" data-bs-toggle="modal" data-bs-target="#lh-modal" onclick="lhOpenAddModal()">
                <i class="fa-solid fa-plus"></i>
                Add Dock
            </button>
        </div>
        
        <!-- Search/Filter -->
        <div class="search-dock-container">
            <div class="search-wrapper">
                <input type="text" id="docks-search" placeholder="Search docks by name or description..." autocomplete="off">
                <button type="button" id="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>
            <div id="search-results-info" class="search-results-info mt-2" style="display: none;">
                Showing <strong id="docks-count">0</strong> of <strong id="total-docks">0</strong> docks
            </div>
        </div>
        
        <!-- Docks List -->
        <div class="docks-container" id="lh-docks-wrapper">
            <div class="section-header">
	            <h3 class="section-title">
	                <i class="fa-solid fa-list-ol" style="color: #6b7280;"></i> Docks
	            </h3>
                <div class="drag-hint">
                    <i class="fa-solid fa-grip-vertical"></i>
                    <span>Drag to reorder</span>
                </div>
            </div>
            
            <!-- Default Dock Info Box -->
            <div id="lh-default-dock-info" class="default-dock-info" style="display: none;">
                <i class="fa-solid fa-star"></i>
                <div class="default-dock-info-text">
                    <strong>Default Dock:</strong> <span id="lh-default-dock-name">None</span>
                    <p>New signals from the Harbor will be automatically assigned to this dock.</p>
                </div>
            </div>
            
            <ul id="lh-docks-list" class="docks-list"></ul>
            
            <div id="lh-empty-state" class="empty-state" style="display: none;">
                <i class="fa-solid fa-anchor"></i>
                <h4>No Docks Yet</h4>
                <p>Create your first dock to get started organizing signals</p>
            </div>
        </div>
        
        <!-- Save Indicator -->
        <div id="lh-save-indicator">
            <i class="fa-solid fa-check-circle me-2"></i>Order saved successfully
        </div>
        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</main>

<!-- Add/Edit Dock Modal -->
<div class="modal fade" id="lh-modal" tabindex="-1" aria-labelledby="lh-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lh-modal-title">
                    <i class="fa-solid fa-plus"></i> Add Dock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="lh-alert"></div>
                
                <form id="lh-form">
                    <input type="hidden" id="lh-dock-id" name="dock_id">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="lh-name" class="form-label">
                                    Dock Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="lh-name" name="dock_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="lh-description" class="form-label">Description</label>
                                <textarea class="form-control" id="lh-description" name="dock_description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lh-color" class="form-label">
                                            Color <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="lh-color" name="dock_color" value="#007bff" required>
                                            <input type="text" class="form-control" id="lh-color-hex" value="#007bff" readonly style="max-width: 100px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lh-icon" class="form-label">
                                            Icon <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="lh-icon-display">
                                                <i class="fa-solid fa-folder"></i>
                                            </span>
                                            <input type="text" class="form-control iconpicker" id="lh-icon" name="dock_icon" value="fa-solid fa-folder" required readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="lh-active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="lh-active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <!-- Right Column - Preview -->
                        <div class="col-md-4">
                            <div class="mb-3 text-center">
                                <label class="form-label">Preview</label>
                                <div class="text-center p-4" style="background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                                    <div id="lh-preview" class="color-preview mx-auto mb-3" style="background: #007bff;">
                                        <i class="fa-solid fa-folder"></i>
                                    </div>
                                    <div id="lh-preview-name" style="font-weight: 600; color: #1f2937;">Dock Name</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-salmon" id="lh-save-btn" onclick="$('#lh-form').submit();">
                    <i class="fa-solid fa-cloud"></i> Save Dock
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function lhLoadDocks() {
    $.ajax({
        url: 'ajax/lh_docks/read_lighthouse_docks.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                lhRenderDocks(response.data);
            } else {
                lhShowEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load docks:', error);
            lhShowEmptyState();
        }
    });
}

function lhRenderDocks(docks) {
    const list = $('#lh-docks-list');
    list.empty();
    
    if (docks.length === 0) {
        lhShowEmptyState();
        $('#lh-default-dock-info').hide();
        return;
    }
    
    $('#lh-empty-state').hide();
    
    // Find the default dock
    let defaultDock = docks.find(d => parseInt(d.is_default) === 1);
    if (defaultDock) {
        $('#lh-default-dock-name').text(defaultDock.dock_name);
        $('#lh-default-dock-info').show();
    } else {
        $('#lh-default-dock-info').hide();
    }
    
    docks.forEach((dept, index) => {
        const isActive = parseInt(dept.is_active) === 1;
        const isDefault = parseInt(dept.is_default) === 1;
        
        const defaultBadge = isDefault ? 
            '<span class="default-badge"><i class="fa-solid fa-star"></i> Default</span>' : '';
        
        const setDefaultBtn = isDefault ?
            `<button type="button" class="btn-action default is-default" title="This is the default dock" disabled>
                <i class="fa-solid fa-star"></i>
            </button>` :
            `<button type="button" class="btn-action default" onclick="lhSetDefaultDock(${dept.dock_id})" title="Set as Default">
                <i class="fa-regular fa-star"></i>
            </button>`;
        
        const item = `
            <li class="dock-item mb-2" data-id="${dept.dock_id}" data-order="${dept.dock_order}">
                <div class="drag-handle">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <div class="order-number">${index + 1}</div>
                <div class="dock-icon-display" style="background-color: ${dept.dock_color};">
                    <i class="${dept.dock_icon || 'fa-solid fa-folder'}"></i>
                </div>
                <div class="dock-info">
                    <div class="dock-name">
                        ${lhEscapeHtml(dept.dock_name)}
                        ${defaultBadge}
                    </div>
                    <div class="dock-description">${lhEscapeHtml(dept.dock_description || '')}</div>
                </div>
                <div class="dock-meta">
                    <div class="status-toggle ${isActive ? 'active' : ''}" data-dock-id="${dept.dock_id}" data-current-status="${isActive ? 1 : 0}">
                        <div class="toggle-switch ${isActive ? 'active' : ''}"></div>
                        <span class="status-label">${isActive ? 'Active' : 'Inactive'}</span>
                    </div>
                    <div class="dock-actions">
                        ${setDefaultBtn}
                        <button type="button" class="btn-action" onclick="lhEditDock(${dept.dock_id})" title="Edit">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button type="button" class="btn-action delete" onclick="lhDeleteDock(${dept.dock_id})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </li>
        `;
        list.append(item);
    });
    
    // Initialize sortable
    if (typeof $.fn.sortable !== 'undefined') {
        $('#lh-docks-list').sortable({
            handle: '.drag-handle',
            placeholder: 'dock-item ui-sortable-placeholder',
            forcePlaceholderSize: true,
            tolerance: 'pointer',
            update: function(event, ui) {
                const order = $(this).sortable('toArray', { attribute: 'data-id' });
                console.log('New order:', order);
                
                // Transform array of IDs into array of objects with dock_id and dock_order
                const orderData = order.map((id, index) => ({
                    dock_id: parseInt(id),
                    dock_order: index
                }));
                
                $.ajax({
                    url: 'ajax/lh_docks/reorder_lighthouse_docks.php',
                    method: 'POST',
                    data: { order: JSON.stringify(orderData) },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            lhShowSaveIndicator();
                            lhLoadDocks();
                        }
                    }
                });
            }
        });
    }
}

function lhSetDefaultDock(dockId) {
    if (!confirm('Set this dock as the default? New signals from the Harbor will be automatically assigned to this dock.')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/lh_docks/set_default_dock.php',
        method: 'POST',
        data: { dock_id: dockId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                lhShowSaveIndicator('Default dock updated!');
                lhLoadDocks();
            } else {
                alert(response.message || 'Failed to set default dock');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to set default dock:', error);
            alert('Failed to set default dock. Please try again.');
        }
    });
}

function lhShowEmptyState() {
    $('#lh-docks-list').empty();
    $('#lh-empty-state').show();
}

function lhShowSaveIndicator(message) {
    const $indicator = $('#lh-save-indicator');
    if (message) {
        $indicator.html('<i class="fa-solid fa-check-circle me-2"></i>' + message);
    } else {
        $indicator.html('<i class="fa-solid fa-check-circle me-2"></i>Order saved successfully');
    }
    $indicator.fadeIn(300);
    
    setTimeout(function() {
        $indicator.fadeOut(300);
    }, 2000);
    
    // Refresh the lighthouse sidebar navigation to reflect changes
    if (typeof refreshLighthouseSidebar === 'function') {
        refreshLighthouseSidebar(false); // false = refresh structure + counts
    }
}

function lhOpenAddModal() {
    $('#lh-modal-title').html('<i class="fa-solid fa-plus me-2"></i>Add Dock');
    $('#lh-form')[0].reset();
    $('#lh-dock-id').val('');
    $('#lh-color').val('#007bff');
    $('#lh-color-hex').val('#007bff');
    $('#lh-icon').val('fa-solid fa-folder');
    $('#lh-active').prop('checked', true);
    
    $("#lh-icon-display").html('<i class="fa-solid fa-folder"></i>');
    
    setTimeout(function() {
        $('#lh-icon').iconpicker('setIcon', 'fa-solid fa-folder');
        lhUpdateIconPreview();
    }, 100);
    
    $('#lh-alert').html('');
}

function lhEditDock(id) {
    $.ajax({
        url: 'ajax/lh_docks/get_dock.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const dept = response.data;
                
                console.log('Editing dock:', dept);
                console.log('Dock icon value:', dept.dock_icon);
                
                $('#lh-modal-title').html('<i class="fa-solid fa-edit me-2"></i>Edit Dock');
                $('#lh-dock-id').val(dept.dock_id);
                $('#lh-name').val(dept.dock_name);
                $('#lh-description').val(dept.dock_description || '');
                $('#lh-color').val(dept.dock_color);
                $('#lh-color-hex').val(dept.dock_color);
                $('#lh-active').prop('checked', dept.is_active == 1);
                
                // Handle the icon - use full class or default
                let iconClass = dept.dock_icon || 'fa-solid fa-folder';
                
                // If the icon value is "0" or invalid, use default
                if (iconClass === '0' || iconClass === '' || iconClass === 'null') {
                    iconClass = 'fa-solid fa-folder';
                    console.warn('Invalid icon value, using default:', iconClass);
                }
                
                $('#lh-icon').val(iconClass);
                
                // Update the icon display with full class (not hardcoded fa-solid)
                $("#lh-icon-display").html(`<i class="${iconClass}"></i>`);
                
                // Set the icon picker value
                $('#lh-icon').iconpicker('setIcon', iconClass);
                
                // Update preview
                lhUpdateIconPreview();
                
                $('#lh-alert').html('');
                $('#lh-modal').modal('show');
            } else {
                alert('Failed to load dock: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load dock:', error);
            alert('Failed to load dock');
        }
    });
}

function lhDeleteDock(id) {
    if (!confirm('Are you sure you want to delete this dock? Signals in this dock will need to be reassigned.')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/lh_docks/delete_dock.php',
        method: 'POST',
        data: { dock_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                lhLoadDocks();
                lhShowSaveIndicator();
            } else {
                alert(response.message || 'Failed to delete dock');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to delete dock:', error);
            alert('Failed to delete dock');
        }
    });
}

function lhUpdateIconPreview() {
    const color = $('#lh-color').val();
    const icon = $('#lh-icon').val() || 'fa-solid fa-folder';
    const name = $('#lh-name').val() || 'Dock Name';
    
    // Use the full icon class name from the picker
    $('#lh-preview').css('background-color', color);
    $('#lh-preview').html(`<i class="${icon}"></i>`);
    $('#lh-preview-name').text(name);
    
    // Update the icon in the input group
    $('#lh-icon-display').html(`<i class="${icon}"></i>`);
    $('#lh-icon-display i').css('color', color);
}

function lhEscapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

$(document).ready(function() {
    console.log('Lighthouse Docks: Page loaded, initializing...');
    
    lhLoadDocks();
    
    // Search/Filter functionality
    let searchTimeout = null;
    
    function filterDocks(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Show all docks
            $('.dock-item').show();
            $('#search-results-info').hide();
            $('#search-no-results').remove();
            return;
        }
        
        let visibleCount = 0;
        
        $('.dock-item').each(function() {
            const $item = $(this);
            const dockName = $item.find('.dock-name').text().toLowerCase();
            const dockDescription = $item.find('.dock-description').text().toLowerCase();
            
            if (dockName.includes(searchTerm) || dockDescription.includes(searchTerm)) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Update search results info
        const totalCount = $('.dock-item').length;
        $('#docks-count').text(visibleCount);
        $('#total-docks').text(totalCount);
        $('#search-results-info').show();
        
        // Show empty state if no results
        if (visibleCount === 0) {
            if ($('#search-no-results').length === 0) {
                $('#lh-docks-list').append(`
                    <div id="search-no-results" class="empty-state">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h4>No Docks Found</h4>
                        <p>No docks match your search criteria</p>
                    </div>
                `);
            }
        } else {
            $('#search-no-results').remove();
        }
    }
    
    // Search input handler with debounce
    $('#docks-search').on('input', function() {
        const searchValue = $(this).val().trim();
        const $searchBtn = $('#search-btn');
        
        // Update button appearance
        if (searchValue.length > 0) {
            $searchBtn.html('<i class="fa-solid fa-circle-xmark"></i> Clear');
            $searchBtn.addClass('clear-mode');
        } else {
            $searchBtn.html('<i class="fa-solid fa-magnifying-glass"></i> Search');
            $searchBtn.removeClass('clear-mode');
        }
        
        // Debounced search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            filterDocks(searchValue);
        }, 300);
    });
    
    // Search on Enter key
    $('#docks-search').on('keypress', function(e) {
        if (e.which === 13) {
            clearTimeout(searchTimeout);
            filterDocks($(this).val().trim());
        }
    });
    
    // Search button click handler
    $('#search-btn').on('click', function() {
        const $searchInput = $('#docks-search');
        const $searchBtn = $(this);
        
        if ($searchBtn.hasClass('clear-mode')) {
            // Clear search
            $searchInput.val('');
            $searchBtn.html('<i class="fa-solid fa-magnifying-glass"></i> Search');
            $searchBtn.removeClass('clear-mode');
            filterDocks('');
        } else {
            // Execute search
            filterDocks($searchInput.val().trim());
        }
    });
    
    if (typeof $.fn.iconpicker !== 'undefined') {
        console.log('Lighthouse Docks: Initializing icon picker...');
        
        $('#lh-icon').iconpicker({
            placement: 'bottomRight',
            showFooter: false,
            hideOnSelect: true,
            component: '.input-group-text',
            templates: {
                iconpickerItem: '<a role="button" class="iconpicker-item"><i></i></a>',
            }
        });
        
        $('#lh-icon').on('iconpickerSelected', function(e) {
            const selectedIcon = e.iconpickerValue;
            console.log('Icon selected:', selectedIcon);
            
            $('#lh-icon').val(selectedIcon);
            $("#lh-icon-display").html(`<i class="${selectedIcon}"></i>`);
            lhUpdateIconPreview();
        });
    } else {
        console.error('IconPicker plugin not found!');
    }
    
    $('#lh-color').on('input change', function() {
        const newColor = $(this).val();
        $('#lh-color-hex').val(newColor);
        lhUpdateIconPreview();
    });
    
    $('#lh-color-hex').on('input change', function() {
        const value = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $('#lh-color').val(value);
            lhUpdateIconPreview();
        }
    });
    
    $('#lh-name').on('input', function() {
        lhUpdateIconPreview();
    });
    
    $(document).on('click', '.status-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $toggle = $(this);
        const dockId = $toggle.data('dock-id');
        const currentStatus = parseInt($toggle.data('current-status'));
        const newStatus = currentStatus ? 0 : 1;
        
        console.log('Toggling status:', dockId, 'from', currentStatus, 'to', newStatus);
        
        $toggle.css('pointer-events', 'none').css('opacity', '0.6');
        
        $.ajax({
            url: 'ajax/lh_docks/toggle_dock_status.php',
            method: 'POST',
            data: { 
                dock_id: dockId,
                is_active: newStatus
            },
            dataType: 'json',
            success: function(response) {
                console.log('Toggle response:', response);
                
                if (response.success) {
                    $toggle.toggleClass('active');
                    $toggle.find('.toggle-switch').toggleClass('active');
                    $toggle.find('.status-label').text(newStatus ? 'Active' : 'Inactive');
                    $toggle.data('current-status', newStatus);
                    
                    lhShowSaveIndicator();
                } else {
                    alert(response.message || 'Failed to update status');
                }
                $toggle.css('pointer-events', '').css('opacity', '');
            },
            error: function(xhr, status, error) {
                console.error('Toggle error:', status, error);
                alert('Failed to update status. Please try again.');
                $toggle.css('pointer-events', '').css('opacity', '');
            }
        });
    });
    
    $('#lh-form').on('submit', function(e) {
        e.preventDefault();
        
        const dockName = $('#lh-name').val().trim();
        const dockColor = $('#lh-color').val();
        const dockIcon = $('#lh-icon').val();
        
        console.log('Submitting form with values:', {
            name: dockName,
            color: dockColor,
            icon: dockIcon
        });
        
        if (!dockName) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Dock name is required.
                </div>
            `);
            return;
        }
        
        if (!dockColor) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Dock color is required.
                </div>
            `);
            return;
        }
        
        if (!dockIcon) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Dock icon is required.
                </div>
            `);
            return;
        }
        
        const btn = $('#lh-save-btn');
        const originalBtnText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        $('#lh-alert').html('');
        
        const formData = $(this).serialize();
        const url = $('#lh-dock-id').val() ? 
            'ajax/lh_docks/update_dock.php' : 
            'ajax/lh_docks/create_dock.php';
        
        console.log('Posting to:', url);
        console.log('Form data:', formData);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                
                if (response.success) {
                    $('#lh-modal').modal('hide');
                    lhLoadDocks();
                    lhShowSaveIndicator();
                } else {
                    $('#lh-alert').html(`
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to save dock.'}
                        </div>
                    `);
                }
                btn.prop('disabled', false).html(originalBtnText);
            },
            error: function(xhr, status, error) {
                console.error('Save error:', status, error);
                console.error('Response:', xhr.responseText);
                
                $('#lh-alert').html(`
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle"></i> An error occurred while saving. Check console for details.
                    </div>
                `);
                btn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});
</script>