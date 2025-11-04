<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('system_links_admin')) {
 	exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createFolder();
        break;
    case 'update':
        updateFolder();
        break;
    case 'delete':
        deleteFolder();
        break;
    case 'list':
        listFolders();
        break;
    case 'get':
        getFolder();
        break;
    case 'dropdown':
        getFoldersDropdown();
        break;
    default:
        if ($_POST['folder_id']) {
            updateFolder();
        } else {
            createFolder();
        }
        break;
}

function createFolder() {
    global $dbc;
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']) ?: 'fas fa-folder';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
 	if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Folder name is required']);
        return;
    }
    
    if (empty($category_id)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
	$sort_condition = "category_id = ?";
    $sort_params = [$category_id];
	
	$sort_query = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM rsb_folders WHERE $sort_condition";
    $sort_stmt = mysqli_prepare($dbc, $sort_query);
    mysqli_stmt_bind_param($sort_stmt, 'i', $category_id);
    mysqli_stmt_execute($sort_stmt);
    $sort_result = mysqli_stmt_get_result($sort_stmt);
    $next_sort = mysqli_fetch_assoc($sort_result)['next_sort'];
    
    $query = "INSERT INTO rsb_folders (name, description, icon, category_id, sort_order) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sssii', $name, $description, $icon, $category_id, $next_sort);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Folder created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create folder']);
    }
}

function updateFolder() {
    global $dbc;
    
    $id = (int)$_POST['folder_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']) ?: 'fas fa-folder';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
	if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Folder name is required']);
        return;
    }
    
    if (empty($category_id)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        return;
    }
    
    $query = "UPDATE rsb_folders SET name = ?, description = ?, icon = ?, category_id = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'sssii', $name, $description, $icon, $category_id, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Folder updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update folder']);
    }
}

function deleteFolder() {
    global $dbc;
    
    $id = (int)$_POST['id'];
    
    // FIX: Only check for ACTIVE weblinks
	$check_query = "SELECT COUNT(*) as weblink_count FROM rsb_weblinks WHERE folder_id = ? AND is_active = 1";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'i', $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $counts = mysqli_fetch_assoc($check_result);
    
    if ($counts['weblink_count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete folder with existing weblinks. Please move or delete them first.'
        ]);
        return;
    }
    
    $query = "DELETE FROM rsb_folders WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Folder deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete folder']);
    }
}

function listFolders() {
    global $dbc;
    
    // FIX: Only count ACTIVE weblinks
    $query = "SELECT f.*, c.name as category_name,
        (SELECT COUNT(*) FROM rsb_weblinks w WHERE w.folder_id = f.id AND w.is_active = 1) as weblink_count
        FROM rsb_folders f 
        LEFT JOIN rsb_categories c ON f.category_id = c.id
        WHERE f.is_active = 1 
        ORDER BY c.sort_order, c.name, f.sort_order, f.name";
    
    $result = mysqli_query($dbc, $query);
    $html = '';
    
	while ($row = mysqli_fetch_assoc($result)) {
	    $html .= '<div class="folders py-2 bg-light rounded shadow-sm p-3 mb-2">';
	    $html .= '<div class="mb-2">';
	    $html .= '<strong>' . htmlspecialchars($row['name']) . '</strong>';
	    if ($row['description']) {
	        $html .= '<br><span class="text-muted">' . htmlspecialchars($row['description']) . '</span>';
	    }
	    $html .= '<br><span class="text-primary">Category: ' . htmlspecialchars($row['category_name'] ?? 'Unassigned') . '</span>';
	    $html .= '<br><span class="text-primary">Weblinks: ' . $row['weblink_count'] . '</span>';
	    $html .= '</div>';
	    $html .= '<div class="btn-group btn-group-sm">';
	    $html .= '<button class="btn btn-primary" onclick="editFolder(' . $row['id'] . ')"><i class="fas fa-edit"></i> Edit</button>';
	    $html .= '<button class="btn btn-outline-primary" onclick="deleteFolder(' . $row['id'] . ')"><i class="fas fa-trash"></i> Delete</button>';
	    $html .= '</div>';
	    $html .= '</div>';
	}
    
    echo json_encode(['success' => true, 'data' => $html]);
}

function getFolder() {
    global $dbc;
    
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM rsb_folders WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Folder not found']);
    }
}

function getFoldersDropdown() {
    global $dbc;
    
    $category_id = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $exclude_id = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;
    
    if (!$category_id) {
        echo json_encode(['success' => true, 'data' => '']);
        return;
    }
    
    $query = "SELECT id, name FROM rsb_folders WHERE category_id = ? AND is_active = 1";
    if ($exclude_id) {
        $query .= " AND id != ?";
    }
    $query .= " ORDER BY sort_order, name";
    
    $stmt = mysqli_prepare($dbc, $query);
    if ($exclude_id) {
        mysqli_stmt_bind_param($stmt, 'ii', $category_id, $exclude_id);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $category_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $html = '';
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    
    echo json_encode(['success' => true, 'data' => $html]);
}
?>