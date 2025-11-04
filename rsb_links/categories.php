<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('system_links_admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createCategory();
        break;
    case 'update':
        updateCategory();
        break;
    case 'delete':
        deleteCategory();
        break;
    case 'list':
        listCategories();
        break;
    case 'get':
        getCategory();
        break;
    case 'dropdown':
        getCategoriesDropdown();
        break;
    default:
        if ($_POST['category_id']) {
            updateCategory();
        } else {
            createCategory();
        }
        break;
}

function createCategory() {
    global $dbc;
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
	if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        return;
    }
    
	$sort_query = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM rsb_categories";
    $sort_result = mysqli_query($dbc, $sort_query);
    $next_sort = mysqli_fetch_assoc($sort_result)['next_sort'];
    
    $query = "INSERT INTO rsb_categories (name, description, sort_order) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $next_sort);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Category created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create category']);
    }
}

function updateCategory() {
    global $dbc;
    
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
 	if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        return;
    }
    
    $query = "UPDATE rsb_categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category']);
    }
}

function deleteCategory() {
    global $dbc;
    
    $id = (int)$_POST['id'];
    
    // FIX: Only check for ACTIVE folders and weblinks
	$check_query = "SELECT 
        (SELECT COUNT(*) FROM rsb_folders WHERE category_id = ? AND is_active = 1) as folder_count,
        (SELECT COUNT(*) FROM rsb_weblinks WHERE category_id = ? AND is_active = 1) as weblink_count";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $id, $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $counts = mysqli_fetch_assoc($check_result);
    
    if ($counts['folder_count'] > 0 || $counts['weblink_count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete category with existing folders or weblinks. Please move or delete them first.'
        ]);
        return;
    }
    
    $query = "DELETE FROM rsb_categories WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
    }
}

function listCategories() {
    global $dbc;

    // FIX: Only count ACTIVE folders and weblinks
    $query = "SELECT c.*, 
        (SELECT COUNT(*) FROM rsb_folders f WHERE f.category_id = c.id AND f.is_active = 1) as folder_count,
        (SELECT COUNT(*) FROM rsb_weblinks w WHERE w.category_id = c.id AND w.is_active = 1) as weblink_count
        FROM rsb_categories c 
        WHERE c.is_active = 1 
        ORDER BY c.sort_order, c.name";

    $result = mysqli_query($dbc, $query);
    $html = '';

    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<div class="category-search bg-light rounded shadow-sm py-2 p-3 mb-2">';
        $html .= '<div class="mb-2">';
        $html .= '<strong>' . htmlspecialchars($row['name']) . '</strong>';
        if ($row['description']) {
            $html .= '<br><span class="text-muted">' . htmlspecialchars($row['description']) . '</span>';
        }
        $html .= '<br><span class="text-primary">' . $row['folder_count'] . ' folders, ' . $row['weblink_count'] . ' weblinks</span>';
        $html .= '</div>';
        $html .= '<div class="btn-group btn-group-sm">';
        $html .= '<button class="btn btn-primary" onclick="editCategory(' . $row['id'] . ')"><i class="fas fa-edit"></i> Edit</button>';
        $html .= '<button class="btn btn-outline-primary" onclick="deleteCategory(' . $row['id'] . ')"><i class="fas fa-trash"></i> Delete</button>';
        $html .= '</div>';
        $html .= '</div>';
    }

    echo json_encode(['success' => true, 'data' => $html]);
}

function getCategory() {
    global $dbc;
    
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM rsb_categories WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
}

function getCategoriesDropdown() {
    global $dbc;
    
    $query = "SELECT id, name FROM rsb_categories WHERE is_active = 1 ORDER BY sort_order, name";
    $result = mysqli_query($dbc, $query);
    $html = '';
    
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    
    echo json_encode(['success' => true, 'data' => $html]);
}
?>