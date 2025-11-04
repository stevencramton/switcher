<?php
session_start();
include '../../mysqli_connect.php';

function generateDynamicRightSidebar() {
    global $dbc;
    
    $html = '<div class="sidebar-item sidebar-menu">';
    $html .= '<ul>';
    
	$categories_query = "SELECT * FROM rsb_categories WHERE is_active = 1 ORDER BY sort_order";
    $categories_result = mysqli_query($dbc, $categories_query);
    
    while ($category = mysqli_fetch_assoc($categories_result)) {
    	$html .= '<li class="header-menu menu-right-three">';
        $html .= '<span>' . htmlspecialchars($category['name']) . '</span>';
        $html .= '</li>';
        
     	$folders_query = "SELECT * FROM rsb_folders WHERE category_id = ? AND is_active = 1 ORDER BY sort_order";
        $folders_stmt = mysqli_prepare($dbc, $folders_query);
        mysqli_stmt_bind_param($folders_stmt, 'i', $category['id']);
        mysqli_stmt_execute($folders_stmt);
        $folders_result = mysqli_stmt_get_result($folders_stmt);
        
     	while ($folder = mysqli_fetch_assoc($folders_result)) {
            $html .= '<li class="sidebar-dropdown-right right-search-three">';
            $html .= '<a href="javascript:void(0);">';
            $html .= '<i class="' . htmlspecialchars($folder['icon']) . '"></i>';
            $html .= '<span class="menu-text">' . htmlspecialchars($folder['name']) . '</span>';
            $html .= '<span class="badge bg-warning"></span>';
            $html .= '</a>';
            $html .= '<div class="sidebar-submenu-right">';
            $html .= '<ul>';
            
         	$weblinks_query = "SELECT * FROM rsb_weblinks WHERE folder_id = ? AND is_active = 1 ORDER BY sort_order";
            $weblinks_stmt = mysqli_prepare($dbc, $weblinks_query);
            mysqli_stmt_bind_param($weblinks_stmt, 'i', $folder['id']);
            mysqli_stmt_execute($weblinks_stmt);
            $weblinks_result = mysqli_stmt_get_result($weblinks_stmt);
            
            while ($weblink = mysqli_fetch_assoc($weblinks_result)) {
                $html .= '<li>';
                $target = $weblink['open_new_tab'] ? ' target="_blank"' : '';
                $html .= '<a href="' . htmlspecialchars($weblink['url']) . '"' . $target . '>';
                $html .= htmlspecialchars($weblink['title']);
                
            	if ($weblink['is_favorite']) {
                    $html .= '<span class="badge bg-warning"></span>';
                }
                
                $html .= '</a>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</li>';
        }
        
     	$direct_weblinks_query = "SELECT * FROM rsb_weblinks WHERE category_id = ? AND folder_id IS NULL AND is_active = 1 ORDER BY sort_order";
        $direct_weblinks_stmt = mysqli_prepare($dbc, $direct_weblinks_query);
        mysqli_stmt_bind_param($direct_weblinks_stmt, 'i', $category['id']);
        mysqli_stmt_execute($direct_weblinks_stmt);
        $direct_weblinks_result = mysqli_stmt_get_result($direct_weblinks_stmt);
        
      	if (mysqli_num_rows($direct_weblinks_result) > 0) {
            $html .= '<li class="sidebar-dropdown-right right-search-three">';
            $html .= '<a href="javascript:void(0);">';
            $html .= '<i class="' . htmlspecialchars($category['icon']) . '"></i>';
            $html .= '<span class="menu-text">' . htmlspecialchars($category['name']) . ' Links</span>';
            $html .= '<span class="badge bg-warning"></span>';
            $html .= '</a>';
            $html .= '<div class="sidebar-submenu-right">';
            $html .= '<ul>';
            
            while ($weblink = mysqli_fetch_assoc($direct_weblinks_result)) {
                $html .= '<li>';
                $target = $weblink['open_new_tab'] ? ' target="_blank"' : '';
                $html .= '<a href="' . htmlspecialchars($weblink['url']) . '"' . $target . '>';
                $html .= htmlspecialchars($weblink['title']);
                
             	if ($weblink['is_favorite']) {
                    $html .= '<span class="badge bg-warning"></span>';
                }
                
                $html .= '</a>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</li>';
        }
    }
    
    $html .= '</ul>';
    $html .= '</div>';
	
	$html .= '<script>
	$(document).ready(function() {
	    $(".admin-right-sidebar-links .sidebar-dropdown-right > a").click(function(e) {
	        e.preventDefault();
	        $(this).siblings(".sidebar-submenu-right").slideToggle(200);
	    });
	});
	</script>';
    
    return $html;
}

echo generateDynamicRightSidebar();
?>