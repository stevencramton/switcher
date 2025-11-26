<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!checkRole('lighthouse_maritime')) {
    header("Location: index.php?msg1");
    exit();
}

define('TITLE', 'Harbor Services');
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

.btn-add-service {
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

.btn-add-service:hover {
    background: #059669;
   	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.search-service-container {
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

.services-container {
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

.services-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.service-item {
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

.service-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #ffffff;
}

.service-item.ui-sortable-helper {
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    background: white;
    opacity: 0.95;
    cursor: grabbing !important;
    transform: scale(1.02);
    z-index: 1000;
}

.service-item.ui-sortable-placeholder {
    border: 2px dashed #3b82f6;
    background: linear-gradient(135deg, #eff6ff 25%, transparent 25%, transparent 50%, #eff6ff 50%, #eff6ff 75%, transparent 75%, transparent);
    background-size: 20px 20px;
    visibility: visible !important;
    height: 80px;
    opacity: 0.8;
}

.service-item.ui-sortable-placeholder .drag-handle,
.service-item.ui-sortable-placeholder .order-number,
.service-item.ui-sortable-placeholder .service-icon-display,
.service-item.ui-sortable-placeholder .service-info,
.service-item.ui-sortable-placeholder .service-meta {
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

.service-icon-display {
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

.service-info {
    flex: 1;
    min-width: 0;
}

.service-name {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.service-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.service-meta {
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

.service-actions {
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
    margin: 0;
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
        <div class="page-header mb-3">
            <div>
                <h1 class="page-title">
                    <i class="fa-solid fa-ship" style="color: #1a987d;"></i> Harbor Services
                </h1>
                <p class="mb-0 mt-1" style="font-size: 14px; color: #6b7280;">
                    Configure services available in the lighthouse system
                </p>
            </div>
            <button type="button" class="btn-add-service" data-bs-toggle="modal" data-bs-target="#lh-modal" onclick="lhOpenAddModal()">
                <i class="fa-solid fa-plus"></i>
                Add Service
            </button>
        </div>

        <!-- Search Container -->
        <div class="search-service-container">
            <div class="search-wrapper">
                <input type="text" 
                       id="services-search" 
                       class="form-control" 
                       placeholder="Search services by name or description..." 
                       autocomplete="off">
                <button type="button" id="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </div>
            <div class="search-results-info mt-2" id="search-results-info" style="display: none;">
                Showing <strong id="services-count">0</strong> of <strong id="total-services">0</strong> services
            </div>
        </div>

        <!-- Services Container -->
        <div class="services-container">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fa-solid fa-list-ol" style="color: #6b7280;"></i> Services
                </h3>
                <div class="drag-hint">
                    <i class="fa-solid fa-grip-vertical"></i>
                    <span>Drag to reorder</span>
                </div>
            </div>
            
            <ul class="services-list" id="lh-services-list">
                <!-- Services will be loaded here via AJAX -->
            </ul>
            
            <div id="lh-empty-state" class="empty-state" style="display: none;">
                <i class="fa-solid fa-ship"></i>
                <h4>No Services Yet</h4>
                <p>Create your first harbor service to get started</p>
            </div>
        </div>
        
        <!-- Save Indicator -->
        <div id="lh-save-indicator">
            <i class="fa-solid fa-check-circle me-2"></i>Changes saved successfully
        </div>
        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'templates/footer.php'; ?>
</main>

<!-- Add/Edit Service Modal -->
<div class="modal fade" id="lh-modal" tabindex="-1" aria-labelledby="lh-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lh-modal-title">
                    <i class="fa-solid fa-plus"></i> Add Service
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="lh-alert"></div>
                
                <form id="lh-form">
                    <input type="hidden" id="lh-service-id" name="service_id">
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="lh-name" class="form-label">
                                    Service Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="lh-name" name="service_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="lh-description" class="form-label">Description</label>
                                <textarea class="form-control" id="lh-description" name="service_description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lh-color" class="form-label">
                                            Color <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="lh-color" name="service_color" value="#007bff" required>
                                            <input type="text" class="form-control" id="lh-color-hex" placeholder="#007bff" maxlength="7">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lh-icon" class="form-label">
                                            Icon <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="lh-icon" name="service_icon" value="fa-solid fa-ship" required readonly>
                                            <span class="input-group-text" id="lh-icon-display">
                                                <i class="fa-solid fa-ship"></i>
                                            </span>
                                        </div>
                                        <small class="text-muted">Click the icon to choose</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="lh-active" name="is_active" checked>
                                <label class="form-check-label" for="lh-active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <!-- Right Column - Preview -->
                        <div class="col-md-4">
                            <label class="form-label">Preview</label>
                            <div class="text-center">
                                <div class="color-preview mx-auto" id="lh-preview">
                                    <i class="fa-solid fa-ship"></i>
                                </div>
                                <p class="mt-3 mb-0 fw-bold" id="lh-preview-name">Service Name</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-circle-xmark"></i> Cancel</button>
                <button type="submit" form="lh-form" class="btn btn-salmon" id="lh-save-btn">
                    <i class="fa-solid fa-cloud"></i> Save Service
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function lhLoadServices() {
    $.ajax({
        url: 'ajax/lh_services/read_services.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Services loaded:', response);
            
            if (response.status === 'success') {
                const servicesList = $('#lh-services-list');
                servicesList.empty();
                
                if (response.data.length === 0) {
                    servicesList.html(`
                        <div class="empty-state">
                            <i class="fa-solid fa-ship"></i>
                            <h4>No Services Yet</h4>
                            <p>Click "Add Service" to create your first harbor service</p>
                        </div>
                    `);
                    return;
                }
                
                response.data.forEach(function(service) {
                    const isActive = parseInt(service.is_active) === 1;
                    const statusClass = isActive ? 'active' : '';
                    const statusLabel = isActive ? 'Active' : 'Inactive';
                    
                    const serviceHtml = `
                        <li class="service-item mb-2" data-service-id="${service.service_id}">
                            <div class="drag-handle">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </div>
                            
                            <div class="service-icon-display" style="background-color: ${lhEscapeHtml(service.service_color)}">
                                <i class="${lhEscapeHtml(service.service_icon)}"></i>
                            </div>
                            
                            <div class="service-info">
                                <div class="service-name">${lhEscapeHtml(service.service_name)}</div>
                                ${service.service_description ? `<div class="service-description">${lhEscapeHtml(service.service_description)}</div>` : ''}
                            </div>
                            
                            <div class="service-meta">
                                <div class="status-toggle ${statusClass}" data-service-id="${service.service_id}" data-current-status="${service.is_active}">
                                    <div class="toggle-switch ${statusClass}"></div>
                                    <span class="status-label">${statusLabel}</span>
                                </div>
                                
                                <div class="service-actions">
                                    <button class="btn-action" onclick="lhEditService(${service.service_id})" title="Edit">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button class="btn-action delete" onclick="lhDeleteService(${service.service_id})" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </li>
                    `;
                    
                    servicesList.append(serviceHtml);
                });
                
                // Reset search after loading
                const searchValue = $('#services-search').val().trim();
                if (searchValue) {
                    filterServices(searchValue);
                } else {
                    $('#search-results-info').hide();
                }
                
                // Initialize sortable
                if (typeof $.fn.sortable !== 'undefined') {
                    servicesList.sortable({
                        handle: '.drag-handle',
                        cursor: 'grabbing',
                        opacity: 0.9,
                        placeholder: 'service-item ui-sortable-placeholder',
                        forcePlaceholderSize: true,
                        update: function(event, ui) {
                            const order = [];
                            $('.service-item').each(function(index) {
                                order.push({
                                    service_id: $(this).data('service-id'),
                                    service_order: index + 1
                                });
                            });
                            
                            console.log('New order:', order);
                            
                            $.ajax({
                                url: 'ajax/lh_services/reorder_services.php',
                                method: 'POST',
                                data: { order: JSON.stringify(order) },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        lhShowSaveIndicator();
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Failed to reorder services:', error);
                                    alert('Failed to save new order');
                                    lhLoadServices(); // Reload to reset order
                                }
                            });
                        }
                    });
                }
            } else {
                alert('Failed to load services: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load services:', error);
            $('#lh-services-list').html(`
                <div class="empty-state">
                    <i class="fa-solid fa-exclamation-triangle text-danger"></i>
                    <h4 class="text-danger">Error Loading Services</h4>
                    <p>Please refresh the page to try again</p>
                </div>
            `);
        }
    });
}

function lhShowSaveIndicator() {
    const indicator = $('#lh-save-indicator');
    indicator.fadeIn(300);
    setTimeout(() => {
        indicator.fadeOut(300);
    }, 2000);
}

function lhOpenAddModal() {
    $('#lh-modal-title').html('<i class="fa-solid fa-plus me-2"></i>Add Service');
    $('#lh-form')[0].reset();
    $('#lh-service-id').val('');
    $('#lh-color').val('#007bff');
    $('#lh-color-hex').val('#007bff');
    $('#lh-icon').val('fa-solid fa-ship');
    $('#lh-active').prop('checked', true);
    
    $("#lh-icon-display").html('<i class="fa-solid fa-ship"></i>');
    
    setTimeout(function() {
        $('#lh-icon').iconpicker('setIcon', 'fa-solid fa-ship');
        lhUpdateIconPreview();
    }, 100);
    
    $('#lh-alert').html('');
}

function lhEditService(id) {
    $.ajax({
        url: 'ajax/lh_services/get_service.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const service = response.data;
                
                console.log('Editing service:', service);
                console.log('Service icon value:', service.service_icon);
                
                $('#lh-modal-title').html('<i class="fa-solid fa-feather-pointed"></i> Edit Service');
                $('#lh-service-id').val(service.service_id);
                $('#lh-name').val(service.service_name);
                $('#lh-description').val(service.service_description || '');
                $('#lh-color').val(service.service_color);
                $('#lh-color-hex').val(service.service_color);
                $('#lh-active').prop('checked', service.is_active == 1);
                
                // Handle the icon - use full class or default
                let iconClass = service.service_icon || 'fa-solid fa-ship';
                
                // If the icon value is "0" or invalid, use default
                if (iconClass === '0' || iconClass === '' || iconClass === 'null') {
                    iconClass = 'fa-solid fa-ship';
                    console.warn('Invalid icon value, using default:', iconClass);
                }
                
                $('#lh-icon').val(iconClass);
                
                // Update the icon display with full class
                $("#lh-icon-display").html(`<i class="${iconClass}"></i>`);
                
                // Set the icon picker value
                $('#lh-icon').iconpicker('setIcon', iconClass);
                
                // Update preview
                lhUpdateIconPreview();
                
                $('#lh-alert').html('');
                $('#lh-modal').modal('show');
            } else {
                alert('Failed to load service: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load service:', error);
            alert('Failed to load service');
        }
    });
}

function lhDeleteService(id) {
    if (!confirm('Are you sure you want to delete this service?')) {
        return;
    }
    
    $.ajax({
        url: 'ajax/lh_services/delete_service.php',
        method: 'POST',
        data: { service_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                lhLoadServices();
                lhShowSaveIndicator();
            } else {
                alert(response.message || 'Failed to delete service');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to delete service:', error);
            alert('Failed to delete service');
        }
    });
}

function lhUpdateIconPreview() {
    const color = $('#lh-color').val();
    const icon = $('#lh-icon').val() || 'fa-solid fa-ship';
    const name = $('#lh-name').val() || 'Service Name';
    
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
    console.log('Lighthouse Services: Page loaded, initializing...');
    
    lhLoadServices();
    
    // Search/Filter functionality
    let allServices = [];
    let searchTimeout = null;
    
    function filterServices(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        if (searchTerm === '') {
            // Show all services
            $('.service-item').show();
            $('#search-results-info').hide();
            return;
        }
        
        let visibleCount = 0;
        
        $('.service-item').each(function() {
            const $item = $(this);
            const serviceName = $item.find('.service-name').text().toLowerCase();
            const serviceDescription = $item.find('.service-description').text().toLowerCase();
            
            if (serviceName.includes(searchTerm) || serviceDescription.includes(searchTerm)) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Update search results info
        const totalCount = $('.service-item').length;
        $('#services-count').text(visibleCount);
        $('#total-services').text(totalCount);
        $('#search-results-info').show();
        
        // Show empty state if no results
        if (visibleCount === 0) {
            if ($('#search-no-results').length === 0) {
                $('#lh-services-list').append(`
                    <div id="search-no-results" class="empty-state">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h4>No Services Found</h4>
                        <p>No services match your search criteria</p>
                    </div>
                `);
            }
        } else {
            $('#search-no-results').remove();
        }
    }
    
    // Search input handler with debounce
    $('#services-search').on('input', function() {
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
            filterServices(searchValue);
        }, 300);
    });
    
    // Search on Enter key
    $('#services-search').on('keypress', function(e) {
        if (e.which === 13) {
            clearTimeout(searchTimeout);
            filterServices($(this).val().trim());
        }
    });
    
    // Search button click handler
    $('#search-btn').on('click', function() {
        const $searchInput = $('#services-search');
        const $searchBtn = $(this);
        
        if ($searchBtn.hasClass('clear-mode')) {
            // Clear search
            $searchInput.val('');
            $searchBtn.html('<i class="fa-solid fa-magnifying-glass"></i> Search');
            $searchBtn.removeClass('clear-mode');
            filterServices('');
        } else {
            // Execute search
            filterServices($searchInput.val().trim());
        }
    });
    
    if (typeof $.fn.iconpicker !== 'undefined') {
        console.log('Lighthouse Services: Initializing icon picker...');
        
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
        const serviceId = $toggle.data('service-id');
        const currentStatus = parseInt($toggle.data('current-status'));
        const newStatus = currentStatus ? 0 : 1;
        
        console.log('Toggling status:', serviceId, 'from', currentStatus, 'to', newStatus);
        
        $toggle.css('pointer-events', 'none').css('opacity', '0.6');
        
        $.ajax({
            url: 'ajax/lh_services/toggle_service_status.php',
            method: 'POST',
            data: { 
                service_id: serviceId,
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
        
        const serviceName = $('#lh-name').val().trim();
        const serviceColor = $('#lh-color').val();
        const serviceIcon = $('#lh-icon').val();
        
        console.log('Submitting form with values:', {
            name: serviceName,
            color: serviceColor,
            icon: serviceIcon
        });
        
        if (!serviceName) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Service name is required.
                </div>
            `);
            return;
        }
        
        if (!serviceColor) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Service color is required.
                </div>
            `);
            return;
        }
        
        if (!serviceIcon) {
            $('#lh-alert').html(`
                <div class="alert alert-danger">
                    <i class="fa-solid fa-exclamation-triangle"></i> Service icon is required.
                </div>
            `);
            return;
        }
        
        const btn = $('#lh-save-btn');
        const originalBtnText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        $('#lh-alert').html('');
        
        const formData = $(this).serialize();
        const url = $('#lh-service-id').val() ? 
            'ajax/lh_services/update_service.php' : 
            'ajax/lh_services/create_service.php';
        
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
                    lhLoadServices();
                    lhShowSaveIndicator();
                } else {
                    $('#lh-alert').html(`
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to save service.'}
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