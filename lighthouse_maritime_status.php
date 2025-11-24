<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_maritime')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', 'Maritime Sea States');
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

.btn-add-state {
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

.btn-add-state:hover {
    background: #059669;
  	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.states-container {
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

.states-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.state-item {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
 	cursor: move;
    transition: all 0.2s ease;
    position: relative;
}

.state-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #ffffff;
}

.state-item.ui-sortable-helper {
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    background: white;
    opacity: 0.95;
    cursor: grabbing !important;
    transform: scale(1.02);
    z-index: 1000;
}

.state-item.ui-sortable-placeholder {
    border: 2px dashed #3b82f6;
    background: linear-gradient(135deg, #eff6ff 25%, transparent 25%, transparent 50%, #eff6ff 50%, #eff6ff 75%, transparent 75%, transparent);
    background-size: 20px 20px;
    visibility: visible !important;
    height: 80px;
    opacity: 0.8;
}

.state-item.ui-sortable-placeholder .drag-handle,
.state-item.ui-sortable-placeholder .order-number,
.state-item.ui-sortable-placeholder .state-badge,
.state-item.ui-sortable-placeholder .state-info,
.state-item.ui-sortable-placeholder .state-meta {
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

.order-number {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #3b82f6;
    color: white;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
}

.state-badge {
    flex-shrink: 0;
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-size: 13px;
    font-weight: 600;
    min-width: 120px;
    text-align: center;
}

.state-info {
    flex: 1;
    min-width: 0;
}

.state-name {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.state-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.state-meta {
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

.state-actions {
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
    color: #9ca3af;
}

.save-indicator {
    position: fixed;
    top: 80px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    display: none;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    z-index: 9999;
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.color-preview {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.color-preview i {
    font-size: 24px;
    color: white;
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

.color-preview-box {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    border: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    font-weight: 700;
}

/* Closed Resolution Badge */
.closed-resolution-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #dc2626;
    color: white;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Status Type Radio Buttons */
.status-type-options {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.status-type-options .form-check {
    padding: 12px;
    border-radius: 6px;
    transition: all 0.2s;
    margin-bottom: 8px;
}

.status-type-options .form-check:last-child {
    margin-bottom: 0;
}

.status-type-options .form-check:hover {
    background: white;
}

.status-type-options .form-check-input:checked + .form-check-label {
    color: #1f2937;
    font-weight: 600;
}

.status-type-options .form-check-label {
    cursor: pointer;
    user-select: none;
    width: 100%;
    margin-left: 8px;
}

.status-type-options .form-check-label small {
    display: block;
    color: #6b7280;
    font-weight: normal;
    margin-top: 4px;
    line-height: 1.4;
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

	<div class="page-header mb-3">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-water me-2" style="color: #3b82f6;"></i>Sea Status
            </h1>
            <p class="mb-0 mt-1" style="font-size: 14px; color: #6b7280;">
                Configure the status stages for signals in the lighthouse system
            </p>
        </div>
        <button type="button" class="btn-add-state" data-bs-toggle="modal" data-bs-target="#ss-modal" onclick="ssOpenAddModal()">
            <i class="fa-solid fa-plus"></i>Add Sea State
        </button>
    </div>
    
    <div class="states-container">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fa-solid fa-list-ol" style="color: #6b7280;"></i> Status
            </h3>
            <div class="drag-hint">
                <i class="fa-solid fa-grip-vertical"></i>
                <span>Drag to reorder</span>
            </div>
        </div>
        
        <div id="ss-empty-state" class="empty-state" style="display: none;">
            <i class="fa-solid fa-water"></i>
            <h4>No Sea States Yet</h4>
            <p>Create your first sea state to get started</p>
        </div>
        
        <ul id="ss-states-list" class="states-list">
            <!-- States will be loaded here -->
        </ul>
    </div>


<div class="save-indicator" id="ss-save-indicator">
    <i class="fa-solid fa-check-circle"></i>
    <span>Changes saved successfully!</span>
</div>

                    </div>
                </div>
            </div>
        </div>
    </div>
	
	<?php include 'templates/footer.php'; ?>
</main>



<!-- Add/Edit Sea State Modal -->
<div class="modal fade" id="ss-modal" tabindex="-1" aria-labelledby="ss-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ss-modal-title">
                    <i class="fa-solid fa-plus"></i> Add Sea State
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ss-alert"></div>
                
                <form id="ss-form">
                    <input type="hidden" id="ss-state-id" name="sea_state_id">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="ss-name" class="form-label">
                                    Sea State Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="ss-name" name="sea_state_name" 
                                       placeholder="e.g., Signal Incoming, Charting Course" required>
                            </div>

                            <div class="mb-3">
                                <label for="ss-description" class="form-label">Description</label>
                                <textarea class="form-control" id="ss-description" name="sea_state_description" rows="3" 
                                          placeholder="Describe this sea state and when it should be used"></textarea>
                            </div>

                            <!-- Status Type Options -->
                            <div class="status-type-options">
                                <label class="form-label mb-3">
                                    <i class="fa-solid fa-toggle-on me-1"></i> Status Type <span class="text-danger">*</span>
                                </label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_closed_resolution" id="ss-type-open" value="0" checked>
                                    <label class="form-check-label" for="ss-type-open">
                                        <i class="fa-solid fa-ship-alt me-1"></i> Open Status
                                        <small>Signals with this status will appear in main signal lists and views</small>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_closed_resolution" id="ss-type-closed" value="1">
                                    <label class="form-check-label" for="ss-type-closed">
                                        <i class="fa-solid fa-anchor me-1"></i> Closed Resolution
                                        <small>Signals with this status will be hidden from main views and only visible when specifically filtered</small>
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ss-color" class="form-label">
                                            Color <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="ss-color" name="sea_state_color" value="#007bff" required>
                                            <input type="text" class="form-control" id="ss-color-hex" placeholder="#007bff" maxlength="7">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ss-icon" class="form-label">
                                            Icon <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="ss-icon" name="sea_state_icon" value="fa-solid fa-circle" required readonly>
                                            <span class="input-group-text" id="ss-icon-display">
                                                <i class="fa-solid fa-circle"></i>
                                            </span>
                                        </div>
                                        <small class="text-muted">Click the icon to choose</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ss-active" name="is_active" checked>
                                <label class="form-check-label" for="ss-active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <!-- Right Column - Preview -->
                        <div class="col-md-4">
                            <label class="form-label">Preview</label>
                            <div class="text-center">
                                <div class="color-preview mx-auto" id="ss-preview">
                                    <i class="fa-solid fa-circle"></i>
                                </div>
                                <p class="mt-3 mb-0 fw-bold" id="ss-preview-name">Sea State Name</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-circle-xmark"></i> Cancel
                </button>
                <button type="submit" form="ss-form" class="btn btn-salmon" id="ss-save-btn">
                    <i class="fa-solid fa-cloud"></i> Save Sea State
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function ssLoadStates() {
    $.ajax({
        url: 'ajax/lh_sea_states/read_sea_states.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                ssRenderStates(response.data);
            } else {
                ssShowEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load states:', error);
            ssShowEmptyState();
        }
    });
}

function ssRenderStates(states) {
    const list = $('#ss-states-list');
    list.empty();
    
    if (states.length === 0) {
        ssShowEmptyState();
        return;
    }
    
    $('#ss-empty-state').hide();
    
    states.forEach((state, index) => {
        const isActive = parseInt(state.is_active) === 1;
        const isClosed = parseInt(state.is_closed_resolution) === 1;
        const closedBadge = isClosed ? '<span class="closed-resolution-badge ms-2"><i class="fa-solid fa-anchor"></i> Closed</span>' : '';
        
        const item = `
            <li class="state-item mb-2" data-id="${state.sea_state_id}" data-order="${state.sea_state_order}">
                <div class="drag-handle">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <div class="order-number">${index + 1}</div>
                <div class="state-badge" style="width:200px; background-color: ${state.sea_state_color};">
                    <i class="${state.sea_state_icon || 'fa-solid fa-circle'}"></i>
                    ${ssEscapeHtml(state.sea_state_name)}
                </div>
                <div class="state-info">
                    <div class="state-name">
                        ${ssEscapeHtml(state.sea_state_name)}
                        ${closedBadge}
                    </div>
                    <div class="state-description">${ssEscapeHtml(state.sea_state_description || '')}</div>
                </div>
                <div class="state-meta">
                    <div class="status-toggle ${isActive ? 'active' : ''}" data-state-id="${state.sea_state_id}" data-current-status="${isActive ? 1 : 0}">
                        <div class="toggle-switch ${isActive ? 'active' : ''}"></div>
                        <span class="status-label">${isActive ? 'Active' : 'Inactive'}</span>
                    </div>
                    <div class="state-actions">
                        <button type="button" class="btn-action" onclick="ssEditState(${state.sea_state_id})" title="Edit">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button type="button" class="btn-action delete" onclick="ssDeleteState(${state.sea_state_id})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </li>
        `;
        list.append(item);
    });
    
    list.sortable({
        handle: '.drag-handle',
        placeholder: 'state-item ui-sortable-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        update: function(event, ui) {
            ssSaveOrder();
        }
    });
}

function ssSaveOrder() {
    const order = [];
    $('#ss-states-list .state-item').each(function(index) {
        order.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });
    
    $.ajax({
        url: 'ajax/lh_sea_states/reorder_sea_states.php',
        method: 'POST',
        data: { order: order },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                ssLoadStates();
                ssShowSaveIndicator();
            } else {
                console.error('Failed to save order:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to save order:', error);
        }
    });
}

function ssShowEmptyState() {
    $('#ss-states-list').empty();
    $('#ss-empty-state').show();
}

function ssShowSaveIndicator() {
    const indicator = $('#ss-save-indicator');
    indicator.css('display', 'flex').hide().fadeIn(300);
    
    setTimeout(function() {
        indicator.fadeOut(300);
    }, 2000);
    
    // Refresh the lighthouse sidebar navigation to reflect changes
    if (typeof refreshLighthouseSidebar === 'function') {
        refreshLighthouseSidebar(false); // false = refresh structure + counts
    }
}

function ssOpenAddModal() {
    $('#ss-modal-title').html('<i class="fa-solid fa-plus me-2"></i>Add Sea State');
    $('#ss-form')[0].reset();
    $('#ss-state-id').val('');
    $('#ss-description').val('');
    $('#ss-color').val('#007bff');
    $('#ss-icon').val('fa-solid fa-circle');
    $('#ss-active').prop('checked', true);
    $('#ss-type-open').prop('checked', true);
    
    $("#ss-icon-display").html('<i class="fa-solid fa-circle"></i>');
    
    setTimeout(function() {
        $('#ss-icon').iconpicker('setIcon', 'fa-solid fa-circle');
        ssUpdateColorPreview();
    }, 100);
    
    $('#ss-alert').html('');
}

function ssEditState(id) {
    $.ajax({
        url: 'ajax/lh_sea_states/get_sea_state.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const state = response.data;
                
                $('#ss-modal-title').html('<i class="fa-solid fa-edit me-2"></i>Edit Sea State');
                $('#ss-state-id').val(state.sea_state_id);
                $('#ss-name').val(state.sea_state_name);
                $('#ss-description').val(state.sea_state_description || '');
                $('#ss-color').val(state.sea_state_color);
                $('#ss-color-hex').val(state.sea_state_color);
                $('#ss-active').prop('checked', state.is_active == 1);
                
                // Set closed resolution radio button
                const isClosed = state.is_closed_resolution == 1;
                if (isClosed) {
                    $('#ss-type-closed').prop('checked', true);
                } else {
                    $('#ss-type-open').prop('checked', true);
                }
                
           	 	let iconClass = state.sea_state_icon || 'fa-solid fa-circle';
                
           	 	if (iconClass === '0' || iconClass === '' || iconClass === 'null') {
                    iconClass = 'fa-solid fa-circle';
                }
                
                $('#ss-icon').val(iconClass);
             	$("#ss-icon-display").html(`<i class="${iconClass}"></i>`);
             	$('#ss-icon').iconpicker('setIcon', iconClass);
                
                ssUpdateColorPreview();
                $('#ss-alert').html('');
                $('#ss-modal').modal('show');
            } else {
                alert('Failed to load sea state: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load sea state:', error);
            alert('Failed to load sea state');
        }
    });
}

function ssDeleteState(id) {
    if (!confirm('Are you sure you want to delete this sea state? Signals with this status will need to be updated.')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/lh_sea_states/delete_sea_state.php',
        method: 'POST',
        data: { sea_state_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                ssLoadStates();
                ssShowSaveIndicator();
            } else {
                alert(response.message || 'Failed to delete sea state');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to delete sea state:', error);
            alert('Failed to delete sea state');
        }
    });
}

function ssUpdateColorPreview() {
    const color = $('#ss-color').val();
    const name = $('#ss-name').val() || 'Sea State Name';
    const icon = $('#ss-icon').val() || 'fa-solid fa-circle';
    
	$('#ss-preview').css('background-color', color);
    $('#ss-preview').html(`<i class="${icon}"></i>`);
    $('#ss-preview-name').text(name);
	$('#ss-icon-display').html(`<i class="${icon}"></i>`);
    $('#ss-icon-display i').css('color', color);
}

function ssEscapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

$(document).ready(function() {
    console.log('Lighthouse Sea States: Page loaded, initializing...');
    
    ssLoadStates();
    
	if (typeof $.fn.iconpicker !== 'undefined') {
        console.log('Lighthouse Sea States: Initializing icon picker...');
        
        $('#ss-icon').iconpicker({
            placement: 'bottomRight',
            showFooter: false,
            hideOnSelect: true,
            component: '.input-group-text',
            templates: {
                iconpickerItem: '<a role="button" class="iconpicker-item"><i></i></a>',
            }
        });
        
        $('#ss-icon').on('iconpickerSelected', function(e) {
            const selectedIcon = e.iconpickerValue;
            console.log('Icon selected:', selectedIcon);
            
            $('#ss-icon').val(selectedIcon);
            $("#ss-icon-display").html(`<i class="${selectedIcon}"></i>`);
            ssUpdateColorPreview();
        });
    } else {
        console.error('IconPicker plugin not found!');
    }
    
	$('#ss-modal').on('shown.bs.modal', function () {
        if (typeof $.fn.iconpicker !== 'undefined') {
         	$('#ss-icon').iconpicker('destroy');
         	$('#ss-icon').iconpicker({
                placement: 'bottomRight',
                showFooter: false,
                hideOnSelect: true,
                component: '.input-group-text',
                templates: {
                    iconpickerItem: '<a role="button" class="iconpicker-item"><i></i></a>',
                }
            });
        }
    });
    
    $('#ss-color').on('input change', function() {
        const newColor = $(this).val();
        $('#ss-color-hex').val(newColor);
        ssUpdateColorPreview();
    });
    
    $('#ss-color-hex').on('input change', function() {
        const value = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $('#ss-color').val(value);
            ssUpdateColorPreview();
        }
    });
    
    $('#ss-name').on('input', function() {
        ssUpdateColorPreview();
    });
    
    $(document).on('click', '.status-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $toggle = $(this);
        const stateId = $toggle.data('state-id');
        const currentStatus = parseInt($toggle.data('current-status'));
        const newStatus = currentStatus ? 0 : 1;
        
       	$toggle.css('pointer-events', 'none').css('opacity', '0.6');
        
        $.ajax({
            url: 'ajax/lh_sea_states/toggle_sea_states_status.php',
            method: 'POST',
            data: { 
                sea_state_id: stateId,
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
                    
                    ssShowSaveIndicator();
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
    
    $('#ss-form').on('submit', function(e) {
        e.preventDefault();
        
        const stateName = $('#ss-name').val().trim();
        const stateColor = $('#ss-color').val();
        
        if (!stateName) {
            $('#ss-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Sea state name is required.
                </div>
            `);
            return;
        }
        
        if (!stateColor) {
            $('#ss-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Badge color is required.
                </div>
            `);
            return;
        }
        
        
        const stateIcon = $('#ss-icon').val();
        
        if (!stateIcon) {
            $('#ss-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Sea state icon is required.
                </div>
            `);
            return;
        }
        
        const btn = $('#ss-save-btn');
        const originalBtnText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        $('#ss-alert').html('');
        
        const formData = $(this).serialize();
        const url = $('#ss-state-id').val() ? 
            'ajax/lh_sea_states/update_sea_state.php' : 
            'ajax/lh_sea_states/create_sea_state.php';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                
                if (response.success) {
                    $('#ss-modal').modal('hide');
                    ssLoadStates();
                    ssShowSaveIndicator();
                } else {
                    $('#ss-alert').html(`
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to save sea state.'}
                        </div>
                    `);
                }
                btn.prop('disabled', false).html(originalBtnText);
            },
            error: function(xhr, status, error) {
                console.error('Save error:', status, error);
                console.error('Response:', xhr.responseText);
                
                $('#ss-alert').html(`
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