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
        createWeblink();
        break;
    case 'update':
        updateWeblink();
        break;
    case 'delete':
        deleteWeblink();
        break;
    case 'list':
        listWeblinks();
        break;
    case 'get':
        getWeblink();
        break;
    default:
        if ($_POST['weblink_id']) {
            updateWeblink();
        } else {
            createWeblink();
        }
        break;
}

function createWeblink() {
    global $dbc;
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = trim($_POST['url']);
    $original_url = trim($_POST['original_url'] ?? $_POST['url']);
    $link_protocol = $_POST['link_protocol'] ?? 'no_protocol';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $folder_id = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
    $open_new_tab = isset($_POST['open_new_tab']) ? 1 : 0;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Weblink title is required']);
        return;
    }
    
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'URL is required']);
        return;
    }
    
 	$valid_protocols = ['https://', 'http://', 'local_link', 'no_protocol'];
    if (!in_array($link_protocol, $valid_protocols)) {
        echo json_encode(['success' => false, 'message' => 'Invalid protocol selected']);
        return;
    }
    
 	if ($link_protocol === 'https://' || $link_protocol === 'http://') {
      	if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid URL format']);
            return;
        }
    } elseif ($link_protocol === 'local_link') {
      	$full_local_url = 'https://switchboardapp.net/dashboard/' . ltrim($original_url, '/');
        if (!filter_var($full_local_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid local path']);
            return;
        }
    } elseif ($link_protocol === 'no_protocol') {
    	if (!preg_match('/^https?:\/\//', $url)) {
            echo json_encode(['success' => false, 'message' => 'When "No Protocol" is selected, the URL must include http:// or https://']);
            return;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid URL']);
            return;
        }
    }
    
    $sort_condition = "1=1";
    $sort_params = [];
    if ($folder_id) {
        $sort_condition = "folder_id = ?";
        $sort_params = [$folder_id];
    } elseif ($category_id) {
        $sort_condition = "category_id = ? AND folder_id IS NULL";
        $sort_params = [$category_id];
    } else {
        $sort_condition = "category_id IS NULL AND folder_id IS NULL";
    }
    
    $sort_query = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_sort FROM rsb_weblinks WHERE $sort_condition";
    if (!empty($sort_params)) {
        $sort_stmt = mysqli_prepare($dbc, $sort_query);
        if (count($sort_params) == 1) {
            mysqli_stmt_bind_param($sort_stmt, 'i', ...$sort_params);
        }
        mysqli_stmt_execute($sort_stmt);
        $sort_result = mysqli_stmt_get_result($sort_stmt);
    } else {
        $sort_result = mysqli_query($dbc, $sort_query);
    }
    $next_sort = mysqli_fetch_assoc($sort_result)['next_sort'];
    
    $query = "INSERT INTO rsb_weblinks (title, description, url, link_protocol, category_id, folder_id, open_new_tab, is_favorite, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'ssssiiiii', $title, $description, $url, $link_protocol, $category_id, $folder_id, $open_new_tab, $is_favorite, $next_sort);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Weblink created successfully']);
    } else {
        // Log the actual error details for debugging (server-side only)
        error_log('Weblink Create Error: ' . mysqli_error($dbc));
        
        // Return generic error message to client (no sensitive information)
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create weblink']);
    }
}

function updateWeblink() {
    global $dbc;
    
    $id = isset($_POST['weblink_id']) && is_numeric($_POST['weblink_id']) ? (int)$_POST['weblink_id'] : 0;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = trim($_POST['url']);
    $original_url = trim($_POST['original_url'] ?? $_POST['url']);
    $link_protocol = $_POST['link_protocol'] ?? 'no_protocol';
    $category_id = (isset($_POST['category_id']) && is_numeric($_POST['category_id'])) ? (int)$_POST['category_id'] : null;
    $folder_id = (isset($_POST['folder_id']) && is_numeric($_POST['folder_id'])) ? (int)$_POST['folder_id'] : null;
    $open_new_tab = isset($_POST['open_new_tab']) ? 1 : 0;
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Weblink title is required']);
        return;
    }
    
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'URL is required']);
        return;
    }
    
 	$valid_protocols = ['https://', 'http://', 'local_link', 'no_protocol'];
    if (!in_array($link_protocol, $valid_protocols)) {
        echo json_encode(['success' => false, 'message' => 'Invalid protocol selected']);
        return;
    }
    
  	if ($link_protocol === 'https://' || $link_protocol === 'http://') {
      	if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid URL format']);
            return;
        }
    } elseif ($link_protocol === 'local_link') {
      	$full_local_url = 'https://switchboardapp.net/dashboard/' . ltrim($original_url, '/');
        if (!filter_var($full_local_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid local path']);
            return;
        }
    } elseif ($link_protocol === 'no_protocol') {
      	if (!preg_match('/^https?:\/\//', $url)) {
            echo json_encode(['success' => false, 'message' => 'When "No Protocol" is selected, the URL must include http:// or https://']);
            return;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid URL']);
            return;
        }
    }
    
    $query = "UPDATE rsb_weblinks 
        SET title = ?, description = ?, url = ?, link_protocol = ?,
            category_id = ?, folder_id = ?, 
            open_new_tab = ?, is_favorite = ?, 
            updated_at = NOW() 
        WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);

    $category_id = is_null($category_id) ? null : $category_id;
    $folder_id = is_null($folder_id) ? null : $folder_id;

    mysqli_stmt_bind_param(
        $stmt,
        'ssssiiiii',
        $title,
        $description,
        $url,
        $link_protocol,
        $category_id,
        $folder_id,
        $open_new_tab,
        $is_favorite,
        $id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Weblink updated successfully']);
    } else {
        // Log the actual error details for debugging (server-side only)
        error_log('Weblink Update Error: ' . mysqli_error($dbc));
        
        // Return generic error message to client (no sensitive information)
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update weblink']);
    }
}

function deleteWeblink() {
    global $dbc;
    
    $id = (int)$_POST['id'];
    
    $query = "UPDATE rsb_weblinks SET is_active = 0, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Weblink deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete weblink']);
    }
}

function listWeblinks() {
    global $dbc;
    
    $query = "SELECT w.*, c.name as category_name, f.name as folder_name
        FROM rsb_weblinks w 
        LEFT JOIN rsb_categories c ON w.category_id = c.id
        LEFT JOIN rsb_folders f ON w.folder_id = f.id
        WHERE w.is_active = 1 
        ORDER BY c.sort_order, c.name, f.sort_order, f.name, w.sort_order, w.title";
    
    $result = mysqli_query($dbc, $query);
    $html = '';
    
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<div class="weblinks py-2 bg-light rounded shadow-sm p-3 mb-2">';
        $html .= '<div>';
        $html .= '<strong>' . htmlspecialchars($row['title']) . '</strong>';
        if ($row['description']) {
            $html .= '<br><span class="text-muted">' . htmlspecialchars($row['description']) . '</span>';
        }
        $html .= '<br><span class="text-primary">';
        if ($row['category_name']) {
            $html .= 'Category: ' . htmlspecialchars($row['category_name']);
        }
        if ($row['folder_name']) {
            $html .= ' | Folder: ' . htmlspecialchars($row['folder_name']);
        }
        $html .= '</span>';
        
        $truncated_url = strlen($row['url']) > 50 ? substr($row['url'], 0, 50) . '...' : $row['url'];
        $html .= '<br><span class="text-secondary" title="' . htmlspecialchars($row['url']) . '">' . htmlspecialchars($truncated_url) . '</span>';
        
      	if (!empty($row['link_protocol'])) {
            $protocol_label = $row['link_protocol'];
            if ($protocol_label === 'local_link') $protocol_label = 'Local';
            if ($protocol_label === 'no_protocol') $protocol_label = 'Direct';
            $html .= '<span class="badge bg-secondary ms-2">' . htmlspecialchars($protocol_label) . '</span>';
        }
        
        if ($row['is_favorite']) {
            $html .= '<i class="fa-solid fa-star ms-2 text-warning"></i>';
        }
        if ($row['open_new_tab']) {
            $html .= '<i class="fa-solid fa-up-right-from-square text-primary ms-2"></i>';
        }
        $html .= '</div>';
        $html .= '<div class="mt-2">';
        $html .= '<div class="btn-group btn-group-sm">';
        $html .= '<a href="' . htmlspecialchars($row['url']) . '" target="_blank" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Open</a>';
        $html .= '<button class="btn btn-outline-primary" onclick="editWeblink(' . $row['id'] . ')"><i class="fas fa-edit"></i> Edit</button>';
        $html .= '<button class="btn btn-outline-primary" onclick="deleteWeblink(' . $row['id'] . ')"><i class="fas fa-trash"></i> Delete</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    echo json_encode(['success' => true, 'data' => $html]);
}

function getWeblink() {
    global $dbc;
    
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM rsb_weblinks WHERE id = ? AND is_active = 1";
    $stmt = mysqli_prepare($dbc, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Weblink not found']);
    }
}
?>