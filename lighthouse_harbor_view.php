<?php
session_start();
date_default_timezone_set('America/New_York');
include 'mysqli_connect.php';
include 'templates/functions.php';

if (!isset($_SESSION['switch_id'])) {
    header("Location: index.php?msg1");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: signals_new.php");
    exit();
}

$signal_id = (int)$_GET['id'];
$user_id = $_SESSION['id'];
$is_admin = false;

$query = "SELECT t.*, 
          ts.sea_state_name, ts.sea_state_color, ts.sea_state_icon,
          tp.priority_name, tp.priority_color, tp.priority_icon,
          td.dock_name, td.dock_color, td.dock_icon,
          tsvc.service_name, tsvc.service_color, tsvc.service_icon,
          CONCAT(u.first_name, ' ', u.last_name) as creator_name,
          u.profile_pic as creator_pic,
          CONCAT(a.first_name, ' ', a.last_name) as assigned_name,
          a.profile_pic as assigned_pic
          FROM lh_signals t
          LEFT JOIN lh_sea_states ts ON t.sea_state_id = ts.sea_state_id
          LEFT JOIN lh_priorities tp ON t.priority_id = tp.priority_id
          LEFT JOIN lh_docks td ON t.dock_id = td.dock_id
          LEFT JOIN lh_services tsvc ON t.service_id = tsvc.service_id
          LEFT JOIN users u ON t.sent_by = u.id
          LEFT JOIN users a ON t.keeper_assigned = a.id
          WHERE t.signal_id = ? AND t.is_deleted = 0";

$stmt = mysqli_prepare($dbc, $query);
mysqli_stmt_bind_param($stmt, 'i', $signal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: signals_new.php");
    exit();
}

$signal = mysqli_fetch_assoc($result);

if (!$is_admin && $signal['sent_by'] != $user_id) {
    header("Location: signals_new.php");
    exit();
}

define('TITLE', 'Harbor Signal');
include 'templates/header.php';

// Messages section removed - comments now handled through updates
/*
$messages_query = "SELECT tc.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as commenter_name,
                   u.profile_pic as commenter_pic
                   FROM lh_signal_messages tc
                   LEFT JOIN users u ON tc.user_id = u.id
                   WHERE tc.signal_id = ?";

if (!$is_admin) {
    $messages_query .= " AND tc.is_internal = 0";
}

$messages_query .= " ORDER BY tc.created_date DESC";

$messages_stmt = mysqli_prepare($dbc, $messages_query);
mysqli_stmt_bind_param($messages_stmt, 'i', $signal_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);
*/

// Get activity log
$activity_query = "SELECT ta.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as user_name
                   FROM lh_signal_activity ta
                   LEFT JOIN users u ON ta.user_id = u.id
                   WHERE ta.signal_id = ?
                   ORDER BY ta.created_date DESC
                   LIMIT 20";

$activity_stmt = mysqli_prepare($dbc, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'i', $signal_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);

// Get lh_sea_states and priorities for editing (both regular users and admins)
$beacons_query = "SELECT * FROM lh_sea_states WHERE is_active = 1 ORDER BY sea_state_order";
$beacons_result = mysqli_query($dbc, $beacons_query);

$priorities_query = "SELECT * FROM lh_priorities WHERE is_active = 1 ORDER BY priority_order";
$priorities_result = mysqli_query($dbc, $priorities_query);

// Get signal attachments
$attachments_query = "SELECT a.*, 
                      CONCAT(u.first_name, ' ', u.last_name) as uploader_name
                      FROM lh_signal_attachments a
                      LEFT JOIN users u ON a.uploaded_by = u.id
                      WHERE a.signal_id = ?
                      ORDER BY a.uploaded_date DESC";

$attachments_stmt = mysqli_prepare($dbc, $attachments_query);
mysqli_stmt_bind_param($attachments_stmt, 'i', $signal_id);
mysqli_stmt_execute($attachments_stmt);
$attachments_result = mysqli_stmt_get_result($attachments_stmt);

// Get signal updates
$updates_query = "SELECT u.*, 
                  CONCAT(usr.first_name, ' ', usr.last_name) as updater_name,
                  usr.profile_pic as updater_pic,
                  usr.cell as updater_phone,
                  usr.display_title as updater_title
                  FROM lh_signal_updates u
                  LEFT JOIN users usr ON u.user_id = usr.id
                  WHERE u.signal_id = ?";

if (!$is_admin) {
    $updates_query .= " AND u.is_internal = 0";
}

$updates_query .= " ORDER BY u.created_date DESC";

$updates_stmt = mysqli_prepare($dbc, $updates_query);
mysqli_stmt_bind_param($updates_stmt, 'i', $signal_id);
mysqli_stmt_execute($updates_stmt);
$updates_result = mysqli_stmt_get_result($updates_stmt);

// Get all update comments for this signal (exclude internal comments in Harbor view)
$update_comments_query = "SELECT uc.*, 
                          CONCAT(u.first_name, ' ', u.last_name) as commenter_name,
                          u.profile_pic as commenter_pic
                          FROM lh_signal_update_comments uc
                          JOIN lh_signal_updates upd ON uc.update_id = upd.update_id
                          LEFT JOIN users u ON uc.user_id = u.id
                          WHERE upd.signal_id = ? AND uc.is_internal = 0
                          ORDER BY uc.created_date ASC";

$update_comments_stmt = mysqli_prepare($dbc, $update_comments_query);
mysqli_stmt_bind_param($update_comments_stmt, 'i', $signal_id);
mysqli_stmt_execute($update_comments_stmt);
$update_comments_result = mysqli_stmt_get_result($update_comments_stmt);

// Group comments by update_id
$update_comments_array = [];
while ($comment = mysqli_fetch_assoc($update_comments_result)) {
    $update_comments_array[$comment['update_id']][] = $comment;
}

// Record this view for tracking (visible to keepers, not displayed to harbor users)
$current_datetime = date('Y-m-d H:i:s');

// Check if this user has already viewed this signal
$check_view_query = "SELECT view_id, view_count FROM lh_signal_views WHERE signal_id = ? AND user_id = ?";
$check_view_stmt = mysqli_prepare($dbc, $check_view_query);
mysqli_stmt_bind_param($check_view_stmt, 'ii', $signal_id, $user_id);
mysqli_stmt_execute($check_view_stmt);
$check_view_result = mysqli_stmt_get_result($check_view_stmt);

if (mysqli_num_rows($check_view_result) > 0) {
    // Update existing view record
    $view_data = mysqli_fetch_assoc($check_view_result);
    $new_view_count = $view_data['view_count'] + 1;
    $update_view_query = "UPDATE lh_signal_views SET last_viewed = ?, view_count = ? WHERE signal_id = ? AND user_id = ?";
    $update_view_stmt = mysqli_prepare($dbc, $update_view_query);
    mysqli_stmt_bind_param($update_view_stmt, 'siii', $current_datetime, $new_view_count, $signal_id, $user_id);
    mysqli_stmt_execute($update_view_stmt);
} else {
    // Insert new view record
    $insert_view_query = "INSERT INTO lh_signal_views (signal_id, user_id, first_viewed, last_viewed) VALUES (?, ?, ?, ?)";
    $insert_view_stmt = mysqli_prepare($dbc, $insert_view_query);
    mysqli_stmt_bind_param($insert_view_stmt, 'iiss', $signal_id, $user_id, $current_datetime, $current_datetime);
    mysqli_stmt_execute($insert_view_stmt);
}
?>

<script> // $(document).ready(function(){ $(".page-wrapper").addClass("pinned"); }); </script>

<style>
.signal-view-container {
    background: #f7f8fa;
    min-height: 100vh;
    padding: 15px 0;
}

.signal-header-card {
    background: white;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid;
}

.signal-number-display {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.signal-title-display {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.3;
}

.signal-meta-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    padding: 12px 0;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

.meta-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.content-section {
    background: white;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.signal-description {
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
}

.comment-item {
    display: flex;
    gap: 12px;
    padding: 14px;
    background: #f9fafb;
    border-radius: 4px;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
    position: relative;
}

.update-item {
    display: flex;
    gap: 14px;
    padding: 18px;
    margin-bottom: 16px;
    border-radius: 8px;
    border-left: 4px solid;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: all 0.2s;
}

.update-item.user-update {
    background: linear-gradient(to right, #f0f9ff 0%, #f8fbff 100%);
    border-left-color: #0ea5e9;
}

.update-item.user-update .update-author {
    color: #0369a1;
}

.update-item.admin-update {
    background: linear-gradient(to right, #f5f3ff 0%, #faf9ff 100%);
    border-left-color: #7c3aed;
}

.update-item.admin-update .update-author {
    color: #5b21b6;
}

.update-item.private-update {
    background: linear-gradient(to right, #fef3c7 0%, #fffbeb 100%);
    border-left-color: #f59e0b;
}

.update-icon-container {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 2px;
}

.update-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.update-content {
    flex: 1;
    min-width: 0;
}

.update-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.update-author {
    font-weight: 700;
    font-size: 14px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
}

.update-private-badge {
    background: #f59e0b;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.update-keeper-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: #7c3aed;
    color: white;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.update-date {
    font-size: 12px;
    color: #9ca3af;
    font-weight: 500;
    white-space: nowrap;
}

.update-status-change {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 14px;
    color: #374151;
    font-weight: 500;
    padding: 12px;
    background: rgba(255,255,255,0.7);
    border-radius: 6px;
    margin-bottom: 10px;
}

.update-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

.update-message {
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
  	border-radius: 6px;
    margin-top: 8px;
}

.update-signature {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 2px solid rgba(0,0,0,0.1);
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
}

.update-signature strong {
    color: #1f2937;
    display: block;
    font-size: 13px;
}

.update-footer {
    margin-top: 12px;
}

.btn-toggle-comments {
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 5px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-toggle-comments:hover {
    background: white;
    border-color: rgba(0,0,0,0.2);
    color: #1f2937;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.update-comments-container {
    margin-top: 12px;
}

.update-comments-list {
    margin-bottom: 12px;
}

.update-comment-item {
    display: flex;
    gap: 10px;
    padding: 12px;
    background: white;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 6px;
    margin-bottom: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.update-comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.update-comment-content {
    flex: 1;
}

.update-comment-author {
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
    margin-bottom: 4px;
}

.update-comment-text {
    font-size: 13px;
    color: #374151;
    line-height: 1.5;
    margin-bottom: 4px;
}

.update-comment-date {
    font-size: 11px;
    color: #9ca3af;
}

.add-update-comment-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid rgba(0,0,0,0.15);
    border-radius: 6px;
    font-size: 13px;
    resize: vertical;
    min-height: 60px;
}

.add-update-form {
    background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 18px;
    margin-top: 20px;
}

.add-update-form h6 {
    color: #1f2937;
    font-weight: 700;
}

.add-update-form .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.add-update-form select,
.add-update-form textarea {
    font-size: 13px;
}

.comment-avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-content {
    flex: 1;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.comment-author {
    font-weight: 600;
    color: #1f2937;
    font-size: 13px;
}

.comment-date {
    font-size: 11px;
    color: #9ca3af;
}

.comment-text {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
    white-space: pre-wrap;
}

.comment-text-display {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
    white-space: pre-wrap;
}

.comment-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.btn-comment-action {
    padding: 4px 10px;
    font-size: 11px;
    border: 1px solid #e5e7eb;
    background: white;
    color: #6b7280;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-comment-action:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #1f2937;
}

.btn-comment-action.delete:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

.comment-edit-form {
    display: none;
    margin-top: 10px;
}

.comment-edit-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 13px;
    resize: vertical;
}

.comment-edit-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.internal-comment {
    background: #fef3c7;
    border-left: 3px solid #f59e0b;
}

.activity-item {
    display: flex;
    align-items: start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    background: #e5e7eb;
    color: #6b7280;
}

.activity-content {
    flex: 1;
    font-size: 12px;
    color: #6b7280;
}

.activity-user {
    font-weight: 600;
    color: #1f2937;
}

.activity-time {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 3px;
}

.lighthouse-user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.user-details {
    flex: 1;
}

.lighthouse-user-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 13px;
}

.user-label {
    font-size: 11px;
    color: #6b7280;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: white;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-back:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #1f2937;
}

.btn-edit-signal {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit-signal:hover {
    background: #2563eb;
}

.btn-add-update {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-update:hover {
    background: #059669;
}

.shareable-link {
    background: #f3f4f6;
    padding: 10px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
}

.shareable-link input {
    flex: 1;
    border: none;
    background: white;
    padding: 6px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-family: monospace;
}

.btn-copy-link {
    padding: 6px 12px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-copy-link:hover {
    background: #059669;
}

.edit-inline-group {
    display: flex;
    gap: 6px;
    align-items: center;
}

.edit-inline-select {
    padding: 5px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
    font-size: 12px;
}

.btn-save-inline {
    padding: 5px 10px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel-inline {
    padding: 5px 10px;
    background: #6b7280;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

/* Attachments Section Styles */
.attachments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.attachment-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.attachment-card:hover {
    background: #f3f4f6;
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.attachment-preview {
    width: 100%;
    height: 120px;
    background: white;
    border-radius: 4px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.attachment-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.attachment-preview-icon {
    font-size: 48px;
    color: #9ca3af;
}

.attachment-info {
    font-size: 12px;
    color: #6b7280;
}

.attachment-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 6px;
}

.attachment-size {
    font-weight: 500;
}

.attachment-date {
    font-style: italic;
}

.no-attachments {
    text-align: center;
    padding: 30px;
    color: #9ca3af;
    font-size: 14px;
}

.no-attachments i {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
    color: #d1d5db;
}

/* Signal Updates Styles */
.update-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-radius: 6px;
  	border-left: 4px solid;
}

/* Admin/Keeper Updates - Purple theme */
.update-item.admin-update {
    background: linear-gradient(to right, #f5f3ff 0%, #faf9ff 100%);
    border-left-color: #7c3aed;
}

.update-item.admin-update .update-author {
    color: #5b21b6;
}

/* User/Harbor Updates - Blue theme */
.update-item.user-update {
    background: linear-gradient(to right, #f0f9ff 0%, #f8fbff 100%);
    border-left-color: #0ea5e9;
}

.update-item.user-update .update-author {
    color: #0369a1;
}

/* Private/Internal Updates - Amber theme */
.update-item.private-update {
    background: linear-gradient(to right, #fef3c7 0%, #fffbeb 100%);
    border-left-color: #f59e0b;
}

.update-avatar {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
}

.update-content {
    flex: 1;
}

.update-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.update-author {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.update-private-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: #f59e0b;
    color: white;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.update-keeper-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: #7c3aed;
    color: white;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.update-date {
    font-size: 11px;
    color: #9ca3af;
}

.update-status-change {
    font-size: 13px;
    color: #1f2937;
    font-weight: 500;
    margin-bottom: 10px;
}

.update-status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-weight: 600;
    font-size: 12px;
}

.update-message {
    font-size: 14px;

}

.update-signature {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
}

.update-signature strong {
    color: #1f2937;
    display: block;
    font-size: 13px;
}

.update-signature-quote {
    font-style: italic;
    color: #b91c1c;
    margin-top: 8px;
    padding-left: 12px;
    border-left: 3px solid #b91c1c;
}

.update-comments-section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.update-comment-item {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    margin-bottom: 8px;
}

.update-comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.update-comment-content {
    flex: 1;
}

.update-comment-author {
    font-weight: 600;
    font-size: 12px;
    color: #1f2937;
}

.update-comment-text {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
    line-height: 1.5;
}

.update-comment-date {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}

.add-update-comment-form {
    margin-top: 10px;
}

.add-update-comment-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 12px;
    resize: vertical;
}

.btn-toggle-comments {
    padding: 4px 10px;
    font-size: 11px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-toggle-comments:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #1f2937;
}

.add-update-form {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 16px;
    margin-top: 16px;
}

.add-update-form .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.add-update-form select,
.add-update-form textarea {
    font-size: 13px;
}
</style>

<main class="page-content pt-2">
    <div class="tab-content">
        <?php include 'templates/alerts.php'; ?>
        <?php include 'templates/breadcrumb.php'; ?>
        <?php include 'templates/search_results_tab.php'; ?>
        <div id="main_tab" class="tab-pane fade in active show">
            <div class="container-fluid fluid-top p-3">
          	  	<div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-edit-signal" data-bs-toggle="modal" data-bs-target="#editSignalModal">
                            <i class="fa-solid fa-feather-pointed"></i> Edit Signal
                        </button>
                        <button type="button" class="btn-add-update" data-bs-toggle="modal" data-bs-target="#addUpdateModal">
                            <i class="fa-solid fa-plus-circle"></i> Add Update
                        </button>
                    </div>
						<a href="lighthouse_harbor.php" class="btn-back">
                           	<i class="fa-solid fa-angles-left"></i> Back to Harbor
                        </a>
                    </div>
                    
                    <!-- Signal Header -->
                    <div class="signal-header-card" style="border-left-color: <?php echo $signal['priority_color']; ?>">
                        <div class="signal-number-display">
                            <i class="fa-solid fa-signal me-2"></i><?php echo htmlspecialchars($signal['signal_number']); ?>
                        </div>
                        <h1 class="signal-title-display"><?php echo htmlspecialchars($signal['title']); ?></h1>
                        
                        <div class="signal-meta-row">
                            <span class="meta-badge" style="background-color: <?php echo $signal['sea_state_color']; ?>; color: white;"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Status">
                                <i class="<?php echo $signal['sea_state_icon']; ?>"></i>
                                <?php echo htmlspecialchars($signal['sea_state_name']); ?>
                            </span>
                            <span class="meta-badge" style="background-color: <?php echo $signal['priority_color']; ?>; color: white;" 
                                  data-bs-toggle="tooltip" data-bs-placement="top" 
                                  title="Priority">
                                <i class="<?php echo $signal['priority_icon']; ?>"></i>
                                <?php echo htmlspecialchars($signal['priority_name']); ?>
                            </span>
                            <?php if ($signal['dock_name']): ?>
                            <span class="meta-badge" style="background-color: <?php echo $signal['dock_color']; ?>20; color: <?php echo $signal['dock_color']; ?>;"
								data-bs-toggle="tooltip" data-bs-placement="top" title="Dock">
                                <i class="<?php echo $signal['dock_icon']; ?>"></i>
                                <?php echo htmlspecialchars($signal['dock_name']); ?>
                            </span>
                            <?php else: ?>
                            <span class="meta-badge" style="background-color: #6c757d20; color: #6c757d;">
                                <i class="fa-solid fa-circle-question"></i>
                                Unassigned
                            </span>
                            <?php endif; ?>
                            <?php if ($signal['service_name']): ?>
                            <span class="meta-badge" style="background-color: <?php echo $signal['service_color']; ?>20; color: <?php echo $signal['service_color']; ?>;"
								data-bs-toggle="tooltip" data-bs-placement="top" title="Service">
                                <i class="fa-solid <?php echo $signal['service_icon']; ?>"></i>
                                <?php echo htmlspecialchars($signal['service_name']); ?>
                            </span>
                            <?php endif; ?>
                            <span class="meta-badge" style="background: #f3f4f6; color: #6b7280;"
								data-bs-toggle="tooltip" data-bs-placement="top" title="Type">
                                <?php echo str_replace('_', ' ', ucwords($signal['signal_type'], '_')); ?>
                            </span>
                        </div>
                        
                        <!-- Shareable Link -->
                        <div class="shareable-link">
                            <i class="fa-solid fa-link" style="color: #6b7280;"></i>
                            <input type="text" id="signalShareLink" readonly 
                                   value="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/dashboard/lighthouse_harbor_view.php?id=' . $signal_id; ?>">
                            <button type="button" class="btn-copy-link" onclick="copyShareLink()">
                                <i class="fa-solid fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="row gx-3">
                        <div class="col-lg-8">
                            <!-- Description -->
                            <div class="content-section">
                                <h5 class="section-title">
                                    <i class="fa-regular fa-file-lines"></i> Description
                                </h5>
                                <div class="signal-description">
                                    <?php echo $signal['message']; ?>
                                </div>
                            </div>
                            
                            <!-- Signal Updates Section -->
                            <div class="content-section">
                                <h5 class="section-title">
                                    <i class="fa-solid fa-timeline"></i> Signal Updates
                                    <span style="color: #9ca3af; font-size: 14px; font-weight: normal;">
                                        (<?php echo mysqli_num_rows($updates_result); ?>)
                                    </span>
                                </h5>
                                
                                <?php if (mysqli_num_rows($updates_result) > 0): ?>
                                    <?php while ($update = mysqli_fetch_assoc($updates_result)): 
                                        // User update = update from the original signal submitter
                                        // Keeper update = update from anyone else (support staff)
                                        $is_user_update = ($update['user_id'] == $signal['sent_by']);
                                        $update_class = $is_user_update ? 'user-update' : 'admin-update';
                                        if ($update['is_internal']) $update_class .= ' private-update';
                                        $update_comments = isset($update_comments_array[$update['update_id']]) ? $update_comments_array[$update['update_id']] : [];
                                    ?>
                                    <div class="update-item <?php echo $update_class; ?>" data-update-id="<?php echo $update['update_id']; ?>">
                                        <div class="update-icon-container">
                                            <img src="<?php echo htmlspecialchars($update['updater_pic']); ?>" 
                                                 alt="Avatar" class="update-avatar">
                                        </div>
                                        <div class="update-content">
                                            <div class="update-header">
                                                <div>
                                                    <span class="update-author">
                                                        <?php echo htmlspecialchars($update['updater_name']); ?>
                                                        <?php if (!$is_user_update): ?>
                                                        <span class="update-keeper-badge">
                                                            <i class="fa-solid fa-tower-observation"></i> Keeper
                                                        </span>
                                                        <?php endif; ?>
                                                        <?php if ($update['is_internal']): ?>
                                                        <span class="update-private-badge">
                                                            <i class="fa-solid fa-lock"></i> Private
                                                        </span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <span class="update-date">
                                                    <?php echo date('D n/j/Y g:i A', strtotime($update['created_date'])); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($update['update_type'] == 'status_change'): ?>
                                            <div class="update-status-change">
                                                Changed Status from 
                                                <span class="update-status-badge" style="background: #e5e7eb; color: #374151;">
                                                    "<?php echo htmlspecialchars($update['old_value']); ?>"
                                                </span> 
                                                to 
                                                <span class="update-status-badge" style="background: #3b82f6; color: white;">
                                                    "<?php echo htmlspecialchars($update['new_value']); ?>"
                                                </span>
                                            </div>
                                            <?php elseif ($update['update_type'] == 'contact_added'): ?>
                                            <div class="update-status-change">
                                                <i class="fa-solid fa-user-plus"></i> 
                                                Added <?php echo htmlspecialchars($update['new_value']); ?> as a contact for this service request.
                                            </div>
                                            <?php elseif ($update['update_type'] == 'assignment'): ?>
                                            <div class="update-status-change">
                                                <i class="fa-solid fa-user-tag"></i> 
                                                <?php echo $update['new_value'] ? 'Assigned to ' . htmlspecialchars($update['new_value']) : 'Unassigned signal'; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($update['message'])): ?>
                                            <div class="update-message">
                                                <?php echo nl2br(htmlspecialchars($update['message'])); ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Update Comments Thread -->
                                            <div class="update-footer">
                                                <button type="button" class="btn-toggle-comments" data-update-id="<?php echo $update['update_id']; ?>">
                                                    <i class="fa-regular fa-comment"></i> 
                                                    <?php echo count($update_comments); ?> 
                                                    <?php echo count($update_comments) == 1 ? 'Comment' : 'Comments'; ?>
                                                </button>
                                            </div>
                                            
                                            <div class="update-comments-container" id="comments-<?php echo $update['update_id']; ?>" style="display: none;">
                                                <?php if (!empty($update_comments)): ?>
                                                    <div class="update-comments-list">
                                                        <?php foreach ($update_comments as $comment): ?>
                                                        <div class="update-comment-item" data-comment-id="<?php echo $comment['comment_id']; ?>">
                                                            <img src="<?php echo htmlspecialchars($comment['commenter_pic']); ?>" 
                                                                 alt="Avatar" class="update-comment-avatar">
                                                            <div class="update-comment-content">
                                                                <div class="update-comment-author">
                                                                    <?php echo htmlspecialchars($comment['commenter_name']); ?>
                                                                </div>
                                                                <div class="update-comment-text-display">
                                                                    <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                                                </div>
                                                                <div class="update-comment-date">
                                                                    <?php echo date('M d, Y g:i A', strtotime($comment['created_date'])); ?>
                                                                </div>
                                                                <?php if ($is_admin || $comment['user_id'] == $user_id): ?>
                                                                <div class="update-comment-actions" style="margin-top: 6px;">
                                                                    <button type="button" class="btn-comment-action edit-update-comment-btn" data-comment-id="<?php echo $comment['comment_id']; ?>" style="font-size: 11px; padding: 2px 8px; margin-right: 4px;">
                                                                        <i class="fa-solid fa-edit"></i> Edit
                                                                    </button>
                                                                    <button type="button" class="btn-comment-action delete delete-update-comment-btn" data-comment-id="<?php echo $comment['comment_id']; ?>" style="font-size: 11px; padding: 2px 8px;">
                                                                        <i class="fa-solid fa-trash"></i> Delete
                                                                    </button>
                                                                </div>
                                                                <div class="update-comment-edit-form" style="display: none; margin-top: 8px;">
                                                                    <textarea class="form-control form-control-sm update-comment-edit-textarea" rows="3" style="font-size: 12px;"><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                                                                    <div style="margin-top: 6px;">
                                                                        <button type="button" class="btn-comment-action save-update-comment-btn" data-comment-id="<?php echo $comment['comment_id']; ?>" style="font-size: 11px; padding: 2px 8px; margin-right: 4px;">
                                                                            <i class="fa-solid fa-save"></i> Save
                                                                        </button>
                                                                        <button type="button" class="btn-comment-action cancel-update-edit-btn" style="font-size: 11px; padding: 2px 8px;">
                                                                            <i class="fa-solid fa-times"></i> Cancel
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Add Comment Form -->
                                                <form class="add-update-comment-form" data-update-id="<?php echo $update['update_id']; ?>">
                                                    <textarea class="form-control form-control-sm" 
                                                              rows="2" 
                                                              placeholder="Add a comment..." 
                                                              required></textarea>
                                                    <button type="submit" class="btn btn-sm btn-primary mt-2">
                                                        <i class="fa-solid fa-paper-plane"></i> Post Comment
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center p-4" style="color: #9ca3af;">
                                        <i class="fa-solid fa-timeline fa-2x mb-3"></i>
                                        <h4>No updates yet.</h4>
                                        <p>Updates will appear here when the signal status changes or when updates are posted.</p>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                            
                            <!-- Messages -->

                        </div>
                        
                        <div class="col-lg-4">
                            <!-- Signal Details -->
                            <div class="content-section">
                                <h5 class="section-title">
                                    <i class="fa-solid fa-info-circle me-2"></i>Details
                                </h5>
                                
                                <div class="mb-2">
                                    <div class="lighthouse-user-info">
                                        <img src="<?php echo htmlspecialchars($signal['creator_pic']); ?>" 
                                             alt="Creator" class="user-avatar">
                                        <div class="user-details">
                                            <div class="user-label">Created by</div>
                                            <div class="lighthouse-user-name"><?php echo htmlspecialchars($signal['creator_name']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($signal['assigned_name']): ?>
                                <div class="mb-2">
                                    <div class="lighthouse-user-info">
                                        <img src="<?php echo htmlspecialchars($signal['assigned_pic']); ?>" 
                                             alt="Assigned" class="user-avatar">
                                        <div class="user-details">
                                            <div class="user-label">Assigned to</div>
                                            <div class="lighthouse-user-name"><?php echo htmlspecialchars($signal['assigned_name']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div style="padding: 10px 0; border-top: 1px solid #f3f4f6;">
                                    <div style="font-size: 11px; color: #9ca3af; margin-bottom: 3px;">Created</div>
                                    <div style="font-size: 13px; color: #374151;">
                                        <?php echo date('M d, Y g:i A', strtotime($signal['sent_date'])); ?>
                                    </div>
                                </div>
                                
                                <div style="padding: 10px 0; border-top: 1px solid #f3f4f6;">
                                    <div style="font-size: 11px; color: #9ca3af; margin-bottom: 3px;">Last Updated</div>
                                    <div style="font-size: 13px; color: #374151;">
                                        <?php echo date('M d, Y g:i A', strtotime($signal['updated_date'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($signal['resolved_date']): ?>
                                <div style="padding: 10px 0; border-top: 1px solid #f3f4f6;">
                                    <div style="font-size: 11px; color: #9ca3af; margin-bottom: 3px;">Resolved</div>
                                    <div style="font-size: 13px; color: #374151;">
                                        <?php echo date('M d, Y g:i A', strtotime($signal['resolved_date'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Attachments -->
                            <?php if (mysqli_num_rows($attachments_result) > 0): ?>
                            <div class="content-section">
                                <div class="section-title">
                                    <i class="fa-solid fa-paperclip me-2"></i>Attachments
                                    <span style="font-size: 12px; font-weight: 500; color: #6b7280; margin-left: 8px;">
                                        (<?php echo mysqli_num_rows($attachments_result); ?> file<?php echo mysqli_num_rows($attachments_result) != 1 ? 's' : ''; ?>)
                                    </span>
                                </div>
                                
                                <div class="attachments-grid">
                                    <?php mysqli_data_seek($attachments_result, 0); ?>
                                    <?php while ($attachment = mysqli_fetch_assoc($attachments_result)): ?>
                                        <?php
                                        $file_extension = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                        $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif']);
                                        $file_size_kb = round($attachment['file_size'] / 1024, 2);
                                        $file_size_display = $file_size_kb < 1024 ? $file_size_kb . ' KB' : round($file_size_kb / 1024, 2) . ' MB';
                                        ?>
                                        
                                        <div class="attachment-card" onclick="viewAttachment('<?php echo htmlspecialchars($attachment['file_path']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', <?php echo $is_image ? 'true' : 'false'; ?>)">
                                            <div class="attachment-preview">
                                                <?php if ($is_image): ?>
                                                    <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($attachment['file_name']); ?>"
                                                         loading="lazy">
                                                <?php elseif ($file_extension === 'pdf'): ?>
                                                    <div class="attachment-preview-icon">
                                                        <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="attachment-preview-icon">
                                                        <i class="fa-solid fa-file"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="attachment-info">
                                                <div class="attachment-name" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                </div>
                                                <div class="attachment-meta">
                                                    <span class="attachment-size"><?php echo $file_size_display; ?></span>
                                                    <span class="attachment-date"><?php echo date('M d, Y', strtotime($attachment['uploaded_date'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <?php endif; ?>
							
                            <!-- Activity Log -->
                            <div class="shadow-sm bg-white">
                                <div class="accordion accordion-flush" id="accordionActivityHarbor">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button type="button" class="text-white accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accord_activity_harbor" aria-expanded="false" aria-controls="accord_activity_harbor" style="padding: 15px 20px;">
                                                <span class="btn btn-light btn-sm me-2" style="width:40px; pointer-events: none;">
                                                    <i class="fa-solid fa-clock-rotate-left text-secondary fa-lg"></i>
                                                </span>
                                                <span class="w-75">
                                                    <strong class="dark-gray">Activity</strong> <span class="text-secondary">Log</span>
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="accord_activity_harbor" class="accordion-collapse collapse" data-bs-parent="#accordionActivityHarbor">
                                            <div class="accordion-body" style="padding: 15px 20px;">
                                                <?php if (mysqli_num_rows($activity_result) > 0): ?>
                                                    <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                                                    <div class="activity-item">
                                                        <div class="activity-icon">
                                                            <i class="fa-solid fa-circle-dot"></i>
                                                        </div>
                                                        <div class="activity-content">
                                                            <span class="activity-user"><?php echo htmlspecialchars($activity['user_name']); ?></span>
                                                            <?php echo htmlspecialchars($activity['activity_type']); ?>
                                                            <?php if ($activity['new_value']): ?>
                                                            <div style="margin-top: 4px; font-size: 12px; color: #9ca3af;">
                                                                <?php echo htmlspecialchars($activity['new_value']); ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="activity-time">
                                                                <?php echo date('M d, Y g:i A', strtotime($activity['created_date'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <p style="color: #9ca3af; text-align: center; padding: 20px 0;">
                                                        No activity yet
                                                    </p>
                                                <?php endif; ?>
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
    
    <!-- Attachment Viewer Modal -->
    <div class="modal fade" id="attachmentViewerModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentFileName">Attachment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" style="background: #f9fafb; padding: 30px;">
                    <div id="attachmentContent">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="attachmentDownloadLink" class="btn btn-primary" download>
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit Signal Modal -->
<div class="modal fade" id="editSignalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-edit me-2"></i>Edit Signal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSignalForm">
                <input type="hidden" name="signal_id" value="<?php echo $signal_id; ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Beacon</label>
                            <select name="sea_state_id" class="form-select">
                                <?php mysqli_data_seek($beacons_result, 0); ?>
                                <?php while ($status = mysqli_fetch_assoc($beacons_result)): ?>
                                <option value="<?php echo $status['sea_state_id']; ?>" 
                                        <?php echo ($status['sea_state_id'] == $signal['sea_state_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status['sea_state_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Update the signal status</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority_id" class="form-select">
                                <?php mysqli_data_seek($priorities_result, 0); ?>
                                <?php while ($priority = mysqli_fetch_assoc($priorities_result)): ?>
                                <option value="<?php echo $priority['priority_id']; ?>"
                                        <?php echo ($priority['priority_id'] == $signal['priority_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($priority['priority_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Update the signal priority</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($signal['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="edit_signal_message_content" name="message" class="form-control" rows="6"><?php echo htmlspecialchars($signal['message']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resolution Notes</label>
                        <textarea name="resolution_notes" class="form-control" rows="4"><?php echo htmlspecialchars($signal['resolution_notes'] ?? ''); ?></textarea>
                        <small class="text-muted">Optional: Add notes about how this signal was resolved</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Update Modal -->
<div class="modal fade" id="addUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2"></i>Add Signal Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUpdateForm">
                <input type="hidden" name="signal_id" value="<?php echo $signal_id; ?>">
                <input type="hidden" name="update_type" value="comment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Update Message</label>
                        <textarea name="message" class="form-control" rows="4" 
                                  placeholder="What's the update?" required></textarea>
                        <small class="text-muted">Share progress, changes, or notes about this signal</small>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_internal" value="1" 
                               class="form-check-input" id="updateInternalCheck">
                        <label class="form-check-label" for="updateInternalCheck">
                            <i class="fa-solid fa-lock me-1"></i> Private Update (visible to admins only)
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Post Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/6i7udj9tuqovoj6lp5jpkopu2phxpzqoe6g35gx49wbr3v1u/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<script>
function copyShareLink() {
    const input = document.getElementById('signalShareLink');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
    btn.style.background = '#059669';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '#10b981';
    }, 2000);
}

$(document).ready(function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize TinyMCE for edit signal message
    tinymce.init({
        selector: '#edit_signal_message_content',
        plugins: [
            'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 
            'searchreplace', 'table', 'visualblocks', 'wordcount'
        ],
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
        height: 300,
        menubar: false,
        statusbar: false
    });
    
    // Handle focus events for TinyMCE in modals
    document.addEventListener('focusin', (e) => {
        if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
            e.stopImmediatePropagation();
        }
    });
    
    // Toggle update comments
    $('.btn-toggle-comments').on('click', function() {
        const updateId = $(this).data('update-id');
        const container = $('#comments-' + updateId);
        container.slideToggle(200);
        
        const icon = $(this).find('i');
        if (container.is(':visible')) {
            icon.removeClass('fa-regular').addClass('fa-solid');
        } else {
            icon.removeClass('fa-solid').addClass('fa-regular');
        }
    });
    
    // Add update
    $('#addUpdateForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/lh_updates/add_signal_update.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to add update');
                }
            },
            error: function() {
                alert('An error occurred while adding the update');
            }
        });
    });
    
    // Add comment to update
    $('.add-update-comment-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const updateId = form.data('update-id');
        const commentText = form.find('textarea').val().trim();
        
        if (!commentText) {
            alert('Please enter a comment');
            return;
        }
        
        $.ajax({
            url: 'ajax/lh_updates/add_update_comment.php',
            type: 'POST',
            data: {
                update_id: updateId,
                comment_text: commentText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to add comment');
                }
            },
            error: function() {
                alert('An error occurred while adding the comment');
            }
        });
    });
    
    // Edit update comment button
    $(document).on('click', '.edit-update-comment-btn', function() {
        const commentItem = $(this).closest('.update-comment-item');
        commentItem.find('.update-comment-text-display').hide();
        commentItem.find('.update-comment-date').hide();
        commentItem.find('.update-comment-actions').hide();
        commentItem.find('.update-comment-edit-form').show();
    });
    
    // Cancel edit update comment
    $(document).on('click', '.cancel-update-edit-btn', function() {
        const commentItem = $(this).closest('.update-comment-item');
        commentItem.find('.update-comment-text-display').show();
        commentItem.find('.update-comment-date').show();
        commentItem.find('.update-comment-actions').show();
        commentItem.find('.update-comment-edit-form').hide();
    });
    
    // Save edited update comment
    $(document).on('click', '.save-update-comment-btn', function() {
        const btn = $(this);
        const commentId = btn.data('comment-id');
        const commentItem = btn.closest('.update-comment-item');
        const newText = commentItem.find('.update-comment-edit-textarea').val().trim();
        
        if (!newText) {
            alert('Comment cannot be empty');
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: 'ajax/lh_updates/edit_update_comment.php',
            type: 'POST',
            data: {
                comment_id: commentId,
                comment_text: newText
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to update comment');
                    btn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Save');
                }
            },
            error: function() {
                alert('An error occurred while updating the comment');
                btn.prop('disabled', false).html('<i class="fa-solid fa-save"></i> Save');
            }
        });
    });
    
    // Delete update comment
    $(document).on('click', '.delete-update-comment-btn', function() {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        
        const btn = $(this);
        const commentId = btn.data('comment-id');
        
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Deleting...');
        
        $.ajax({
            url: 'ajax/lh_updates/delete_update_comment.php',
            type: 'POST',
            data: { comment_id: commentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to delete comment');
                    btn.prop('disabled', false).html('<i class="fa-solid fa-trash"></i> Delete');
                }
            },
            error: function() {
                alert('An error occurred while deleting the comment');
                btn.prop('disabled', false).html('<i class="fa-solid fa-trash"></i> Delete');
            }
        });
    });
    
	$('#editSignalForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get content from TinyMCE and set it to the textarea
        var messageContent = tinymce.get('edit_signal_message_content').getContent();
        $('#edit_signal_message_content').val(messageContent);
        
        // Validate that message content is not empty
        if (!messageContent || messageContent.trim() === '' || messageContent === '<p></p>' || messageContent === '<p><br></p>') {
            alert('Please provide a message description for your signal.');
            return false;
        }
        
        $.ajax({
            url: 'ajax/lighthouse_harbor/update_harbor_signal.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to update signal');
                }
            },
            error: function() {
                alert('An error occurred while updating the signal');
            }
        });
    });
});

function viewAttachment(filePath, fileName, isImage) {
    $('#attachmentFileName').text(fileName);
    $('#attachmentDownloadLink').attr('href', filePath).attr('download', fileName);
    
    const contentDiv = $('#attachmentContent');
    contentDiv.html('');
    
    if (isImage) {
        contentDiv.html(`
            <img src="${filePath}" 
                 alt="${fileName}" 
                 style="max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        `);
    } else {
        const fileExt = fileName.split('.').pop().toLowerCase();
        if (fileExt === 'pdf') {
            contentDiv.html(`
                <div style="background: white; padding: 20px; border-radius: 8px;">
                    <i class="fa-solid fa-file-pdf fa-5x mb-3" style="color: #ef4444;"></i>
                    <h5>${fileName}</h5>
                    <p class="text-muted">Click the download button below to view this PDF</p>
                </div>
            `);
        } else {
            contentDiv.html(`
                <div style="background: white; padding: 20px; border-radius: 8px;">
                    <i class="fa-solid fa-file fa-5x mb-3" style="color: #6b7280;"></i>
                    <h5>${fileName}</h5>
                    <p class="text-muted">Click the download button below to download this file</p>
                </div>
            `);
        }
    }
    
    const modal = new bootstrap.Modal(document.getElementById('attachmentViewerModal'));
    modal.show();
}
</script>