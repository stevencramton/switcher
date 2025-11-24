<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    header("Location: index.php?msg1");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? 0;
$is_admin = checkRole('lighthouse_keeper');

define('TITLE', 'Lighthouse - Keeper Signals');
include 'templates/header.php';

$url_dock = isset($_GET['dock']) && is_numeric($_GET['dock']) ? (int)$_GET['dock'] : null;
$url_state = isset($_GET['state']) && is_numeric($_GET['state']) ? (int)$_GET['state'] : null;
$url_filter = isset($_GET['filter']) ? $_GET['filter'] : null;
$url_action = isset($_GET['action']) ? $_GET['action'] : null;

$current_dock = null;
if (isset($_GET['dock']) && is_numeric($_GET['dock'])) {
    $dock_id = (int)$_GET['dock'];
    $dock_query = "SELECT dock_id, dock_name, dock_icon, dock_color FROM lh_docks WHERE dock_id = ? AND is_active = 1";
    $dock_stmt = mysqli_prepare($dbc, $dock_query);
    
    if ($dock_stmt) {
        mysqli_stmt_bind_param($dock_stmt, 'i', $dock_id);
        mysqli_stmt_execute($dock_stmt);
        $dock_result = mysqli_stmt_get_result($dock_stmt);
        
        if ($dock_result && mysqli_num_rows($dock_result) > 0) {
            $current_dock = mysqli_fetch_assoc($dock_result);
        }
        
        mysqli_stmt_close($dock_stmt);
    }
}

$current_state = null;
if (isset($_GET['state']) && is_numeric($_GET['state'])) {
    $state_id = (int)$_GET['state'];
    $state_query = "SELECT sea_state_id, sea_state_name, sea_state_icon, sea_state_color FROM lh_sea_states WHERE sea_state_id = ? AND is_active = 1";
    $state_stmt = mysqli_prepare($dbc, $state_query);
    
    if ($state_stmt) {
        mysqli_stmt_bind_param($state_stmt, 'i', $state_id);
        mysqli_stmt_execute($state_stmt);
        $state_result = mysqli_stmt_get_result($state_stmt);
        
        if ($state_result && mysqli_num_rows($state_result) > 0) {
            $current_state = mysqli_fetch_assoc($state_result);
        }
        
        mysqli_stmt_close($state_stmt);
    }
}

// Get active services for the dropdown
if ($is_admin) {
    $services_query = "SELECT service_id, service_name, service_icon, service_color FROM lh_services WHERE is_active = 1 ORDER BY service_order";
    $services_result = mysqli_query($dbc, $services_query);
}

?>

<style>
.signals-wrapper {
	margin: 0 auto;
	background: #f7f8fa;
	min-height: 100vh;
}

.signals-search {
	background: white;
	border-radius: 8px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.search-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16px;
}

.search-header h5 {
	font-size: 18px;
	font-weight: 600;
	color: #1f2937;
	margin: 0;
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
}

.search-wrapper button:hover {
	background: #2563eb;
}

.search-wrapper button.clear-mode {
	background: #E91E63;
}

.search-wrapper button.clear-mode:hover {
	background: #d51b5a;
}

.btn-send-signal {
	padding: 10px 24px;
	background: #10b981;
	color: white;
	border: none;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s;
	display: inline-flex;
	align-items: center;
	gap: 8px;
}

.btn-send-signal:hover {
	background: #059669;
	box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.signals-scroll-wrapper {
	position: relative;
	height: calc(100vh - 110px);
	overflow-y: auto;
	overflow-x: hidden;
	padding-right: 8px;
}

.signals-scroll-wrapper::-webkit-scrollbar {
	width: 8px;
}

.signals-scroll-wrapper::-webkit-scrollbar-track {
	background: #f1f1f1;
	border-radius: 10px;
}

.signals-scroll-wrapper::-webkit-scrollbar-thumb {
	background: #d9d9d9;
	border-radius: 10px;
}

.signals-scroll-wrapper::-webkit-scrollbar-thumb:hover {
	background: #9e9e9e;
}

.signals-container {
	background: white;
	border-radius: 8px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
	margin-bottom: 20px;
	overflow: hidden;
}

/* Bulk Actions Bar */
.bulk-actions-bar {
	display: none;
	background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
	color: white;
	padding: 14px 20px;
	align-items: center;
	justify-content: space-between;
	border-bottom: 2px solid #1d4ed8;
}

.bulk-actions-bar.active {
	display: flex;
	animation: slideDown 0.3s;
}

@keyframes slideDown {
	from { opacity: 0; max-height: 0; }
	to { opacity: 1; max-height: 100px; }
}

.bulk-actions-info {
	font-weight: 600;
	font-size: 14px;
	display: flex;
	align-items: center;
	gap: 10px;
}

.bulk-actions-buttons {
	display: flex;
	gap: 10px;
}

.bulk-action-btn {
	padding: 8px 18px;
	border: 2px solid white;
	background: transparent;
	color: white;
	border-radius: 6px;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s;
	align-items: center;
}

.bulk-action-btn:hover {
	background: white;
	color: #3b82f6;
}

.bulk-action-btn.danger:hover {
	background: #ef4444;
	border-color: #ef4444;
	color: white;
}

/* Table Styles */
.signals-table-wrapper {
	overflow-x: auto;
}

.signals-table {
	width: 100%;
	border-collapse: separate;
	border-spacing: 0;
	font-size: 14px;
}

.signals-table thead {
	background: #f9fafb;
	border-bottom: 2px solid #e5e7eb;
}

.signals-table thead th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #d1d5db;
    border-top: 1px solid #ffffff;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    position: sticky;
    top: 0;
    background: #f9fafb;
    z-index: 10;
}

.signals-table thead th:first-child {
	width: 50px;
	text-align: center;
	padding-left: 20px;
}

.signals-table tbody tr {
	border-bottom: 1px solid #f3f4f6;
	border-left: 4px solid transparent;
	cursor: pointer;
	transition: all 0.2s;
	position: relative;
	opacity: 0;
	animation: fadeInRow 0.3s forwards;
}

@keyframes fadeInRow {
	from {
		opacity: 0;
		transform: translateX(-10px);
	}
	to {
		opacity: 1;
		transform: translateX(0);
	}
}

.signals-table tbody tr:hover {
	background: #f9fafb;
}

.signals-table tbody tr.selected {
	background: #eff6ff;
}

.signals-table tbody td {
	padding: 12px 16px;
	vertical-align: middle;
	color: #1f2937;
	border-bottom: 1px solid #e5e7eb;
}

.signal-checkbox-cell {
	text-align: center;
	width: 50px;
	padding-left: 20px !important;
}

.signal-checkbox {
	width: 18px;
	height: 18px;
	cursor: pointer;
	border-radius: 4px;
	accent-color: #3b82f6;
}

.select-all-checkbox {
	width: 18px;
	height: 18px;
	cursor: pointer;
	border-radius: 4px;
	accent-color: #3b82f6;
}

.signal-number-cell {
	font-weight: 600;
	color: #1f2937;
	white-space: nowrap;
	font-family: 'Courier New', monospace;
	font-size: 13px;
	min-width: 150px;
}

.signal-title-cell {
	font-weight: 600;
	color: #1f2937;
	max-width: 350px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.signal-title-cell:hover {
	color: #3b82f6;
}

.signal-status-cell, .signal-priority-cell, .signal-dock-cell {
	white-space: nowrap;
}

.status-badge {
	display: inline-block;
	padding: 5px 12px;
	border-radius: 5px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.3px;
}

.priority-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 5px 12px;
	border-radius: 5px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.3px;
}

.dock-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 5px 12px;
	border-radius: 5px;
	font-size: 11px;
	font-weight: 600;
}

.signal-creator-cell, .signal-date-cell, .signal-assigned-cell, .signal-activity-cell {
	font-size: 13px;
	color: #6b7280;
	white-space: nowrap;
}

.signal-creator-cell i, .signal-date-cell i, .signal-assigned-cell i, .signal-activity-cell i {
	font-size: 11px;
	color: #9ca3af;
}

.no-results {
	text-align: center;
	padding: 60px 20px;
	color: #9ca3af;
}

.no-results i {
	font-size: 64px;
	margin-bottom: 20px;
	opacity: 0.5;
}

.no-results h4 {
	font-size: 20px;
	font-weight: 600;
	color: #6b7280;
	margin-bottom: 8px;
}

.no-results p {
	font-size: 14px;
	margin: 0;
}

.scroll-top {
	position: fixed;
	bottom: 50px;
	right: 30px;
	width: 50px;
	height: 50px;
	background: #3b82f6;
	color: white;
	border: none;
	border-radius: 50%;
	font-size: 18px;
	cursor: pointer;
	box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
	transition: all 0.3s;
	opacity: 0;
	visibility: hidden;
	z-index: 1000;
}

.scroll-top.visible {
	opacity: 1;
	visibility: visible;
}

.scroll-top:hover {
	background: #2563eb;
	transform: translateY(-3px);
	box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
}

.loading-shimmer {
	background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
	background-size: 200% 100%;
	animation: shimmer 1.5s infinite;
	height: 120px;
	border-radius: 8px;
	margin-bottom: 16px;
}

@keyframes shimmer {
	0% {
		background-position: -200% 0;
	}
	100% {
		background-position: 200% 0;
	}
}

.type-badge {
	font-size: 10px;
	padding: 3px 8px;
	border-radius: 4px;
	font-weight: 600;
	text-transform: uppercase;
	background: #e5e7eb;
	color: #6b7280;
}

.accordion-item {
	border: none;
	margin-bottom: 8px;
}

.accordion-button {
	display: flex;
	align-items: center;
	gap: 12px;
	background: white;
	border: 1px solid #e5e7eb;
	border-radius: 8px !important;
	padding: 12px 16px;
	cursor: pointer;
	transition: all 0.2s;
	font-size: 14px;
	font-weight: 600;
	color: #1f2937;
}

.accordion-button:not(.collapsed) {
	background: #eff6ff;
	border-color: #3b82f6;
	color: #1e40af;
	box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.accordion-button:hover {
	border-color: #d1d5db;
	box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.accordion-button:focus {
	box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
	border-color: #3b82f6;
}

.accordion-button::after {
	margin-left: auto;
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%236b7280'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-button:not(.collapsed)::after {
	background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%233b82f6'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-dept-icon {
	flex-shrink: 0;
	width: 32px;
	height: 32px;
	border-radius: 6px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 14px;
	color: white;
}

.accordion-dept-info {
	flex: 1;
	min-width: 0;
}

.accordion-dept-name {
	font-size: 14px;
	font-weight: 700;
	margin-bottom: 2px;
}

.accordion-dept-desc {
	font-size: 11px;
	opacity: 0.8;
}

.accordion-body {
	padding: 0;
	border: none;
}

.accordion-collapse {
	border: none;
}

.status-subfilter {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 16px 10px 10px;
	background: #f9fafb;
	border-left: 3px solid transparent;
	cursor: pointer;
	transition: all 0.2s;
	font-size: 13px;
}

.status-subfilter:hover {
	background: #f3f4f6;
	border-left-color: #d1d5db;
}

.status-subfilter.active {
	background: #eff6ff;
	border-left-color: #3b82f6;
	font-weight: 600;
}

.status-subfilter-dot {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	flex-shrink: 0;
}

.status-subfilter-name {
	flex: 1;
	color: #374151;
}

.status-subfilter.active .status-subfilter-name {
	color: #1e40af;
}

.status-subfilter-count {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 20px;
	height: 20px;
	background: #e5e7eb;
	color: #6b7280;
	border-radius: 10px;
	font-size: 10px;
	font-weight: 700;
	padding: 0 6px;
}

.status-subfilter.active .status-subfilter-count {
	background: #3b82f6;
	color: white;
}

/* Content View Transitions */
.content-view {
	display: none;
	opacity: 0;
	transition: opacity 0.3s ease-in-out;
}

.content-view.active {
	display: block;
	opacity: 1;
}

.content-view.fading-out {
	opacity: 0;
}

.content-view.fading-in {
	display: block;
	animation: fadeIn 0.3s ease-in-out forwards;
}

@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

/* Create Signal Container */
.create-signal-container {
	background: white;
	border-radius: 8px;
	padding: 32px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
	margin: 0 auto;
}

.create-signal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 32px;
	padding-bottom: 16px;
	border-bottom: 2px solid #e5e7eb;
}

.create-signal-header h4 {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
	color: #1f2937;
}

.form-actions {
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	margin-top: 24px;
	
}

.form-actions .btn {
	padding: 10px 24px;
	font-weight: 600;
}

/* File Upload Styles */
.upload-container {
	background: white;
	border-radius: 8px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.upload-header {
	font-size: 16px;
	font-weight: 600;
	color: #1f2937;
	margin-bottom: 15px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.drop-zone {
	border: 2px dashed #d1d5db;
	border-radius: 8px;
	padding: 30px 20px;
	text-align: center;
	background-color: #f9fafb;
	transition: all 0.3s ease;
	cursor: pointer;
	position: relative;
}

.drop-zone:hover {
	background-color: #f3f4f6;
	border-color: #3b82f6;
}

.drop-zone.drag-over {
	background-color: #eff6ff;
	border-color: #3b82f6;
	border-style: solid;
}

.drop-zone-icon {
	font-size: 40px;
	color: #3b82f6;
	margin-bottom: 12px;
}

.drop-zone-text {
	color: #374151;
	font-size: 14px;
	font-weight: 500;
	margin-bottom: 4px;
}

.drop-zone-text .browse-link {
	color: #3b82f6;
	cursor: pointer;
	text-decoration: underline;
}

.drop-zone-text .browse-link:hover {
	color: #2563eb;
}

.drop-zone-hint {
	color: #9ca3af;
	font-size: 12px;
}

.file-input-hidden {
	display: none;
}

.upload-statistics {
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	padding: 15px;
	margin-top: 15px;
}

.upload-stat-title {
	font-size: 13px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 10px;
}

.upload-stat-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 6px 0;
	font-size: 13px;
}

.upload-stat-label {
	color: #6b7280;
}

.upload-stat-value {
	font-weight: 600;
	color: #1f2937;
}

.uploaded-files-list {
	margin-top: 15px;
}

.uploaded-file-item {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px;
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	margin-bottom: 8px;
	font-size: 13px;
}

.file-icon {
	color: #6b7280;
	font-size: 18px;
	flex-shrink: 0;
}

.file-name {
	flex: 1;
	color: #374151;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

.file-size {
	color: #9ca3af;
	font-size: 11px;
	flex-shrink: 0;
}

.file-remove {
	background: none;
	border: none;
	color: #ef4444;
	cursor: pointer;
	padding: 4px;
	font-size: 16px;
	flex-shrink: 0;
	transition: all 0.2s;
}

.file-remove:hover {
	color: #dc2626;
	transform: scale(1.1);
}

.upload-note {
	margin-top: 12px;
	padding: 10px;
	background: #fef3c7;
	border: 1px solid #fcd34d;
	border-radius: 6px;
	font-size: 12px;
	color: #92400e;
}

.upload-note i {
	color: #f59e0b;
	margin-right: 6px;
}

/* Signal Receipt Styles */
.receipt-container {
	background: white;
	border-radius: 8px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.06);
	max-width: 600px;
	margin: 0 auto;
}

.receipt-header {
	background: linear-gradient(135deg, #10b981 0%, #059669 100%);
	color: white;
	padding: 30px;
	text-align: center;
	border-radius: 8px 8px 0 0;
}

.receipt-header i {
	font-size: 48px;
	margin-bottom: 15px;
	animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
	from {
		transform: scale(0);
		opacity: 0;
	}
	to {
		transform: scale(1);
		opacity: 1;
	}
}

.receipt-header h3 {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
}

.receipt-body {
	padding: 30px;
}

.receipt-info-box {
	background: #f9fafb;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 25px;
}

.receipt-info-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 12px 0;
	border-bottom: 1px solid #e5e7eb;
}

.receipt-info-row:last-child {
	border-bottom: none;
	padding-bottom: 0;
}

.receipt-info-label {
	font-size: 13px;
	color: #6b7280;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.receipt-info-value {
	font-size: 16px;
	color: #1f2937;
	font-weight: 700;
}

.receipt-actions {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.receipt-action-btn {
	padding: 12px 20px;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 600;
	border: none;
	cursor: pointer;
	transition: all 0.2s;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
}

.receipt-action-btn.primary {
	background: #3b82f6;
	color: white;
}

.receipt-action-btn.primary:hover {
	background: #2563eb;
	box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.receipt-action-btn.secondary {
	background: #6b7280;
	color: white;
}

.receipt-action-btn.secondary:hover {
	background: #4b5563;
}

.receipt-action-btn.success {
	background: #10b981;
	color: white;
}

.receipt-action-btn.success:hover {
	background: #059669;
}

.receipt-action-btn.outline {
	background: white;
	color: #3b82f6;
	border: 2px solid #3b82f6;
}

.receipt-action-btn.outline:hover {
	background: #eff6ff;
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
		
		<div class="signals-wrapper">
		
			<div class="signals-search p-3 mb-3">
				<div class="search-header">
				    <div style="display: flex; align-items: center; gap: 15px;">
				        <img src="img/lighthouse/lighthouse_icon.png" height="60" width="60">
				        <div>
				            <h5 style="margin: 0;">Lighthouse</h5>
				            <?php if ($current_dock): ?>
				                <div style="font-size: 14px; color: #6c757d; font-weight: normal; margin-top: 2px;">
				                    <i class="fa-solid <?php echo htmlspecialchars($current_dock['dock_icon']); ?>" 
				                       style="color: <?php echo htmlspecialchars($current_dock['dock_color']); ?>;"></i>
				                    <?php echo htmlspecialchars($current_dock['dock_name']); ?>
                    
				                    <?php if ($current_state): ?>
				                        <span style=""> 
						                    <i class="fa-solid <?php echo htmlspecialchars($current_state['sea_state_icon']); ?>" 
						                       style="color: <?php echo htmlspecialchars($current_state['sea_state_color']); ?>;"></i>
				                                <?php echo htmlspecialchars($current_state['sea_state_name']); ?>
				                            
				                        </span>
				                    <?php endif; ?>
				                </div>
				            <?php else: ?>
				                <div style="font-size: 14px; color: #6c757d; font-weight: normal; margin-top: 2px;">
				                    Welcome to Keepers Watch!
				                </div>
				            <?php endif; ?>
				        </div>
				    </div>
				    <button type="button" class="btn-send-signal" id="btn-send-signal">
				        <i class="fa-solid fa-ship"></i> Signal the Crew
				    </button>
				</div>
				
				<div class="search-wrapper">
					<input type="text" 
						   id="signals-search" 
						   class="form-control" 
						   placeholder="Search by signal number, title, or message..." 
						   autocomplete="off">
					<button type="button" id="search-btn">
						<i class="fa-solid fa-magnifying-glass"></i> Search
					</button>
				</div>
			</div>
			
			<div class="row">
				
				<div class="col-md-12">
					<!-- Signals List View -->
					<div id="signals-list-view" class="content-view active">
						<div class="signals-scroll-wrapper" id="signals-scroll-wrapper">
							<h6 class="filter-section-title" id="results-count" style="display: none;">
								Showing <strong>0</strong> signals
							</h6>
							
							<div class="signals-container">
								<div id="bulk-actions-bar" class="bulk-actions-bar">
									<div class="bulk-actions-info">
										<i class="fa-solid fa-check-circle"></i>
										<span id="selected-count">0</span> signal(s) selected
									</div>
									<div class="bulk-actions-buttons">
									    <button class="bulk-action-btn" id="bulk-move-btn">
									        <i class="fa-solid fa-right-left"></i> Move to Dock
									    </button>
									    <button class="bulk-action-btn" id="bulk-status-btn">
									        <i class="fa-solid fa-flag"></i> Change Status
									    </button>
									    <button class="bulk-action-btn" id="bulk-assign-btn">
									        <i class="fa-solid fa-user-tag"></i> Assign Keeper
									    </button>
									  	<button class="bulk-action-btn danger" id="bulk-delete-btn">
									        <i class="fa-solid fa-trash"></i> Delete
									    </button>
									    <button class="bulk-action-btn" id="bulk-deselect-btn">
									        <i class="fa-solid fa-circle-xmark"></i> Cancel
									    </button>
									</div>
								</div>
								
								<div class="signals-table-wrapper">
									<table class="signals-table">
										<thead>
											<tr>
												<th>
													<input type="checkbox" class="select-all-checkbox" id="select-all" title="Select All">
												</th>
												<th>Signal</th>
												<th>Subject</th>
												<th>Status</th>
												<th>Priority</th>
												<th>Dock</th>
												<th>Caster</th>
												<th>Created</th>
												<th>Activity</th>
												<th>Keeper</th>
											</tr>
										</thead>
										<tbody id="signals-content">
										</tbody>
									</table>
								</div>
								
								<div id="no-results" class="no-results" style="display: none;">
									<i class="fa-solid fa-ship"></i>
									<h4>No Signals found</h4>
									<p>Try adjusting your search or filters</p>
								</div>
							</div>
						</div>
						<button class="scroll-top" id="scrollTop">
							<i class="fa-solid fa-arrow-up"></i>
						</button>
					</div>
					
					<div id="create-signal-view" class="content-view">
						<div class="create-signal-container p-3 mb-3">
							<div class="create-signal-header mb-3">
								<h4><i class="fa-solid fa-water me-2"></i>Signal the Crew</h4>
								<button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-signal">
									<i class="fa-solid fa-arrow-left me-1"></i> Back to Signals
								</button>
							</div>
						</div>
						
						<div class="row gx-3">
							<div class="col-md-8">
								<div class="create-signal-container p-3">
									<form id="createSignalForm">
										<div id="createSignalAlert"></div>
										<div class="mb-3">
											<label class="form-label">Dock <span class="text-danger">*</span></label>
											<select name="dock_id" id="dock_select" class="form-select" required>
												<option value="">Select Dock</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Signal Type <span class="text-danger">*</span></label>
											<select name="signal_type" class="form-select" required>
												<option value="">Select Type</option>
												<option value="bug_report">Bug Report</option>
												<option value="feature_request">Feature Request</option>
												<option value="feedback">Feedback</option>
												<option value="other">Other</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Title <span class="text-danger">*</span></label>
											<input type="text" name="title" class="form-control" 
												   placeholder="Brief description of the issue" required maxlength="255">
										</div>
										<div class="mb-3">
											<label class="form-label">Message <span class="text-danger">*</span></label>
											<textarea id="keeper_signal_message_content" name="message" class="form-control" rows="4" 
													  placeholder="Provide detailed information about your signal..."></textarea>
											<small class="text-muted">Be as specific as possible to help us address your request.</small>
										</div>
										
										<?php if ($is_admin): ?>
											<div class="row">
																					<div class="col-md-6 mb-3">
																						<label class="form-label">Priority</label>
																						<select name="priority_id" id="priority_select" class="form-select">
																						</select>
																					</div>
																					<div class="col-md-6 mb-3">
																						<label class="form-label">Service</label>
																						<select name="service_id" class="form-select">
																							<option value="">No Service</option>
																							<?php
																							if (isset($services_result)) {
																								mysqli_data_seek($services_result, 0);
																								while ($service = mysqli_fetch_assoc($services_result)):
																							?>
																							<option value="<?php echo $service['service_id']; ?>">
																								<?php echo htmlspecialchars($service['service_name']); ?>
																							</option>
																							<?php 
																								endwhile;
																							}
																							?>
																						</select>
																					</div>
																				</div>
																				<div class="row">
																					<div class="col-md-12 mb-3">
																						<label class="form-label">Assign To</label>
																						<select name="keeper_assigned" class="form-select">
																							<option value="">No Keeper</option>
																							<?php
																							$admin_query = "SELECT u.id, u.first_name, u.last_name 
																							                FROM users u 
																							                INNER JOIN roles_dev r ON u.role_id = r.role_id 
																							                WHERE r.lighthouse_keeper = 1 AND u.account_delete = 0 
																							                ORDER BY u.first_name, u.last_name";
																							$admin_result = mysqli_query($dbc, $admin_query);
																							while ($admin = mysqli_fetch_assoc($admin_result)):
																							?>
																							<option value="<?php echo $admin['id']; ?>">
																								<?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
																							</option>
																							<?php endwhile; ?>
																						</select>
																					</div>
																				</div>
																				<?php endif; ?>
										
										<div class="form-actions">
											<button type="button" class="btn btn-secondary btn-sm" id="btn-cancel-signal-2">
												<i class="fa-solid fa-times"></i> Cancel
											</button>
											<button type="submit" class="btn btn-primary btn-sm" id="createSignalBtn">
												<i class="fa-solid fa-paper-plane"></i> Signal the Crew
											</button>
										</div>
									</form>
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="upload-container p-3">
									<div class="upload-header">
										<i class="fa-solid fa-paperclip"></i>
										Attachments
									</div>
									
									<div class="drop-zone" id="signal-drop-zone">
										<input type="file" class="file-input-hidden" id="signal-file-input" accept=".jpg,.jpeg,.png,.gif,.pdf" multiple>
										<div class="drop-zone-icon">
											<i class="fa-solid fa-cloud-arrow-up"></i>
										</div>
										<div class="drop-zone-text">
											Drag & Drop or <span class="browse-link" onclick="document.getElementById('signal-file-input').click()">browse</span>
										</div>
										<div class="drop-zone-hint">
											JPG, PNG, GIF, PDF (Max 5MB each)
										</div>
									</div>
									
									<div class="upload-statistics">
										<div class="upload-stat-title">Upload Summary</div>
										<div class="upload-stat-row">
											<span class="upload-stat-label">Files Ready:</span>
											<span class="upload-stat-value" id="files-count">0</span>
										</div>
										<div class="upload-stat-row">
											<span class="upload-stat-label">Total Size:</span>
											<span class="upload-stat-value" id="total-size">0 KB</span>
										</div>
									</div>
									
									<div class="uploaded-files-list" id="uploaded-files-list">
										<!-- Uploaded files will appear here -->
									</div>
									
									<div class="upload-note">
										<i class="fa-solid fa-info-circle"></i>
										<strong>Note:</strong> Files will be attached when you submit the signal.
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div id="signal-receipt-view" class="content-view">
					<div class="receipt-container p-3">
						<div class="receipt-header">
							<i class="fa-solid fa-circle-check"></i>
							<h3>Signal Sent Successfully!</h3>
							<p style="margin: 10px 0 0 0; opacity: 0.9;">Your signal has been received by the crew</p>
						</div>
						
						<div class="receipt-body">
							<div class="receipt-info-box">
								<div class="receipt-info-row">
									<span class="receipt-info-label">Signal Number</span>
									<span class="receipt-info-value" id="receipt-signal-number">-</span>
								</div>
								<div class="receipt-info-row" id="receipt-attachments-row" style="display: none;">
									<span class="receipt-info-label">Attachments</span>
									<span class="receipt-info-value" id="receipt-attachments-count">-</span>
								</div>
								<div class="receipt-info-row">
									<span class="receipt-info-label">Status</span>
									<span class="receipt-info-value" style="color: #10b981;">
										<i class="fa-solid fa-circle-dot"></i> Active
									</span>
								</div>
							</div>
							
							<div class="receipt-actions">
								<button type="button" class="receipt-action-btn primary" id="receipt-copy-link">
									<i class="fa-solid fa-copy"></i> Copy Signal Link
								</button>
								<button type="button" class="receipt-action-btn success" id="receipt-view-signal">
									<i class="fa-solid fa-eye"></i> View Signal
								</button>
								<button type="button" class="receipt-action-btn secondary" id="receipt-go-home">
									<i class="fa-solid fa-house"></i> Return to Keeper's Watch
								</button>
								<button type="button" class="receipt-action-btn outline" id="receipt-create-another">
									<i class="fa-solid fa-plus"></i> Create Another Signal
								</button>
							</div>
						</div>
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

<!-- Bulk Move Modal -->
<div class="modal fade" id="bulkMoveModal" tabindex="-1" aria-labelledby="bulkMoveModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkMoveModalLabel">
					<i class="fa-solid fa-anchor me-2"></i>Move Signals to Dock
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="bulkMoveAlert"></div>
				<p class="mb-3">
					Select a dock to move <strong id="move-signal-count">0</strong> signal(s) to:
				</p>
				<div class="mb-3">
					<label for="bulk-move-dock-select" class="form-label">Target Dock</label>
					<select id="bulk-move-dock-select" class="form-select">
						<option value="">Select a dock...</option>
						<option value="null">Unassign Dock (Remove from all docks)</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmBulkMove">
					<i class="fa-solid fa-anchor"></i> Move Signals
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Bulk Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1" aria-labelledby="bulkAssignModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkAssignModalLabel">
					<i class="fa-solid fa-user-tag me-2"></i>Assign Lighthouse Keeper
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="bulkAssignAlert"></div>
				<p class="mb-3">
					Select a lighthouse keeper to assign <strong id="assign-signal-count">0</strong> signal(s) to:
				</p>
				<div class="mb-3">
					<label for="bulk-assign-keeper-select" class="form-label">Lighthouse Keeper</label>
					<select id="bulk-assign-keeper-select" class="form-select">
						<option value="">Select a keeper...</option>
						<option value="null">Unassign (No Keeper)</option>
					</select>
				</div>
				<div class="alert alert-info">
					<i class="fa-solid fa-info-circle me-2"></i>
					<small>Only administrators can be assigned as lighthouse keepers.</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmBulkAssign">
					<i class="fa-solid fa-user-tag"></i> Assign Keeper
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Bulk Change Status Modal -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkStatusModalLabel">
					<i class="fa-solid fa-flag me-2"></i>Change Signal Status
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="bulkStatusAlert"></div>
				<p class="mb-3">
					Select a new status for <strong id="status-signal-count">0</strong> signal(s):
				</p>
				<div class="mb-3">
					<label for="bulk-status-select" class="form-label">New Status</label>
					<select id="bulk-status-select" class="form-select">
						<option value="">Select a status...</option>
					</select>
				</div>
				<div class="alert alert-info">
					<i class="fa-solid fa-info-circle me-2"></i>
					<small>The status will be updated for all selected signals.</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmBulkStatus">
					<i class="fa-solid fa-flag"></i> Change Status
				</button>
			</div>
		</div>
	</div>
</div>

<script src="https://cdn.tiny.cloud/1/6i7udj9tuqovoj6lp5jpkopu2phxpzqoe6g35gx49wbr3v1u/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<script>
let currentFilters = {
	dock: <?php echo $url_dock ? $url_dock : "'all'"; ?>,
	state: <?php echo $url_state ? $url_state : "null"; ?>,
	priority: null,
	owner: <?php echo $url_filter ? "'" . $url_filter . "'" : "null"; ?>,
	search: ''
};

let beaconsData = [];
let countsData = {};
let selectedSignals = [];
let pendingFiles = [];

function handleRowClick(event, signalId) {
	if ($(event.target).is('.signal-checkbox') || $(event.target).is('.signal-checkbox-cell')) {
		return;
	}
	window.location.href = `lighthouse_keeper_view.php?id=${signalId}`;
}

function handleCheckboxChange(signalId) {
	const checkbox = $(`.signal-checkbox[data-signal-id="${signalId}"]`);
	const row = checkbox.closest('tr');
	
	if (checkbox.prop('checked')) {
		if (!selectedSignals.includes(signalId)) {
			selectedSignals.push(signalId);
			row.addClass('selected');
		}
	} else {
		selectedSignals = selectedSignals.filter(id => id !== signalId);
		row.removeClass('selected');
	}
	
	updateBulkActionsBar();
	updateSelectAllCheckbox();
}

function updateBulkActionsBar() {
	const bar = $('#bulk-actions-bar');
	const count = selectedSignals.length;
	
	$('#selected-count').text(count);
	
	if (count > 0) {
		bar.addClass('active');
	} else {
		bar.removeClass('active');
	}
}

function updateSelectAllCheckbox() {
	const totalCheckboxes = $('.signal-checkbox').length;
	const checkedCheckboxes = $('.signal-checkbox:checked').length;
	const selectAllCheckbox = $('#select-all');
	
	if (checkedCheckboxes === 0) {
		selectAllCheckbox.prop('checked', false);
		selectAllCheckbox.prop('indeterminate', false);
	} else if (checkedCheckboxes === totalCheckboxes) {
		selectAllCheckbox.prop('checked', true);
		selectAllCheckbox.prop('indeterminate', false);
	} else {
		selectAllCheckbox.prop('checked', false);
		selectAllCheckbox.prop('indeterminate', true);
	}
}

function loadSignals() {
	$('#signals-content').html(
		'<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading signals...</p></td></tr>'
	);
	
	$.ajax({
		url: 'ajax/lighthouse_keeper/read_keeper_signals.php',
		method: 'GET',
		data: currentFilters,
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				if (response.data && response.data.length > 0) {
					renderSignals(response.data);
					countsData = response.counts;
					updateCounts(response.counts);
					updateResultsCount(response.data.length);
				} else {
					showNoResults();
				}
				
				// Refresh sidebar counts to keep them in sync
				if (typeof loadLighthouseCounts === 'function') {
					loadLighthouseCounts();
				}
			} else {
				showNoResults();
			}
		},
		error: function(xhr, status, error) {
			$('#signals-content').html(
				'<tr><td colspan="9" class="text-center text-danger"><i class="fa-solid fa-exclamation-triangle fa-2x mb-3"></i><p><strong>Error loading signals</strong></p><p>Check browser console (F12) for details</p></td></tr>'
			);
		}
	});
}

function renderDockAccordions(docks) {
	container.empty();
	
	docks.forEach((dept, index) => {
		const accordionId = `dept-${dept.dock_id}`;
		const collapseId = `collapse-${dept.dock_id}`;
		
		let statusHtml = '';
		beaconsData.forEach(status => {
			statusHtml += `
				<div class="status-subfilter" 
					 data-dept-id="${dept.dock_id}" 
					 data-status-id="${status.sea_state_id}"
					 data-type="dept-status">
					<div class="status-subfilter-dot" style="background-color: ${status.sea_state_color};"></div>
					<div class="status-subfilter-name">${escapeHtml(status.sea_state_name)}</div>
					<span class="status-subfilter-count" id="count-dept-${dept.dock_id}-status-${status.sea_state_id}">0</span>
				</div>
			`;
		});
		
		const accordionItem = `
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading-${accordionId}">
					<button class="accordion-button collapsed" type="button" 
							data-bs-toggle="collapse" 
							data-bs-target="#${collapseId}" 
							aria-expanded="false" 
							aria-controls="${collapseId}">
						<div class="accordion-dept-icon" style="background-color: ${dept.dock_color};">
							<i class="fa-solid ${dept.dock_icon}"></i>
						</div>
						<div class="accordion-dept-info">
							<div class="accordion-dept-name">${escapeHtml(dept.dock_name)}</div>
							<div class="accordion-dept-desc">${escapeHtml(dept.dock_description || '')}</div>
						</div>
						<span class="filter-count" id="count-dept-${dept.dock_id}">0</span>
					</button>
				</h2>
				<div id="${collapseId}" 
					 class="accordion-collapse collapse" 
					 aria-labelledby="heading-${accordionId}" 
					 data-bs-parent="#dockAccordion">
					<div class="accordion-body">
						${statusHtml}
					</div>
					
				</div>
			</div>
		`;
		
		container.append(accordionItem);
	});
	
	if (countsData && Object.keys(countsData).length > 0) {
		updateCounts(countsData);
	}
}

function renderPriorityFilters(priorities) {
	container.empty();
	
	const icons = {
		'Low': 'fa-flag',
		'Medium': 'fa-circle-exclamation',
		'High': 'fa-gauge-high',
		'Critical': 'fa-triangle-exclamation'
	};
	
	priorities.forEach(priority => {
		const icon = icons[priority.priority_name] || 'fa-flag';
		const filterCard = `
			<div class="filter-card" data-filter="${priority.priority_id}" data-type="priority">
				<div class="filter-icon" style="background: ${priority.priority_color}20; color: ${priority.priority_color};">
					<i class="fa-solid ${icon}"></i>
				</div>
				<div class="filter-content">
					<div class="filter-title">${escapeHtml(priority.priority_name)} Priority</div>
				</div>
				<span class="filter-count" id="count-priority-${priority.priority_id}">0</span>
			</div>
		`;
		container.append(filterCard);
	});
}

function populateDockSelect(docks) {
	const select = $('#dock_select');
	docks.forEach(dept => {
		select.append(`<option value="${dept.dock_id}">${escapeHtml(dept.dock_name)}</option>`);
	});
}

function populatePrioritySelect(priorities) {
	const select = $('#priority_select');
	priorities.forEach(priority => {
		const selected = priority.priority_name === 'Medium' ? 'selected' : '';
		select.append(`<option value="${priority.priority_id}" ${selected}>${escapeHtml(priority.priority_name)}</option>`);
	});
}

function renderSignals(signals) {
	const tbody = $('#signals-content');
	const noResults = $('#no-results');
	
	tbody.empty();
	noResults.hide();
	selectedSignals = [];
	updateBulkActionsBar();
	$('#select-all').prop('checked', false);
	
	if (signals.length === 0) {
		showNoResults();
		return;
	}
	
	signals.forEach((signal, index) => {
		const signalRow = createSignalRow(signal, index);
		tbody.append(signalRow);
	});
}

function createSignalRow(signal, index) {
	const assignedDisplay = signal.assigned_name 
		? `<i class="fa-solid fa-user-tag"></i> ${escapeHtml(signal.assigned_name)}`
		: '<span style="color: #d1d5db;">No Keeper</span>';
	
	const dockDisplay = signal.dock_name 
		? `<span class="dock-badge" style="background-color: ${signal.dock_color}20; color: ${signal.dock_color};">
			<i class="${signal.dock_icon}"></i>
			${escapeHtml(signal.dock_name)}
		   </span>`
		: '<span class="dock-badge" style="background-color: #6c757d20; color: #6c757d;"><i class="fa-solid fa-circle-question"></i> Unassigned</span>';
	
	return `
		<tr data-signal-id="${signal.signal_id}" 
			style="animation-delay: ${index * 0.03}s; border-left-color: ${signal.priority_color};"
			onclick="handleRowClick(event, ${signal.signal_id})">
			<td class="signal-checkbox-cell" onclick="event.stopPropagation()">
				<input type="checkbox" 
					   class="signal-checkbox" 
					   data-signal-id="${signal.signal_id}"
					   onchange="handleCheckboxChange(${signal.signal_id})">
			</td>
			<td class="signal-number-cell">
				<i class="fa-solid fa-hashtag me-1"></i>${escapeHtml(signal.signal_number)}
			</td>
			<td class="signal-title-cell" title="${escapeHtml(signal.title)}">
				${escapeHtml(signal.title)}
			</td>
			<td class="signal-status-cell">
				<span class="status-badge" style="background-color: ${signal.sea_state_color}; color: white;">
					<i class="${signal.sea_state_icon || 'fa-solid fa-circle'}"></i>
					${escapeHtml(signal.sea_state_name)}
				</span>
			</td>
			<td class="signal-priority-cell">
				<span class="priority-badge" style="background-color: ${signal.priority_color}; color: white;">
					<i class="${signal.priority_icon || 'fa-solid fa-flag'}"></i>
					${escapeHtml(signal.priority_name)}
				</span>
			</td>
			<td class="signal-dock-cell">
				${dockDisplay}
			</td>
				<td class="signal-creator-cell">
					<i class="fa-solid fa-user"></i> ${escapeHtml(signal.creator_name)}
				</td>
				<td class="signal-date-cell">
					<i class="fa-solid fa-clock"></i> ${signal.sent_date_formatted}
				</td>
				<td class="signal-activity-cell">
					<i class="bi bi-activity"></i> ${signal.updated_date_formatted}
				</td>
				<td class="signal-assigned-cell">
					${assignedDisplay}
				</td>
		</tr>
	`;
}

function updateCounts(counts) {
	$('#count-all').text(counts.all || 0);
	$('#count-my-signals').text(counts.my_signals || 0);
	$('#count-assigned').text(counts.assigned || 0);
	$('#count-closed').text(counts.closed || 0);
	
	// Also update sidebar Quick Access closed count
	if (counts.quick_access && counts.quick_access.closed !== undefined) {
		$('#quick-closed-signals').text(counts.quick_access.closed || 0);
	}
	
	if (counts.docks) {
		Object.keys(counts.docks).forEach(deptId => {
			const count = counts.docks[deptId] || 0;
			const selector = `#count-dept-${deptId}`;
			$(selector).text(count);
		});
	}
	
	if (counts.dept_status) {
		Object.keys(counts.dept_status).forEach(key => {
			const [deptId, statusId] = key.split('-');
			const count = counts.dept_status[key] || 0;
			const selector = `#count-dept-${deptId}-status-${statusId}`;
			$(selector).text(count);
		});
	}
	
	if (counts.priorities) {
		Object.keys(counts.priorities).forEach(priorityId => {
			$(`#count-priority-${priorityId}`).text(counts.priorities[priorityId] || 0);
		});
	}
}

function updateResultsCount(count) {
	$('#results-count').html(`Showing <strong>${count}</strong> signal${count !== 1 ? 's' : ''}`).show();
}

function showNoResults() {
    $('#signals-content').empty();
    $('#no-results').show();
    $('#results-count').html('Showing <strong>0</strong> signals').show();  // Now shows 0
}

function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

$(document).ready(function() {
	tinymce.init({
		selector: '#keeper_signal_message_content',
		plugins: [
			'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 
			'searchreplace', 'table', 'visualblocks', 'wordcount'
		],
		toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
		height: 300,
		menubar: false,
		statusbar: false
	});
	
	document.addEventListener('focusin', (e) => {
		if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
			e.stopImmediatePropagation();
		}
	});
	
	function switchToCreateView() {
		$('.signals-search').hide();
		$('#signals-list-view').removeClass('active').addClass('fading-out');
		setTimeout(function() {
			$('#signals-list-view').removeClass('fading-out');
			$('#create-signal-view').addClass('fading-in');
			setTimeout(function() {
				$('#create-signal-view').removeClass('fading-in').addClass('active');
			}, 300);
		}, 300);
	}
	
	function switchToListView() {
		$('.signals-search').show();
		$('#create-signal-view').removeClass('active').addClass('fading-out');
		setTimeout(function() {
			$('#create-signal-view').removeClass('fading-out');
			$('#signals-list-view').addClass('fading-in');
			setTimeout(function() {
				$('#signals-list-view').removeClass('fading-in').addClass('active');
			}, 300);
		}, 300);
	}
	
	$('#btn-send-signal').on('click', function() {
		switchToCreateView();
		$('#createSignalForm')[0].reset();
		$('#createSignalAlert').html('');
		pendingFiles = [];
		$('#uploaded-files-list').html('');
		updateFileStatistics();
	});
	
	$('#btn-cancel-signal, #btn-cancel-signal-2').on('click', function() {
		switchToListView();
	});
	
	<?php if ($url_action === 'create'): ?>
	switchToCreateView();
	<?php else: ?>
	$('#signals-list-view').addClass('active');
	loadSignals();
	<?php endif; ?>
	
	$.ajax({
		url: 'ajax/lh_priorities/get_priorities.php',
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				populatePrioritySelect(response.data);
			}
		}
	});
	
	$.ajax({
		url: 'ajax/lh_docks/get_lighthouse_docks.php',
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success') {
				populateDockSelect(response.data);
			}
		}
	});
	
	$('#select-all').on('change', function() {
		const isChecked = $(this).prop('checked');
		$('.signal-checkbox').each(function() {
			$(this).prop('checked', isChecked);
			const signalId = parseInt($(this).data('signal-id'));
			const row = $(this).closest('tr');
			
			if (isChecked) {
				if (!selectedSignals.includes(signalId)) {
					selectedSignals.push(signalId);
					row.addClass('selected');
				}
			} else {
				selectedSignals = selectedSignals.filter(id => id !== signalId);
				row.removeClass('selected');
			}
		});
		updateBulkActionsBar();
	});
	
	$('#bulk-deselect-btn').on('click', function() {
		$('.signal-checkbox').prop('checked', false);
		$('.signals-table tbody tr').removeClass('selected');
		$('#select-all').prop('checked', false);
		selectedSignals = [];
		updateBulkActionsBar();
	});
	
	$('#bulk-assign-btn').on('click', function() {
		if (selectedSignals.length === 0) return;
	
		$('#assign-signal-count').text(selectedSignals.length);
		$('#bulkAssignAlert').html('');
	
		$.ajax({
			url: 'ajax/lighthouse_keeper/get_keeper_keepers.php',
			method: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.status === 'success') {
					const select = $('#bulk-assign-keeper-select');
					select.empty();
					select.append('<option value="">Select a keeper...</option>');
					select.append('<option value="null">Unassign (No Keeper)</option>');
				
					response.data.forEach(keeper => {
						select.append(`
							<option value="${keeper.id}">
								${escapeHtml(keeper.first_name + ' ' + keeper.last_name)}
							</option>
						`);
					});
				
					const modal = new bootstrap.Modal(document.getElementById('bulkAssignModal'));
					modal.show();
				} else {
					alert('Failed to load lighthouse keepers. Please try again.');
				}
			},
			error: function() {
				alert('An error occurred while loading lighthouse keepers.');
			}
		});
	});

	$('#confirmBulkAssign').on('click', function() {
		const keeperId = $('#bulk-assign-keeper-select').val();
	
		if (!keeperId) {
			$('#bulkAssignAlert').html(`
				<div class="alert alert-warning">
					<i class="fa-solid fa-exclamation-triangle"></i> Please select a lighthouse keeper
				</div>
			`);
			return;
		}
	
		const btn = $(this);
		const originalHtml = btn.html();
	
		btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Assigning...');
		$('#bulkAssignAlert').html('');
	
		$.ajax({
			url: 'ajax/lighthouse_keeper/bulk_assign_keeper_signals.php',
			type: 'POST',
			data: { 
				signal_ids: selectedSignals,
				keeper_id: keeperId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const modal = bootstrap.Modal.getInstance(document.getElementById('bulkAssignModal'));
					modal.hide();
				
					const toastHtml = `
						<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
							<div class="toast show bg-success text-white" role="alert">
								<div class="toast-header bg-success text-white">
									<i class="fa-solid fa-check-circle me-2"></i>
									<strong class="me-auto">Success</strong>
									<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
								</div>
								<div class="toast-body">
									${response.message}
								</div>
							</div>
						</div>
					`;
					$('body').append(toastHtml);
				
					setTimeout(function() {
						$('.toast').fadeOut(function() { $(this).parent().remove(); });
					}, 3000);
				
					selectedSignals = [];
					$('.signal-checkbox').prop('checked', false);
					$('#select-all').prop('checked', false);
					updateBulkActionsBar();
					loadSignals();
				} else {
					$('#bulkAssignAlert').html(`
						<div class="alert alert-danger">
							<i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to assign signals'}
						</div>
					`);
				}
			
				btn.prop('disabled', false).html(originalHtml);
			},
			error: function(xhr, status, error) {
				$('#bulkAssignAlert').html(`
					<div class="alert alert-danger">
						<i class="fa-solid fa-exclamation-triangle"></i> An error occurred while assigning signals. Please try again.
					</div>
				`);
			
				btn.prop('disabled', false).html(originalHtml);
			}
		});
	});
	
	
	$('#bulk-delete-btn').on('click', function() {
		if (selectedSignals.length === 0) return;
	
		const signalCount = selectedSignals.length;
		const signalText = signalCount === 1 ? 'signal' : 'signals';
	
		if (confirm(`Are you sure you want to delete ${signalCount} ${signalText}?\n\nThis action cannot be undone.`)) {
			const btn = $(this);
			const originalHtml = btn.html();
		
			btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');
			$('#bulk-deselect-btn').prop('disabled', true);
			$('#bulk-move-btn').prop('disabled', true);
		
			$.ajax({
				url: 'ajax/lighthouse_keeper/bulk_delete_keeper_signals.php',
				type: 'POST',
				data: { signal_ids: selectedSignals },
				dataType: 'json',
				success: function(response) {
					if (response.success) {
						const toastHtml = `
							<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
								<div class="toast show bg-success text-white" role="alert">
									<div class="toast-header bg-success text-white">
										<i class="fa-solid fa-check-circle me-2"></i>
										<strong class="me-auto">Success</strong>
										<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
									</div>
									<div class="toast-body">
										${response.message}
									</div>
								</div>
							</div>
						`;
						$('body').append(toastHtml);
					
						setTimeout(function() {
							$('.toast').fadeOut(function() { $(this).parent().remove(); });
						}, 3000);
					
						selectedSignals = [];
						$('.signal-checkbox').prop('checked', false);
						$('#select-all').prop('checked', false);
						updateBulkActionsBar();
						
						loadSignals();
					} else {
						alert('Error: ' + (response.message || 'Failed to delete signals'));
					}
				
					btn.prop('disabled', false).html(originalHtml);
					$('#bulk-deselect-btn').prop('disabled', false);
					$('#bulk-move-btn').prop('disabled', false);
				},
				error: function(xhr, status, error) {
					alert('An error occurred while deleting signals. Please try again.');
				
					btn.prop('disabled', false).html(originalHtml);
					$('#bulk-deselect-btn').prop('disabled', false);
					$('#bulk-move-btn').prop('disabled', false);
				}
			});
		}
	});
	
	// Bulk Status Change Button Handler
	$('#bulk-status-btn').on('click', function() {
		if (selectedSignals.length === 0) return;
	
		$('#status-signal-count').text(selectedSignals.length);
		$('#bulkStatusAlert').html('');
	
		// Load available statuses (sea states)
		$.ajax({
			url: 'ajax/lh_sea_states/get_sea_states.php',
			method: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.status === 'success') {
					const select = $('#bulk-status-select');
					select.empty();
					select.append('<option value="">Select a status...</option>');
				
					response.data.forEach(state => {
						if (state.is_active == 1) {
							select.append(`
								<option value="${state.sea_state_id}" data-color="${state.sea_state_color}">
									${escapeHtml(state.sea_state_name)}
								</option>
							`);
						}
					});
				
					const modal = new bootstrap.Modal(document.getElementById('bulkStatusModal'));
					modal.show();
				} else {
					alert('Failed to load statuses. Please try again.');
				}
			},
			error: function() {
				alert('An error occurred while loading statuses.');
			}
		});
	});

	$('#confirmBulkStatus').on('click', function() {
		const statusId = $('#bulk-status-select').val();
	
		if (!statusId || statusId === '') {
			$('#bulkStatusAlert').html(`
				<div class="alert alert-warning">
					<i class="fa-solid fa-exclamation-triangle"></i> Please select a status
				</div>
			`);
			return;
		}
	
		const btn = $(this);
		const originalHtml = btn.html();
	
		btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Updating...');
		$('#bulkStatusAlert').html('');
	
		$.ajax({
			url: 'ajax/lighthouse_keeper/bulk_change_status_keeper_signals.php',
			type: 'POST',
			data: { 
				signal_ids: selectedSignals,
				status_id: statusId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const modal = bootstrap.Modal.getInstance(document.getElementById('bulkStatusModal'));
					modal.hide();
				
					const toastHtml = `
						<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
							<div class="toast show bg-success text-white" role="alert">
								<div class="toast-header bg-success text-white">
									<i class="fa-solid fa-check-circle me-2"></i>
									<strong class="me-auto">Success</strong>
									<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
								</div>
								<div class="toast-body">
									${response.message}
								</div>
							</div>
						</div>
					`;
					$('body').append(toastHtml);
				
					setTimeout(function() {
						$('.toast').fadeOut(function() { $(this).parent().remove(); });
					}, 3000);
				
					selectedSignals = [];
					$('.signal-checkbox').prop('checked', false);
					$('#select-all').prop('checked', false);
					updateBulkActionsBar();
					loadSignals();
				} else {
					$('#bulkStatusAlert').html(`
						<div class="alert alert-danger">
							<i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to change status'}
						</div>
					`);
				}
			
				btn.prop('disabled', false).html(originalHtml);
			},
			error: function(xhr, status, error) {
				$('#bulkStatusAlert').html(`
					<div class="alert alert-danger">
						<i class="fa-solid fa-exclamation-triangle"></i> An error occurred while changing status. Please try again.
					</div>
				`);
			
				btn.prop('disabled', false).html(originalHtml);
			}
		});
	});
	
	$('#bulk-move-btn').on('click', function() {
		if (selectedSignals.length === 0) return;
	
		$('#move-signal-count').text(selectedSignals.length);
		$('#bulkMoveAlert').html('');
	
		$.ajax({
			url: 'ajax/lh_docks/get_lighthouse_docks.php',
			method: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.status === 'success') {
					const select = $('#bulk-move-dock-select');
					select.empty();
					select.append('<option value="">Select a dock...</option>');
					select.append('<option value="null">Unassign Dock (Remove from all docks)</option>');
				
					response.data.forEach(dock => {
						select.append(`
							<option value="${dock.dock_id}">
								${escapeHtml(dock.dock_name)}
							</option>
						`);
					});
				
					const modal = new bootstrap.Modal(document.getElementById('bulkMoveModal'));
					modal.show();
				} else {
					alert('Failed to load docks. Please try again.');
				}
			},
			error: function() {
				alert('An error occurred while loading docks.');
			}
		});
	});

	$('#confirmBulkMove').on('click', function() {
		const targetDockId = $('#bulk-move-dock-select').val();
	
		if (!targetDockId || targetDockId === '') {
			$('#bulkMoveAlert').html(`
				<div class="alert alert-warning">
					<i class="fa-solid fa-exclamation-triangle"></i> Please select a dock or choose to unassign
				</div>
			`);
			return;
		}
	
		const btn = $(this);
		const originalHtml = btn.html();
	
		btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Moving...');
		$('#bulkMoveAlert').html('');
	
		$.ajax({
			url: 'ajax/lighthouse_keeper/bulk_move_keeper_signals.php',
			type: 'POST',
			data: { 
				signal_ids: selectedSignals,
				dock_id: targetDockId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					const modal = bootstrap.Modal.getInstance(document.getElementById('bulkMoveModal'));
					modal.hide();
				
					const toastHtml = `
						<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
							<div class="toast show bg-success text-white" role="alert">
								<div class="toast-header bg-success text-white">
									<i class="fa-solid fa-check-circle me-2"></i>
									<strong class="me-auto">Success</strong>
									<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
								</div>
								<div class="toast-body">
									${response.message}
								</div>
							</div>
						</div>
					`;
					$('body').append(toastHtml);
				
					setTimeout(function() {
						$('.toast').fadeOut(function() { $(this).parent().remove(); });
					}, 3000);
				
					selectedSignals = [];
					$('.signal-checkbox').prop('checked', false);
					$('#select-all').prop('checked', false);
					updateBulkActionsBar();
				
					loadSignals();
				} else {
					$('#bulkMoveAlert').html(`
						<div class="alert alert-danger">
							<i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to move signals'}
						</div>
					`);
				}
			
				btn.prop('disabled', false).html(originalHtml);
			},
			error: function(xhr, status, error) {
				$('#bulkMoveAlert').html(`
					<div class="alert alert-danger">
						<i class="fa-solid fa-exclamation-triangle"></i> An error occurred while moving signals. Please try again.
					</div>
				`);
			
				btn.prop('disabled', false).html(originalHtml);
			}
		});
	});
	
	$(document).on('click', '.status-subfilter', function(e) {
		e.stopPropagation();
		const $this = $(this);
		const deptId = $this.data('dept-id');
		const statusId = $this.data('status-id');
		
		$('.filter-card[data-type="dock"]').removeClass('active');
		$('.filter-card[data-type="owner"]').removeClass('active');
		$('.status-subfilter').removeClass('active');
		
		$this.addClass('active');
		
		currentFilters.dock = deptId;
		currentFilters.state = statusId;
		currentFilters.owner = null;
		
		loadSignals();
	});
	
	$(document).on('click', '.filter-card[data-type="dock"], .filter-card[data-type="owner"]', function() {
		const $this = $(this);
		const filterValue = $this.data('filter');
		const filterType = $this.data('type');
		
		$('.filter-card').removeClass('active');
		$('.status-subfilter').removeClass('active');
		$this.addClass('active');
		
		if (filterType === 'dock') {
			currentFilters.dock = filterValue;
			currentFilters.state = null;
			currentFilters.owner = null;
		} else if (filterType === 'owner') {
			currentFilters.owner = filterValue;
			currentFilters.dock = 'all';
			currentFilters.state = null;
		}
		
		loadSignals();
	});
	
	$(document).on('click', '.filter-card[data-type="priority"]', function() {
		const $this = $(this);
		const filterValue = $this.data('filter');
		
		$(`.filter-card[data-type="priority"]`).removeClass('active');
		$this.addClass('active');
		
		currentFilters.priority = filterValue;
		loadSignals();
	});
	
	let searchTimeout = null;
	
	$('#signals-search').on('input', function() {
		const searchValue = $(this).val().trim();
		const $searchBtn = $('#search-btn');
		
		if (searchValue.length > 0) {
			$searchBtn.html('<i class="fa-solid fa-circle-xmark"></i> Clear');
			$searchBtn.addClass('clear-mode');
		} else {
			$searchBtn.html('<i class="fa-solid fa-magnifying-glass"></i> Search');
			$searchBtn.removeClass('clear-mode');
		}
		
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(function() {
			currentFilters.search = searchValue;
			loadSignals();
		}, 300);
	});
	
	$('#signals-search').on('keypress', function(e) {
		if (e.which === 13) {
			clearTimeout(searchTimeout);
			currentFilters.search = $(this).val().trim();
			loadSignals();
		}
	});
	
	$('#search-btn').on('click', function() {
		const $searchInput = $('#signals-search');
		const $searchBtn = $(this);
		
		if ($searchBtn.hasClass('clear-mode')) {
			$searchInput.val('');
			$searchBtn.html('<i class="fa-solid fa-magnifying-glass"></i> Search');
			$searchBtn.removeClass('clear-mode');
			currentFilters.search = '';
			loadSignals();
		} else {
			currentFilters.search = $searchInput.val().trim();
			loadSignals();
		}
	});
	
	$('#signals-scroll-wrapper').on('scroll', function() {
		if ($(this).scrollTop() > 300) {
			$('#scrollTop').addClass('visible');
		} else {
			$('#scrollTop').removeClass('visible');
		}
	});
	
	$('#scrollTop').on('click', function() {
		$('#signals-scroll-wrapper').animate({ scrollTop: 0 }, 400);
	});
	
	function updateFileStatistics() {
		const fileCount = pendingFiles.length;
		let totalSize = 0;
		
		for (let i = 0; i < pendingFiles.length; i++) {
			totalSize += pendingFiles[i].size;
		}
		
		$('#files-count').text(fileCount);
		
		let sizeDisplay = '';
		if (totalSize < 1024) {
			sizeDisplay = totalSize + ' B';
		} else if (totalSize < 1048576) {
			sizeDisplay = (totalSize / 1024).toFixed(2) + ' KB';
		} else {
			sizeDisplay = (totalSize / 1048576).toFixed(2) + ' MB';
		}
		$('#total-size').text(sizeDisplay);
	}
	
	function displayUploadedFile(file, index) {
		const fileIcon = getFileIcon(file.type);
		const fileSizeDisplay = formatFileSize(file.size);
		
		const fileHtml = `
			<div class="uploaded-file-item" data-file-index="${index}">
				<i class="${fileIcon} file-icon"></i>
				<span class="file-name" title="${file.name}">${file.name}</span>
				<span class="file-size">${fileSizeDisplay}</span>
				<button type="button" class="file-remove" onclick="removeFile(${index})">
					<i class="fa-solid fa-circle-xmark"></i>
				</button>
			</div>
		`;
		
		$('#uploaded-files-list').append(fileHtml);
	}
	
	function getFileIcon(fileType) {
		if (fileType.startsWith('image/')) {
			return 'fa-solid fa-file-image';
		} else if (fileType === 'application/pdf') {
			return 'fa-solid fa-file-pdf';
		} else {
			return 'fa-solid fa-file';
		}
	}
	
	function formatFileSize(bytes) {
		if (bytes < 1024) {
			return bytes + ' B';
		} else if (bytes < 1048576) {
			return (bytes / 1024).toFixed(2) + ' KB';
		} else {
			return (bytes / 1048576).toFixed(2) + ' MB';
		}
	}
	
	window.removeFile = function(index) {
		pendingFiles.splice(index, 1);
		$(`[data-file-index="${index}"]`).remove();
		
		$('.uploaded-file-item').each(function(newIndex) {
			$(this).attr('data-file-index', newIndex);
			$(this).find('.file-remove').attr('onclick', `removeFile(${newIndex})`);
		});
		
		updateFileStatistics();
	}
	
	function handleFiles(files) {
		const maxSize = 5 * 1024 * 1024;
		const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
		
		for (let i = 0; i < files.length; i++) {
			const file = files[i];
			
			if (file.size > maxSize) {
				alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
				continue;
			}
			
			if (!allowedTypes.includes(file.type)) {
				alert(`File "${file.name}" has an unsupported format. Only JPG, PNG, GIF, and PDF are allowed.`);
				continue;
			}
			
			const currentIndex = pendingFiles.length;
			pendingFiles.push(file);
			displayUploadedFile(file, currentIndex);
		}
		
		updateFileStatistics();
	}
	
	const dropZone = $('#signal-drop-zone')[0];
	const fileInput = $('#signal-file-input')[0];
	
	if (dropZone) {
		dropZone.ondragover = function(e) {
			e.preventDefault();
			$(dropZone).addClass('drag-over');
			return false;
		};
		
		dropZone.ondragleave = function(e) {
			e.preventDefault();
			$(dropZone).removeClass('drag-over');
			return false;
		};
		
		dropZone.ondrop = function(e) {
			e.preventDefault();
			$(dropZone).removeClass('drag-over');
			const files = e.dataTransfer.files;
			handleFiles(files);
			return false;
		};
	}
	
	if (fileInput) {
		fileInput.onchange = function(e) {
			const files = e.target.files;
			handleFiles(files);
			fileInput.value = '';
		};
	}
	
	$('#createSignalForm').on('submit', function(e) {
		e.preventDefault();
		
		var messageContent = tinymce.get('keeper_signal_message_content').getContent();
		$('textarea[name="message"]').val(messageContent);
		
		if (!messageContent || messageContent.trim() === '' || messageContent === '<p></p>' || messageContent === '<p><br></p>') {
			alert('Please provide a message description for your signal.');
			return false;
		}
		
		const btn = $('#createSignalBtn');
		const originalBtnText = btn.html();
		
		btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creating...');
		$('#createSignalAlert').html('');
		
		const formData = new FormData(this);
		
		for (let i = 0; i < pendingFiles.length; i++) {
			formData.append('signal_attachments[]', pendingFiles[i]);
		}
		
		$.ajax({
			url: 'ajax/lighthouse_keeper/create_keeper_signal.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					window.currentSignalData = {
						signal_id: response.signal_id,
						signal_number: response.signal_number,
						attachments_uploaded: response.attachments_uploaded || 0
					};
					
					$('#receipt-signal-number').text(response.signal_number);
					
					if (response.attachments_uploaded && response.attachments_uploaded > 0) {
						$('#receipt-attachments-count').text(response.attachments_uploaded + ' file(s) uploaded');
						$('#receipt-attachments-row').show();
					} else {
						$('#receipt-attachments-row').hide();
					}
					
					$('#createSignalForm')[0].reset();
					tinymce.get('keeper_signal_message_content').setContent('');
					pendingFiles = [];
					$('#uploaded-files-list').html('');
					updateFileStatistics();
					$('#createSignalAlert').html('');
					
					$('#signals-list-view').removeClass('active');
					$('#create-signal-view').removeClass('active');
					$('#signal-receipt-view').addClass('active');
					
					loadSignals();
				} else {
					$('#createSignalAlert').html(`
						<div class="alert alert-danger">
							<i class="fa-solid fa-exclamation-triangle"></i> ${response.message || 'Failed to send a signal.'}
						</div>
					`);
				}
				btn.prop('disabled', false).html(originalBtnText);
			},
			error: function() {
				$('#createSignalAlert').html(`
					<div class="alert alert-danger">
						<i class="fa-solid fa-exclamation-triangle"></i> An error occurred.
					</div>
				`);
				btn.prop('disabled', false).html(originalBtnText);
			}
		});
	});
});

function copySignalLink(url) {
	const fullUrl = window.location.origin + '/' + url;
	const temp = $('<input>');
	$('body').append(temp);
	temp.val(fullUrl).select();
	document.execCommand('copy');
	temp.remove();
	
	const btn = event.target.closest('button');
	const originalText = btn.innerHTML;
	btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
	setTimeout(() => {
		btn.innerHTML = originalText;
	}, 2000);
}

$('#receipt-copy-link').on('click', function() {
	if (window.currentSignalData && window.currentSignalData.signal_id) {
		const signalUrl = `lighthouse_keeper_view.php?id=${window.currentSignalData.signal_id}`;
		const fullUrl = window.location.origin + '/' + signalUrl;
		
		const temp = $('<input>');
		$('body').append(temp);
		temp.val(fullUrl).select();
		document.execCommand('copy');
		temp.remove();
		
		const btn = $(this);
		const originalHtml = btn.html();
		btn.html('<i class="fa-solid fa-check"></i> Copied!');
		setTimeout(() => {
			btn.html(originalHtml);
		}, 2000);
	}
});

$('#receipt-view-signal').on('click', function() {
	if (window.currentSignalData && window.currentSignalData.signal_id) {
		window.location.href = `lighthouse_keeper_view.php?id=${window.currentSignalData.signal_id}`;
	}
});

$('#receipt-go-home').on('click', function() {
	$('#signal-receipt-view').removeClass('active');
	$('#signals-list-view').addClass('active');
	$('#create-signal-view').removeClass('active');
});

$('#receipt-create-another').on('click', function() {
	$('#signal-receipt-view').removeClass('active');
	$('#signals-list-view').removeClass('active');
	$('#create-signal-view').addClass('active');
});
</script>