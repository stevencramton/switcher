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
    case 'load':
        loadSortableData();
        break;
    case 'update_sort':
        updateSortOrder();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function loadSortableData() {
    global $dbc;
    
	$categories_html = loadCategories();
    $unassigned_folders_html = loadUnassignedFolders();
    $unassigned_weblinks_html = loadUnassignedWeblinks();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories_html,
        'folders' => $unassigned_folders_html,
        'weblinks' => $unassigned_weblinks_html
    ]);
}

function loadCategories() {
    global $dbc;
    
    $query = "SELECT * FROM rsb_categories WHERE is_active = 1 ORDER BY sort_order";
    $result = mysqli_query($dbc, $query);
    $html = '';
    
    while ($category = mysqli_fetch_assoc($result)) {
        $html .= '<div class="category p-2 border border-white mb-1 sortable-item" style="background-color:#c5d0da;" data-id="' . $category['id'] . '" data-type="category">';
        $html .= '<span class="js-handle-category px-1 fw-bold" role="button" draggable="true">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1"></i> ' . htmlspecialchars($category['name']);
        $html .= '</span>';
        $html .= '<span class="deletegrouphover">';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteCategory(' . $category['id'] . ')"></i>';
        $html .= '</span>';
  	  	$html .= '<div class="list-group nested-sortable category-folders mt-2" data-parent-id="' . $category['id'] . '" data-parent-type="category">';
        $html .= loadCategoryFolders($category['id']);
        $html .= '</div>';
    	$html .= '<div class="mt-2">';
        $html .= '<small class="text-muted fw-bold">Direct Links:</small>';
        $html .= '<div class="list-group nested-sortable category-weblinks mt-1" data-parent-id="' . $category['id'] . '" data-parent-type="category">';
        $html .= loadCategoryDirectWeblinks($category['id']);
        $html .= '</div>';
        $html .= '</div>';
     	$html .= '</div>';
    }
	return $html;
}

function loadCategoryDirectWeblinks($category_id) {
    global $dbc;
	
	$query = "SELECT * FROM rsb_weblinks WHERE category_id = ? AND folder_id IS NULL AND is_active = 1 ORDER BY sort_order";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $html = '';
    
    while ($weblink = mysqli_fetch_assoc($result)) {
        $html .= '<div class="weblink bg-light border border-white p-1 mb-1 sortable-item" data-id="' . $weblink['id'] . '" data-type="weblink">';
        $html .= '<span class="fw-bold" role="button" draggable="true">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1"></i> ' . htmlspecialchars($weblink['title']);
        $html .= '</span>';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteWeblink(' . $weblink['id'] . ')"></i>';
        $html .= '</div>';
    }
    
    if (empty($html)) {
        $html = '<div class="text-center text-muted p-2" style="font-size: 0.8rem;">No direct links</div>';
    }
    
    return $html;
}

function loadCategoryFolders($category_id) {
    global $dbc;
    
    $query = "SELECT * FROM rsb_folders WHERE category_id = ? AND is_active = 1 ORDER BY sort_order";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $html = '';
    
    while ($folder = mysqli_fetch_assoc($result)) {
      	$html .= '<div class="accordion accordion-flush mb-1 sortable-item" data-id="' . $folder['id'] . '" data-type="folder" id="accordionCategory' . $category_id . 'Folder' . $folder['id'] . '">';
        $html .= '<div class="accordion-item border shadow-sm">';
        $html .= '<h6 class="accordion-header p-2" style="background-color:#dfe7ee;">';
        $html .= '<span class="fw-bold folder" role="button" draggable="true" data-bs-toggle="collapse" ';
        $html .= 'data-bs-target="#flush-collapse-' . $folder['id'] . '" aria-expanded="false" aria-controls="flush-collapse-' . $folder['id'] . '">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1 ms-1"></i> ' . htmlspecialchars($folder['name']);
        $html .= '</span>';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteFolder(' . $folder['id'] . ')"></i>';
        $html .= '</h6>';
        $html .= '<div id="flush-collapse-' . $folder['id'] . '" class="accordion-collapse collapse show" data-bs-parent="#accordionCategory' . $category_id . 'Folder' . $folder['id'] . '">';
        $html .= '<div class="accordion-body p-1">';
        $html .= '<div class="list-group nested-sortable folder-weblinks mt-0" data-parent-id="' . $folder['id'] . '" data-parent-type="folder">';
        $html .= loadFolderWeblinks($folder['id']);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
 	return $html;
}

function loadFolderWeblinks($folder_id) {
    global $dbc;
    
    $query = "SELECT * FROM rsb_weblinks WHERE folder_id = ? AND is_active = 1 ORDER BY sort_order";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $folder_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $html = '';
    
    while ($weblink = mysqli_fetch_assoc($result)) {
        $html .= '<div class="weblink bg-light border border-white p-2 mb-1 sortable-item" data-id="' . $weblink['id'] . '" data-type="weblink">';
        $html .= '<span class="fw-bold" role="button" draggable="true">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1"></i> ' . htmlspecialchars($weblink['title']);
        $html .= '</span>';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteWeblink(' . $weblink['id'] . ')"></i>';
        $html .= '</div>';
    }
    
    return $html;
}

function loadUnassignedFolders() {
    global $dbc;
    
	$query = "SELECT f.*, c.name as category_name FROM rsb_folders f 
        LEFT JOIN rsb_categories c ON f.category_id = c.id
        WHERE f.is_active = 1 AND f.category_id IS NULL
        ORDER BY f.sort_order";
    $result = mysqli_query($dbc, $query);
    $html = '';
    
    while ($folder = mysqli_fetch_assoc($result)) {
    	$html .= '<div class="accordion accordion-flush mb-1 sortable-item" data-id="' . $folder['id'] . '" data-type="folder" id="accordionMainFolder' . $folder['id'] . '">';
        $html .= '<div class="accordion-item border shadow-sm">';
        $html .= '<h6 class="accordion-header p-2" style="background-color:#dfe7ee;">';
        $html .= '<span class="fw-bold folder" role="button" draggable="true" data-bs-toggle="collapse" ';
        $html .= 'data-bs-target="#flush-collapse-main-' . $folder['id'] . '" aria-expanded="false" aria-controls="flush-collapse-main-' . $folder['id'] . '">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1 ms-1"></i> ' . htmlspecialchars($folder['name']);
        $html .= ' <small class="text-muted">(Unassigned)</small>';
        $html .= '</span>';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteFolder(' . $folder['id'] . ')"></i>';
        $html .= '</h6>';
        $html .= '<div id="flush-collapse-main-' . $folder['id'] . '" class="accordion-collapse collapse show" data-bs-parent="#accordionMainFolder' . $folder['id'] . '">';
        $html .= '<div class="accordion-body p-1">';
        $html .= '<div class="list-group nested-sortable folder-weblinks mt-0" data-parent-id="' . $folder['id'] . '" data-parent-type="folder">';
        $html .= loadFolderWeblinks($folder['id']);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    if (empty($html)) {
        $html = '<div class="text-center text-muted p-4">';
        $html .= '<i class="fas fa-folder-open fa-2x mb-2"></i><br>';
        $html .= 'No unassigned folders<br>';
        $html .= '<small>All folders are assigned to categories</small>';
        $html .= '</div>';
    }
    
    return $html;
}

function loadUnassignedWeblinks() {
    global $dbc;
    
	$query = "SELECT w.* FROM rsb_weblinks w 
        WHERE w.is_active = 1 AND w.folder_id IS NULL AND w.category_id IS NULL
        ORDER BY w.sort_order";
    $result = mysqli_query($dbc, $query);
    $html = '';
    
    while ($weblink = mysqli_fetch_assoc($result)) {
        $html .= '<div class="weblink bg-light border border-white p-2 mb-1 sortable-item" data-id="' . $weblink['id'] . '" data-type="weblink">';
        $html .= '<span class="fw-bold" role="button" draggable="true">';
        $html .= '<i class="fa-solid fa-grip-vertical me-1"></i> ' . htmlspecialchars($weblink['title']);
        $html .= ' <small class="text-muted">(Unassigned)</small>';
        $html .= '</span>';
        $html .= '<i class="fa-solid fa-trash-can float-end mt-1 me-1 opacity-50" role="button" onclick="deleteWeblink(' . $weblink['id'] . ')"></i>';
        $html .= '</div>';
    }
    
    if (empty($html)) {
        $html = '<div class="text-center text-muted p-4">';
        $html .= '<i class="fas fa-link fa-2x mb-2"></i><br>';
        $html .= 'No unassigned weblinks<br>';
        $html .= '<small>All weblinks are assigned to folders or categories</small>';
        $html .= '</div>';
    }
    
    return $html;
}

function updateSortOrder() {
    global $dbc;
    
	if (!isset($_POST['type']) || !isset($_POST['item_id'])) {
    	echo json_encode(['success' => false, 'message' => 'Missing required parameters: ' . print_r($_POST, true)]);
        return;
    }
    
    $type = $_POST['type'];
    $item_id = (int)$_POST['item_id'];
    $new_index = (int)$_POST['new_index'];
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $parent_type = $_POST['parent_type'] ?? null;
    
	switch ($type) {
        case 'categories':
            updateCategorySortOrder($item_id, $new_index);
            break;
        case 'folders':
            updateFolderSortOrder($item_id, $new_index, $parent_id, $parent_type);
            break;
        case 'weblinks':
            updateWeblinkSortOrder($item_id, $new_index, $parent_id, $parent_type);
            break;
        default:
        	echo json_encode(['success' => false, 'message' => 'Invalid type: ' . $type]);
            return;
    }
}

function updateCategorySortOrder($item_id, $new_index) {
    global $dbc;
    
	$query = "SELECT id FROM rsb_categories WHERE is_active = 1 ORDER BY sort_order";
    $result = mysqli_query($dbc, $query);
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row['id'];
    }
    
	$current_index = array_search($item_id, $categories);
    if ($current_index !== false) {
        array_splice($categories, $current_index, 1);
        array_splice($categories, $new_index, 0, $item_id);
        
   	 	foreach ($categories as $index => $category_id) {
            $update_query = "UPDATE rsb_categories SET sort_order = ? WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'ii', $index, $category_id);
            mysqli_stmt_execute($stmt);
        }
  	  	echo json_encode(['success' => true, 'message' => 'Category sort order updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
}

function updateFolderSortOrder($item_id, $new_index, $parent_id, $parent_type) {
    global $dbc;
 	
	$check_query = "SELECT id, name, category_id FROM rsb_folders WHERE id = ?";
    $check_stmt = mysqli_prepare($dbc, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'i', $item_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $current_state = mysqli_fetch_assoc($check_result);
    
	if ($parent_type === 'category' && $parent_id) {
     	$update_query = "UPDATE rsb_folders SET category_id = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $parent_id, $item_id);
        $result = mysqli_stmt_execute($stmt);
      	if (!$result) {
            error_log("MySQL error: " . mysqli_error($dbc));
        }
    } elseif ($parent_type === 'main') {
    	$update_query = "UPDATE rsb_folders SET category_id = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'i', $item_id);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log("MySQL error: " . mysqli_error($dbc));
        }
    }
    
	mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $new_state = mysqli_fetch_assoc($check_result);
	$condition = "is_active = 1";
    $params = [];
    $param_types = '';
    
    if ($parent_type === 'category' && $parent_id) {
        $condition .= " AND category_id = ?";
        $params[] = $parent_id;
        $param_types = 'i';
    } elseif ($parent_type === 'main') {
     	$condition .= " AND category_id IS NULL";
    }
    
    $query = "SELECT id, name FROM rsb_folders WHERE $condition ORDER BY sort_order";
    
	if (!empty($params)) {
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($dbc, $query);
    }
    
    $folders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $folders[] = $row['id'];
	}
    
	$current_index = array_search($item_id, $folders);
    if ($current_index !== false) {
    	array_splice($folders, $current_index, 1);
    	array_splice($folders, $new_index, 0, $item_id);
        
    	foreach ($folders as $index => $folder_id) {
            $update_query = "UPDATE rsb_folders SET sort_order = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'ii', $index, $folder_id);
            $sort_result = mysqli_stmt_execute($stmt);
           
        }
   	 	echo json_encode(['success' => true, 'message' => 'Folder sort order updated']);
    } else {
   	 	echo json_encode(['success' => false, 'message' => 'Folder not found in current container']);
    }
}

function updateWeblinkSortOrder($item_id, $new_index, $parent_id, $parent_type) {
    global $dbc;
    
	if ($parent_type === 'folder') {
     	$update_query = "UPDATE rsb_weblinks SET folder_id = ?, category_id = (SELECT category_id FROM rsb_folders WHERE id = ?), updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'iii', $parent_id, $parent_id, $item_id);
        mysqli_stmt_execute($stmt);
       
    } elseif ($parent_type === 'category') {
    	$update_query = "UPDATE rsb_weblinks SET category_id = ?, folder_id = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $parent_id, $item_id);
        mysqli_stmt_execute($stmt);
        
    } elseif ($parent_type === 'main') {
   	 	$update_query = "UPDATE rsb_weblinks SET category_id = NULL, folder_id = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($dbc, $update_query);
        mysqli_stmt_bind_param($stmt, 'i', $item_id);
        mysqli_stmt_execute($stmt);
        
    }
    
	$condition = "is_active = 1";
    $params = [];
    $param_types = '';
    
    if ($parent_type === 'folder') {
        $condition .= " AND folder_id = ?";
        $params[] = $parent_id;
        $param_types = 'i';
    } elseif ($parent_type === 'category') {
        $condition .= " AND category_id = ? AND folder_id IS NULL";
        $params[] = $parent_id;
        $param_types = 'i';
    } elseif ($parent_type === 'main') {
     	$condition .= " AND category_id IS NULL AND folder_id IS NULL";
    }
    
    $query = "SELECT id FROM rsb_weblinks WHERE $condition ORDER BY sort_order";
   
	if (!empty($params)) {
        $stmt = mysqli_prepare($dbc, $query);
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($dbc, $query);
    }
    
    $weblinks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $weblinks[] = $row['id'];
    }
    
	$current_index = array_search($item_id, $weblinks);
    if ($current_index !== false) {
        array_splice($weblinks, $current_index, 1);
        array_splice($weblinks, $new_index, 0, $item_id);
        
     	foreach ($weblinks as $index => $weblink_id) {
            $update_query = "UPDATE rsb_weblinks SET sort_order = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($dbc, $update_query);
            mysqli_stmt_bind_param($stmt, 'ii', $index, $weblink_id);
            mysqli_stmt_execute($stmt);
        }
        
        echo json_encode(['success' => true, 'message' => 'Weblink sort order updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Weblink not found in current container']);
    }
}
?>