<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_maritime')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', 'Manage Priority Levels');
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

.btn-add-priority {
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

.btn-add-priority:hover {
    background: #059669;
	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.priorities-container {
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

.priorities-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.priority-item {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
 	cursor: move;
    transition: all 0.2s ease;
    position: relative;
}

.priority-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #ffffff;
}

.priority-item.ui-sortable-helper {
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    background: white;
    opacity: 0.95;
    cursor: grabbing !important;
    transform: scale(1.02);
    z-index: 1000;
}

.priority-item.ui-sortable-placeholder {
    border: 2px dashed #3b82f6;
    background: linear-gradient(135deg, #eff6ff 25%, transparent 25%, transparent 50%, #eff6ff 50%, #eff6ff 75%, transparent 75%, transparent);
    background-size: 20px 20px;
    visibility: visible !important;
    height: 60px;
    opacity: 0.8;
}

.priority-item.ui-sortable-placeholder .drag-handle,
.priority-item.ui-sortable-placeholder .order-number,
.priority-item.ui-sortable-placeholder .priority-icon-display,
.priority-item.ui-sortable-placeholder .priority-info,
.priority-item.ui-sortable-placeholder .priority-meta {
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

.priority-icon-display {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.priority-info {
    flex: 1;
    min-width: 0;
}

.priority-name {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.priority-meta {
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

.priority-actions {
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
                <i class="fa-solid fa-flag" style="color: #ff5722;"></i> Priority Levels
            </h1>
            <p class="mb-0 mt-1" style="font-size: 14px; color: #6b7280;">
                Configure the lighthouse signal priority levels
            </p>
        </div>
        <button type="button" class="btn-add-priority" data-bs-toggle="modal" data-bs-target="#pr-modal" onclick="prOpenAddModal()">
            <i class="fa-solid fa-plus"></i>Add Priority
        </button>
    </div>
    
    <div class="priorities-container">
        <div class="section-header">
            <h3 class="section-title">
                <i class="fa-solid fa-list-ol" style="color: #6b7280;"></i> Priority Levels
            </h3>
            <div class="drag-hint">
                <i class="fa-solid fa-grip-vertical"></i>
                <span>Drag to reorder</span>
            </div>
        </div>
        
        <div id="pr-empty-state" class="empty-state" style="display: none;">
            <i class="fa-solid fa-flag"></i>
            <h4>No Priority Levels Yet</h4>
            <p>Create your first priority level to get started</p>
        </div>
        
        <ul id="pr-priorities-list" class="priorities-list">
            <!-- Priorities will be loaded here -->
        </ul>
    </div>


<div class="save-indicator" id="pr-save-indicator">
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


<!-- Add/Edit Priority Modal -->
<div class="modal fade" id="pr-modal" tabindex="-1" aria-labelledby="pr-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pr-modal-title">
                    <i class="fa-solid fa-plus"></i> Add Priority
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pr-alert"></div>
                
                <form id="pr-form">
                    <input type="hidden" id="pr-priority-id" name="priority_id">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="pr-name" class="form-label">
                                    Priority Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="pr-name" name="priority_name" 
                                       placeholder="e.g., Storm, Calm, Choppy" required>
                            </div>

                            <div class="mb-3">
                                <label for="pr-description" class="form-label">Description</label>
                                <textarea class="form-control" id="pr-description" name="priority_description" rows="3"
                                          placeholder="Describe when this priority level should be used"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pr-color" class="form-label">
                                            Color <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="pr-color" name="priority_color" value="#28a745" required>
                                            <input type="text" class="form-control" id="pr-color-hex" placeholder="#28a745" maxlength="7">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pr-icon" class="form-label">
                                            Icon <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="pr-icon" name="priority_icon" value="fa-solid fa-flag" required readonly>
                                            <span class="input-group-text" id="pr-icon-display">
                                                <i class="fa-solid fa-flag"></i>
                                            </span>
                                        </div>
                                        <small class="text-muted">Click the icon to choose</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pr-active" name="is_active" checked>
                                <label class="form-check-label" for="pr-active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <!-- Right Column - Preview -->
                        <div class="col-md-4">
                            <label class="form-label">Preview</label>
                            <div class="text-center">
                                <div class="color-preview mx-auto" id="pr-preview">
                                    <i class="fa-solid fa-flag"></i>
                                </div>
                                <p class="mt-3 mb-0 fw-bold" id="pr-preview-name">Priority Name</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-circle-xmark"></i> Cancel</button>
                <button type="submit" form="pr-form" class="btn btn-salmon" id="pr-save-btn">
                    <i class="fa-solid fa-cloud"></i> Save Priority
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function prLoadPriorities() {
    $.ajax({
        url: 'ajax/lh_priorities/read_priorities.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                prRenderPriorities(response.data);
            } else {
                prShowEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load priorities:', error);
            prShowEmptyState();
        }
    });
}

function prRenderPriorities(priorities) {
    const list = $('#pr-priorities-list');
    list.empty();
    
    if (priorities.length === 0) {
        prShowEmptyState();
        return;
    }
    
    $('#pr-empty-state').hide();
    
    priorities.forEach((priority, index) => {
        const isActive = parseInt(priority.is_active) === 1;
        const item = `
            <li class="priority-item mb-2" data-id="${priority.priority_id}" data-order="${priority.priority_order}">
                <div class="drag-handle">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <div class="order-number">${index + 1}</div>
                <div class="priority-icon-display" style="background-color: ${priority.priority_color};">
                    <i class="${priority.priority_icon || 'fa-solid fa-flag'}" style="color: white;"></i>
                </div>
                <div class="priority-info">
                    <div class="priority-name">${prEscapeHtml(priority.priority_name)}</div>
                    ${priority.priority_description ? `<div style="font-size: 13px; color: #6b7280; margin-top: 4px;">${prEscapeHtml(priority.priority_description)}</div>` : ''}
                </div>
                <div class="priority-meta">
                    <div class="status-toggle ${isActive ? 'active' : ''}" data-priority-id="${priority.priority_id}" data-current-status="${isActive ? 1 : 0}">
                        <div class="toggle-switch ${isActive ? 'active' : ''}"></div>
                        <span class="status-label">${isActive ? 'Active' : 'Inactive'}</span>
                    </div>
                    <div class="priority-actions">
                        <button type="button" class="btn-action" onclick="prEditPriority(${priority.priority_id})" title="Edit">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button type="button" class="btn-action delete" onclick="prDeletePriority(${priority.priority_id})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </li>
        `;
        list.append(item);
    });
    
    // Initialize sortable
    list.sortable({
        handle: '.drag-handle',
        placeholder: 'ui-sortable-placeholder',
        axis: 'y',
        tolerance: 'pointer',
        cursor: 'grabbing',
        revert: 150,
        opacity: 0.95,
        helper: 'clone',
        forcePlaceholderSize: true,
        start: function(event, ui) {
            ui.placeholder.height(ui.helper.outerHeight());
        },
        update: function(event, ui) {
            prSaveOrder();
        }
    });
}

function prShowEmptyState() {
    $('#pr-priorities-list').empty();
    $('#pr-empty-state').show();
}

function prSaveOrder() {
    const order = [];
    $('#pr-priorities-list li').each(function(index) {
        order.push({
            priority_id: $(this).data('id'),
            priority_order: index + 1
        });
        $(this).find('.order-number').text(index + 1);
    });
    
    $.ajax({
        url: 'ajax/lh_priorities/reorder_priorities.php',
        method: 'POST',
        data: { order: JSON.stringify(order) },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                prShowSaveIndicator();
            }
        }
    });
}

function prShowSaveIndicator() {
    const indicator = $('#pr-save-indicator');
    indicator.fadeIn(300);
    
    setTimeout(function() {
        indicator.fadeOut(300);
    }, 2000);
}

function prOpenAddModal() {
    $('#pr-modal-title').html('<i class="fa-solid fa-plus me-2"></i>Add Priority');
    $('#pr-form')[0].reset();
    $('#pr-priority-id').val('');
    $('#pr-description').val('');
    $('#pr-color').val('#28a745');
    $('#pr-color-hex').val('#28a745');
    $('#pr-icon').val('fa-solid fa-flag');
    $('#pr-active').prop('checked', true);
    
    
    $("#pr-icon-display").html('<i class="fa-solid fa-flag"></i>');
    
    setTimeout(function() {
        $('#pr-icon').iconpicker('setIcon', 'fa-solid fa-flag');
        prUpdateIconPreview();
    }, 100);
    
    
    prUpdateIconPreview();
    $('#pr-alert').html('');
}

function prEditPriority(id) {
    $.ajax({
        url: 'ajax/lh_priorities/get_priority.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const priority = response.data;
                
                $('#pr-modal-title').html('<i class="fa-solid fa-edit me-2"></i>Edit Priority');
                $('#pr-priority-id').val(priority.priority_id);
                $('#pr-name').val(priority.priority_name);
                $('#pr-description').val(priority.priority_description || '');
                $('#pr-icon').val(priority.priority_icon);
                $('#pr-color').val(priority.priority_color);
                $('#pr-color-hex').val(priority.priority_color);
                $('#pr-active').prop('checked', priority.is_active == 1);
                
                // Handle the icon - use full class or default
                let iconClass = priority.priority_icon || 'fa-solid fa-flag';
                
                // If the icon value is invalid, use default
                if (iconClass === '0' || iconClass === '' || iconClass === 'null') {
                    iconClass = 'fa-solid fa-flag';
                }
                
                $('#pr-icon').val(iconClass);
                
                // Update the icon display with full class
                $("#pr-icon-display").html(`<i class="${iconClass}"></i>`);
                
                // Set the icon picker value
                $('#pr-icon').iconpicker('setIcon', iconClass);
                
                
                prUpdateIconPreview();
                $('#pr-alert').html('');
                $('#pr-modal').modal('show');
            } else {
                alert('Failed to load priority: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load priority:', error);
            alert('Failed to load priority');
        }
    });
}

function prDeletePriority(id) {
    if (!confirm('Are you sure you want to delete this priority level? Signals with this priority will need to be updated.')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/lh_priorities/delete_priority.php',
        method: 'POST',
        data: { priority_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                prLoadPriorities();
                prShowSaveIndicator();
            } else {
                alert(response.message || 'Failed to delete priority');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to delete priority:', error);
            alert('Failed to delete priority');
        }
    });
}

function prUpdateIconPreview() {
    const color = $('#pr-color').val();
    const icon = $('#pr-icon').val() || 'fa-solid fa-flag';
    const name = $('#pr-name').val() || 'Priority Name';
    
    $('#pr-preview').css('background-color', color);
    $('#pr-preview').html(`<i class="${icon}"></i>`);
    $('#pr-preview-name').text(name);
    
    $('#pr-icon-display').html(`<i class="${icon}"></i>`);
    $('#pr-icon-display i').css('color', color);
}

function prEscapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

$(document).ready(function() {
    console.log('Lighthouse Priorities: Page loaded, initializing...');
    
    prLoadPriorities();
    
    
    // Initialize icon picker if available
    if (typeof $.fn.iconpicker !== 'undefined') {
        console.log('Lighthouse Priorities: Initializing icon picker...');
        
        $('#pr-icon').iconpicker({
            placement: 'bottomRight',
            showFooter: false,
            hideOnSelect: true,
            component: '.input-group-text',
            templates: {
                iconpickerItem: '<a role="button" class="iconpicker-item"><i></i></a>',
            }
        });
        
        $('#pr-icon').on('iconpickerSelected', function(e) {
            const selectedIcon = e.iconpickerValue;
            console.log('Icon selected:', selectedIcon);
            
            $('#pr-icon').val(selectedIcon);
            $("#pr-icon-display").html(`<i class="${selectedIcon}"></i>`);
            prUpdateIconPreview();
        });
    } else {
        console.error('IconPicker plugin not found!');
    }
    
    
    $('#pr-color').on('input change', function() {
        const newColor = $(this).val();
        $('#pr-color-hex').val(newColor);
        prUpdateIconPreview();
    });
    
    $('#pr-color-hex').on('input change', function() {
        const value = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $('#pr-color').val(value);
            prUpdateIconPreview();
        }
    });
    
    $('#pr-name').on('input', function() {
        prUpdateIconPreview();
    });
    
    $(document).on('click', '.status-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $toggle = $(this);
        const priorityId = $toggle.data('priority-id');
        const currentStatus = parseInt($toggle.data('current-status'));
        const newStatus = currentStatus ? 0 : 1;
        
      	$toggle.css('pointer-events', 'none').css('opacity', '0.6');
        
        $.ajax({
            url: 'ajax/lh_priorities/toggle_priority_status.php',
            method: 'POST',
            data: { 
                priority_id: priorityId,
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
                    
                    prShowSaveIndicator();
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
    
    $('#pr-form').on('submit', function(e) {
        e.preventDefault();
        
        const priorityName = $('#pr-name').val().trim();
        const priorityIcon = $('#pr-icon').val();
        const priorityColor = $('#pr-color').val();
        
        if (!priorityName) {
            $('#pr-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Priority name is required.
                </div>
            `);
            return;
        }
        
        if (!priorityIcon) {
            $('#pr-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Priority icon is required.
                </div>
            `);
            return;
        }
        
        if (!priorityColor) {
            $('#pr-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Badge color is required.
                </div>
            `);
            return;
        }
        
        const btn = $('#pr-save-btn');
        const originalBtnText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        $('#pr-alert').html('');
        
        const formData = $(this).serialize();
        const url = $('#pr-priority-id').val() ? 
            'ajax/lh_priorities/update_priority.php' : 
            'ajax/lh_priorities/create_priority.php';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                
                if (response.success) {
                    $('#pr-modal').modal('hide');
                    prLoadPriorities();
                    prShowSaveIndicator();
                } else {
                    $('#pr-alert').html(`
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to save priority.'}
                        </div>
                    `);
                }
                btn.prop('disabled', false).html(originalBtnText);
            },
            error: function(xhr, status, error) {
                console.error('Save error:', status, error);
                console.error('Response:', xhr.responseText);
                
                $('#pr-alert').html(`
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