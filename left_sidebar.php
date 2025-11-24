<script>
$(document).ready(function(){
	$('#search-contact-options').on('click', function() {
	    $('#search_options_modal').show();
	});
	$("#sidebar-search").on("keyup", function() {
		if ($("#sidebar-search").val() == ''){
			$(".sidebar-item-left").find('.fa').removeClass('fa-times').addClass("fa-search");
		} else {
			$(".sidebar-item-left").find('.fa').removeClass('fa-search').addClass("fa-times");
			$('.fa-times').click(function() {
				var value = $(this).val().toLowerCase();
				$("#sidebar li.left-search").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
				$("#sidebar .menu-left").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
				$('.input[type="text"], .leftnav').val('').trigger('propertychange').focus();
				$(".sidebar-item-left").find('.fa').removeClass('fa-times').addClass("fa-search");
			});
		}
		var value = $(this).val().toLowerCase();
		$("#sidebar li.left-search").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
		$("#sidebar .menu-left").filter(function() {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
	
	loadFavorites('view');
});

function loadFavorites(mode = 'view', tab = 'info') {
	$.get("ajax/favorites/load_favorites.php?mode=" + mode + "&tab=" + tab, function(data) {
		const containerId = tab + '_favorites_' + mode + '_content';
		$("#" + containerId).html(data);
		
		if (mode === 'edit') {
			initializeSortable(tab);
		}
	}).fail(function() {
		console.error('Failed to load favorites for tab: ' + tab);
	});
}

function addToFavorites(linkId, tab = 'info') {
	$.post("ajax/favorites/add_favorite.php", {link_id: linkId, tab: tab}, function(response) {
		if (response.success) {
			loadFavorites('view', tab);
			loadFavorites('edit', tab);
			
			showToast('Added to favorites', response.link_name + ' added to favorites', 'success');
		}
	}, 'json').fail(function(xhr) {
		let error = 'Failed to add favorite';
		try {
			const response = JSON.parse(xhr.responseText);
			error = response.error || error;
		} catch(e) {}
		showToast('Error', error, 'error');
	});
}

function removeFromFavorites(linkId, tab = 'info') {
	$.post("ajax/favorites/remove_favorite.php", {link_id: linkId, tab: tab}, function(response) {
		if (response.success) {
			loadFavorites('view', tab);
			loadFavorites('edit', tab);
			
			showToast('Removed from favorites', 'Link removed from favorites', 'success');
		}
	}, 'json').fail(function(xhr) {
		let error = 'Failed to remove favorite';
		try {
			const response = JSON.parse(xhr.responseText);
			error = response.error || error;
		} catch(e) {}
		showToast('Error', error, 'error');
	});
}

function reorderFavorites(newOrder, tab = 'info') {
	$.post("ajax/favorites/reorder_favorites.php", {order: JSON.stringify(newOrder), tab: tab}, function(response) {
		if (response.success) {
			loadFavorites('view', tab);
			showToast('Reordered', 'Favorites reordered successfully', 'success');
		}
	}, 'json').fail(function(xhr) {
		let error = 'Failed to reorder favorites';
		try {
			const response = JSON.parse(xhr.responseText);
			error = response.error || error;
		} catch(e) {}
		showToast('Error', error, 'error');
	});
}

function initializeSortable(tab = 'info') {
	const editContainer = document.getElementById(tab + '_favorites_edit_content');
	if (editContainer) {
		new Sortable(editContainer, {
			animation: 150,
			handle: '.grip-handle',
			filter: '.available-link-item',
			onEnd: function(evt) {
				const favoriteItems = editContainer.querySelectorAll('.favorite-edit-item');
				const newOrder = Array.from(favoriteItems).map(item => item.dataset.linkId);
				reorderFavorites(newOrder, tab);
			}
		});
	}
}

function editFavInfo(tab = 'info'){
	$("#" + tab + "-favorites-view-mode").fadeOut("fast", function () {
		$("#" + tab + "-favorites-view-mode").removeClass("active show");
		loadFavorites('edit', tab);
	  	$("#" + tab + "-favorites-edit-mode").fadeIn('fast', function () {
	     	$("#" + tab + "-favorites-edit-mode").addClass("active show");
		});
	});
}

function closeEditFavInfo(tab = 'info'){
	$("#" + tab + "-favorites-edit-mode").fadeOut("fast", function () {
		$("#" + tab + "-favorites-edit-mode").removeClass("active show");
	 	$("#" + tab + "-favorites-view-mode").fadeIn('fast', function () {
	     	$("#" + tab + "-favorites-view-mode").addClass("active show");
		});
	});
}

function showToast(title, message, type = 'info') {
	if (typeof Swal !== 'undefined') {
		Swal.fire({
			title: title,
			text: message,
			icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
			timer: 2000,
			showConfirmButton: false,
			toast: true,
			position: 'top-end'
		});
	}
}

$(document).on('click', '.add-favorite', function() {
    const linkId = $(this).data('link-id');
 	let tab = 'info';
    if ($(this).closest('#admin-favorites-edit-mode, #admin_favorites_edit_content').length > 0) {
        tab = 'admin';
    } else if ($(this).closest('#blog-favorites-edit-mode, #blog_favorites_edit_content').length > 0) {
        tab = 'blog';
    }
	addToFavorites(linkId, tab);
});

$(document).on('click', '.remove-favorite', function() {
    const linkId = $(this).data('link-id');
 	let tab = 'info'; // default
    if ($(this).closest('#admin-favorites-edit-mode, #admin_favorites_edit_content').length > 0) {
        tab = 'admin';
    } else if ($(this).closest('#blog-favorites-edit-mode, #blog_favorites_edit_content').length > 0) {
        tab = 'blog';
    }
  	removeFromFavorites(linkId, tab);
});

$(document).ready(function(){
	loadFavorites('view');
	loadFavorites('view', 'info');
    
 	if (document.getElementById('admin-tab')) {
        loadFavorites('view', 'admin');
    }
    
    if (document.getElementById('blog-admin-tab')) {
        loadFavorites('view', 'blog');
    }
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
	var collapseEl = document.getElementById('collapseInfoFav');
	var caret = document.querySelector('.favorites-label i');
	
	if (collapseEl && caret) {
		collapseEl.addEventListener('shown.bs.collapse', function () {
			caret.classList.remove('fa-solid', 'fa-folder-plus');
			caret.classList.add('fa-solid', 'fa-folder-minus');
		});
		collapseEl.addEventListener('hidden.bs.collapse', function () {
			caret.classList.remove('fa-solid', 'fa-folder-minus');
			caret.classList.add('fa-solid', 'fa-folder-plus');
		});
	}
  
	var collapseAdminEl = document.getElementById('collapseAdminFav');
	var adminCaret = document.querySelector('[href="#collapseAdminFav"] i');
	
	if (collapseAdminEl && adminCaret) {
		collapseAdminEl.addEventListener('shown.bs.collapse', function () {
			adminCaret.classList.remove('fa-solid', 'fa-folder-plus');
			adminCaret.classList.add('fa-solid', 'fa-folder-minus');
		});
		collapseAdminEl.addEventListener('hidden.bs.collapse', function () {
			adminCaret.classList.remove('fa-solid', 'fa-folder-minus');
			adminCaret.classList.add('fa-solid', 'fa-folder-plus');
		});
	}
	
	var collapseBlogEl = document.getElementById('collapseBlogFav');
	var blogCaret = document.querySelector('[href="#collapseBlogFav"] i');
	
	if (collapseBlogEl && blogCaret) {
		collapseBlogEl.addEventListener('shown.bs.collapse', function () {
			blogCaret.classList.remove('fa-solid', 'fa-folder-plus');
			blogCaret.classList.add('fa-solid', 'fa-folder-minus');
		});
		collapseBlogEl.addEventListener('hidden.bs.collapse', function () {
			blogCaret.classList.remove('fa-solid', 'fa-folder-minus');
			blogCaret.classList.add('fa-solid', 'fa-folder-plus');
		});
	}
});
</script>

<style>
.favorites-label {
	cursor: pointer;
	transition: all 0.3s ease;
}

.favorites-label:hover {
	color: var(--bs-cyan) !important;
}

.favorites-label:hover i {
	color: var(--bs-cyan) !important;
}

.favorites-edit {
	cursor: pointer;
	transition: all 0.3s ease;
}

.favorites-edit:hover {
	color: var(--bs-cyan) !important;
}

.favorites-done {
	cursor: pointer;
	transition: all 0.3s ease;
}

.favorites-done:hover {
	color: var(--bs-cyan) !important;
}

.left-sidebar-nav-link i {
	background-color: transparent !important;
	font-size: 1rem !important;
}

.fa-times {
	cursor: pointer;
}

.nav-link:focus {
	outline: none;
}

.favorite-edit-item {
	cursor: move;
}

.grip-handle {
	cursor: grab;
}

.grip-handle:active {
	cursor: grabbing;
}

.remove-favorite,
.add-favorite {
	cursor: pointer;
	transition: all 0.3s ease;
}

.remove-favorite:hover {
	transform: scale(1.1);
}

.add-favorite:hover {
	transform: scale(1.1);
}

.sortable-ghost {
	opacity: 0.4;
}

.favorites-empty {
	text-align: center;
	padding: 20px;
	color: #6c757d;
	font-style: italic;
}

.user-name {
    font-size: 0.875rem;
    color: #212529;
    line-height: 1.2;
}

.available-link-item {
	cursor: default;
}

.left-sidebar-nav-link i {
	background-color: transparent !important;
	font-size: 1rem !important;
}

.nav-link:focus {
	outline: none;
}
</style>

<nav id="sidebar" class="sidebar-wrapper" style="padding-top:57px;">
	<div class="sidebar-content">
		<div class="sidebar-item sidebar-brand" id="left-version-header" style="<?php if ($version_view_toggle == 1) { echo "display:none"; } else { echo ""; }?>">
			<?php echo adminPortal(); ?>
			<?php 
				if (checkRole('version_features')) {
			    	if (checkRole('version_view')) {
			        	echo '<span class="float-end fw-bold code-pin">
			                  	<a href="versions.php"><i class="fas fa-code-branch" aria-hidden="true"></i> v' . $version .'</a>
			              	</span>';
			    	} else {
			        	echo '<span class="float-end fw-bold code-pin">
			                  	<a href="features.php"><i class="fas fa-code-branch" aria-hidden="true"></i> v' . $version .'</a>
			              	</span>';
			    	}
				} else {
			    	echo '<span class="float-end fw-bold code-pin">
			              	<a href="javascript:void(0);"><i class="fas fa-code-branch" aria-hidden="true"></i> v' . $version . '</a>
			 	   		</span>';
				}
			?>
		</div>
		<div class="sidebar-item sidebar-header d-print-flex flex-nowrap" id="left-sidebar-header" style="<?php if ($profile_view_toggle == 1) { echo "display:none"; } else { echo ""; }?>">
			<div class="user-pic" data-bs-toggle="collapse" href="#quick-links-header" role="button" aria-expanded="false" aria-controls="quick-links-header">
			<?php 
				if ($profile_icon_toggle == 1) { 
					echo '<span id="profile-image-sidebar" style="display:none;">';
						  	generateUserProfilePic(); 
					echo '</span>';
					echo '<div class="d-flex justify-content-center"><span class="" id="profile-icon-sidebar" style="">';
							generateUserIcon();
					echo '</span></div>';
				} else { 
					echo '<span id="profile-image-sidebar" style="">';
							generateUserProfilePic(); 
					echo '</span>';
					echo '<div class="d-flex justify-content-center"><span class="" id="profile-icon-sidebar" style="display:none;">';
							generateUserIcon();
					echo '</span></div>';
				}
			?>
			</div>
			<div class="user-info text-truncate">
				<a href="profile.php" class="texdt-decoration-none user-name"><?php echo $_SESSION['display_name']; ?></a>
				<span class="user-role">
				<?php 
					if(isset($_SESSION['temp_session_token']) && $_SESSION['temp_session_token'] !== NULL){
							
						echo '<a href="https://switchboardapp.net/dashboard/actions/stop_impersonate.php?impersonate='.$_SESSION['unique_id'].'" id="impersonateDrop" class="mr-1" aria-expanded="true">
								<i class="fas fa-user-secret"></i>
							   </a>';
					} else {}
				?>
				<span class="user-status"><i class="fa fa-circle"></i></span><?php generateUserRoleTwo();?></span>
				<span class="user-phone-code">
				<?php
				$user_id = $_SESSION['id'];
				$query = "SELECT phone_code, phone_code_sup FROM users WHERE id = ?";
				$stmt = mysqli_prepare($dbc, $query);
				mysqli_stmt_bind_param($stmt, "i", $user_id);
				mysqli_stmt_execute($stmt);
				mysqli_stmt_bind_result($stmt, $user_phone_code, $sup_phone_code);
				mysqli_stmt_fetch($stmt);
				mysqli_stmt_close($stmt);
				$user_phone_code = mysqli_real_escape_string($dbc, strip_tags($user_phone_code));
				$sup_phone_code = mysqli_real_escape_string($dbc, strip_tags($sup_phone_code));
				if ($user_phone_code != '0') {
				    echo '<i class="fa-brands fa-galactic-republic me-1"></i><span class="me-1">'.$user_phone_code.'</span>';
				} 
				if ($sup_phone_code != '0') {
				    echo '<i class="fa-brands fa-galactic-senate me-1"></i>'.$sup_phone_code.'';
				}
				?>
			</div>
		</div>

		<div class="sidebar-item collapse" id="quick-links-header" style="<?php if ($quick_links_toggle == 1) { echo "display:none"; } else { echo ""; }?>">
			<div class="sidebar-search text-center">
				<div class="btn-group d-flex" role="group">
				    <a href="profile.php" class="btn btn-dark w-100" data-bs-toggle="tooltip" title="" data-original-title="Profile">
				        <i class="fa-regular fa-user"></i>
				        <span id="gems-quick-notification" style="<?php if($toggle_notify_gems == 0){echo "display:none";} else {echo "";}?>">
				            <span class="" id="display_diamond" style="<?php if ($gem_display_toggle == 0) { echo "display:none"; } else { echo ""; }?>">
				                <?php if (displayNewGem() > 0){ echo displayNewGem(); } else {} ?>
				            </span>
				        </span>
				    </a>

					<a href="kudos.php" class="btn btn-dark btn-sidebar-link w-100">
					    <i class="fa-solid fa-trophy"></i>
					    <span id="kudos-left-notification" style="<?php if($toggle_notify_kudos == 0){echo "display:none";} else {echo "";}?>">
					        <?php if (countUserKudos() >= 1): ?>
					            <span class="badge badge_quick_gold kudos_count">
					                <?php echo countUserKudos(); ?>
					            </span>
					        <?php endif; ?>
					    </span>
					</a>

					<?php
					    if(checkRole('feedback_admin')){
					        echo '<a href="feedback_admin.php" class="btn btn-dark btn-sidebar-link w-100" data-bs-toggle="tooltip" title="" data-original-title="Submit Feedback">
					            <i class="fa-regular fa-circle-dot"></i>
					            <span id="feedback-quick-notification" style="'; if($toggle_notify_feedback == 0){echo "display:none";} else {echo "";} echo'">';
					            if (countFeedbackOpen() > 0){
					                echo '<span class="badge badge_quick_robin count-open-feedback">' . countFeedbackOpen() . '</span>';
					            }
					            echo '</span></a>';
					    } else {
					        echo '<a href="feedback.php" class="btn btn-dark btn-sidebar-link w-100" data-bs-toggle="tooltip" title="" data-original-title="Submit Feedback">
					                <i class="fa-regular fa-circle-dot"></i>
					            </a>';
					    }
					?>

					<?php
					    if(checkRole('user_password')) {
					        echo '<a href="password_resets.php" class="btn btn-dark btn-sidebar-link w-100" data-bs-toggle="tooltip" title="" data-original-title="Password Resets">
					            <i class="fa-solid fa-unlock"></i>
					            <span id="pass-reset-quick-notification" style="'; if($toggle_notify_pass_resets == 0){echo "display:none";} else {echo "";} echo'">';
					            if (countPasswordResets() > 0){
					                echo '<span class="badge badge_quick_plum reset_count reset_count_badge">' . countPasswordResets() . '</span>';
					            }
					        echo '</span></a>';
					    }
					?>

					<?php
					    if(checkRole('messages_view')) {	
					        echo '<a href="messages.php" class="btn btn-dark btn-sidebar-link w-100" data-bs-toggle="tooltip" title="" data-original-title="Messages">
					                <i class="fa-solid fa-envelope-open-text"></i>
					                <span id="messages-quick-notification" style="'; 
					        if($toggle_notify_messages == 0){ 
					            echo 'display:none'; 
					        } 
					        echo '">';

					        if (countUnreadMessages() > 0){
					            echo '<span class="badge badge_quick_fresh message_count">' . countUnreadMessages() . '</span>';
					        }
					        echo '</span></a>';
					    }
					?>
				</div>
			</div>
		</div>

   	 	<div class="sidebar-item sidebar-search" id="left-sidebar-search-header" style="<?php if ($left_sidebar_search_toggle == 1) { echo "display:none"; } else { echo ""; }?>">
        	<div class="input-group">
				<input type="text" class="form-control search-menu leftnav" id="sidebar-search" placeholder="Search...">
				<span class="input-group-text sidebar-item-left">
         		   <i class="fa fa-search" aria-hidden="true"></i>
      		 	</span>
			</div>
		</div>

		<div class="sidebar-item sidebar-menu">

			<?php 
			if(checkRole('system_admin_tab') || checkRole('blog_tab') || checkRole('my_learning')){
				echo '<div class="container" style="margin-top:15px;">
					<ul class="nav nav-tabs nav-justified" id="left-sidebar-tabs" style="margin-left:5px;margin-right:5px;">
						<li class="nav-item">
							<a class="nav-link left left-sidebar-nav-link p-0 active" data-bs-toggle="tab" href="#info-tab">
								<i class="fas fa-info-circle mx-auto"></i>
							</a>
						</li>';

						if(checkRole('system_admin_tab')){
						echo '<li class="nav-item">
							<a class="nav-link left left-sidebar-nav-link p-0" data-bs-toggle="tab" href="#admin-tab">
								<i class="fas fa-user-cog mx-auto"></i>
							</a>
						</li>';} else {}
							
						if(checkRole('blog_tab')){
						echo '<li class="nav-item">
							<a class="nav-link left left-sidebar-nav-link p-0 " data-bs-toggle="tab" href="#blog-admin-tab">
								<i class="fas fa-leaf mx-auto"></i>
							</a>
						</li>';} else {}
								
						if(checkRole('my_learning')){
						echo '<li class="nav-item">
							<a class="nav-link left left-sidebar-nav-link p-0" data-bs-toggle="tab" href="#my-learning-tab">
								<i class="fa-solid fa-chalkboard-user mx-auto"></i>
							</a>
						</li>'; } else {}
									
				echo '</ul>
					</div>';
			} else {} 
			?>

			<div class="tab-content">
				<div id="info-tab" class="tab-pane active">
					<ul>
						<div id="info-favorites-view-mode" class="active show">
							<li class="header-menu menu-left">
								<span class="favorites-label" data-bs-toggle="collapse" href="#collapseInfoFav" role="button" aria-expanded="false" aria-controls="collapseInfoFav">
									<i class="fa-solid fa-folder-plus"></i> Favorites
								</span>
								<span class="favorites-edit float-end" role="button" onclick="editFavInfo('info');">
									Edit
								</span>
							</li>
							<div class="collapse" id="collapseInfoFav">
								<div id="info_favorites_view_content"></div>
							</div>
						</div>
						<div id="info-favorites-edit-mode" style="display: none;">
							<li class="header-menu menu-left">
								<span class="favorites-label"><i class="fa-solid fa-folder-minus"></i> Favorites</span>
								<span class="favorites-done float-end" role="button" onclick="closeEditFavInfo('info');">
									Done
								</span>
							</li>
							<div id="info_favorites_edit_content"></div>
							<li class="left-search" style="padding: 10px 20px; text-align: center; font-size: 12px; color: #6c757d;">
								<i class="fa-solid fa-arrows-alt me-1"></i>
									Drag favorites to reorder <br>Click star to add <br> Click trash to remove
							</li>
						</div>
						
						<?php 
						if(checkRole('switchboard_view')){
							echo '<li class="header-menu menu-left">
									<span>Switchboards</span>
								 </li>
								 <li class="sidebar-dropdown left-search">
									<a href="javascript:void(0);">
										<i class="far fa-address-book"></i>
										<span>Address Book</span>
									</a>
									<div class="sidebar-submenu">
										<div class="switchboard_cat_links"></div>
									</div>';
									
							if(checkRole('switchboard_categories')){
								echo '<li class="left-search">
									<a href="switchboard_categories.php">
										<i class="fas fa-folder-plus"></i>
										<span> Categories</span>
									</a>
								  </li>';
							} else {}

						} else {} 
						?>
					
					<li class="header-menu menu-left">
						<span>Timesown</span>
					</li>
					
					<li class="left-search">
						<a href="timesown.php">
							<i class="fa-solid fa-clock-rotate-left"></i>
							<span>My Schedule</span>
						</a>
					</li>
					
	<li id="lh-nav-start" style="display:none !important;"></li>
	<?php 
	if(checkRole('lighthouse_harbor')){
		echo '<li class="header-menu menu-left">
			<span>Lighthouse</span>
		</li>
		<li class="sidebar-dropdown left-search">
			<a href="javascript:void(0);">
				<i class="fa-solid fa-compass"></i>
				<span>Safe Harbor</span>
			</a>
			<div class="sidebar-submenu">
				<ul>
					<li>
						<a href="lighthouse_harbor.php"> My Signals
							<span class="badge rounded-pill bg-secondary ms-auto" id="count-my-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_harbor.php?filter=closed"> My Closed Signals
							<span class="badge rounded-pill bg-secondary ms-auto" id="count-my-closed-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
				</ul>
			</div>
		</li>';
	} else {} 
	?>
	 
	 <?php if(checkRole('lighthouse_keeper')){ 
			$docks_query = "SELECT dock_id, dock_name, dock_icon, dock_color FROM lh_docks WHERE is_active = 1 ORDER BY dock_order ASC";
			$docks_result = mysqli_query($dbc, $docks_query);
			$docks = [];
			if ($docks_result) {
				while ($dock = mysqli_fetch_assoc($docks_result)) {
					$docks[] = $dock;
				}
			}
			
			$sea_states_query = "SELECT sea_state_id, sea_state_name, sea_state_color FROM lh_sea_states WHERE is_active = 1 ORDER BY sea_state_order ASC";
			$sea_states_result = mysqli_query($dbc, $sea_states_query);
			$sea_states = [];
			if ($sea_states_result) {
				while ($state = mysqli_fetch_assoc($sea_states_result)) {
					$sea_states[] = $state;
				}
			}
		?>
		
		<li class="header-menu menu-left">
			<span>Keeper's Watch</span>
		</li>
		
		<li class="sidebar-dropdown left-search">
			<a href="javascript:void(0);">
				<i class="fa-solid fa-list"></i>
				<span>Quick Access</span>
			</a>
			<div class="sidebar-submenu">
				<ul>
					<li>
						<a href="lighthouse_keeper.php">
							<span>View All Signals</span>
							<span class="badge rounded-pill bg-secondary ms-auto" id="quick-all-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_keeper.php?filter=assigned">
							<span>My Assigned Signals</span>
							<span class="badge rounded-pill bg-secondary ms-auto" id="quick-assigned-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_keeper.php?filter=unassigned">
							<span>Unassigned Queue</span>
							<span class="badge rounded-pill bg-secondary ms-auto" id="quick-unassigned-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_keeper.php?filter=closed">
							<span>View All Closed</span>
							<span class="badge rounded-pill bg-secondary ms-auto" id="quick-closed-signals" style="font-size: 10px;">0</span>
						</a>
					</li>
				</ul>
			</div>
		</li>
		
		<?php foreach ($docks as $dock): ?>
		<li class="sidebar-dropdown left-search">
			<a href="javascript:void(0);">
				<i class="fa-solid <?php echo htmlspecialchars($dock['dock_icon']); ?>"></i>
				<span><?php echo htmlspecialchars($dock['dock_name']); ?></span>
				<span class="badge rounded-pill bg-secondary ms-auto" id="dock-count-<?php echo $dock['dock_id']; ?>" style="font-size: 10px;">0</span>
			</a>
			<div class="sidebar-submenu">
				<ul>
					<li class="submenu-header">
						<span style="font-size: 11px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Sea States</span>
					</li>
					<li>
						<a href="lighthouse_keeper.php?dock=<?php echo $dock['dock_id']; ?>">
							<span>View All</span>
							<span class="badge rounded-pill bg-primary text-light ms-auto" id="dock-viewall-count-<?php echo $dock['dock_id']; ?>" style="font-size: 10px;">0</span>
						</a>
					</li>
					<?php foreach ($sea_states as $state): ?>
					<li>
						<a href="lighthouse_keeper.php?dock=<?php echo $dock['dock_id']; ?>&state=<?php echo $state['sea_state_id']; ?>">
							
							<span><?php echo htmlspecialchars($state['sea_state_name']); ?></span>
							<span class="badge rounded-pill bg-secondary text-light ms-auto" id="dock-state-count-<?php echo $dock['dock_id']; ?>-<?php echo $state['sea_state_id']; ?>" style="font-size: 9px;">0</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</li>
		<?php endforeach; ?>
		
		<?php } ?>
		
		<?php 
		if(checkRole('lighthouse_maritime')){
			echo '<li class="header-menu menu-left">
			<span>Maritime</span>
		</li>
		<li class="sidebar-dropdown left-search">
			<a href="javascript:void(0);">
				<i class="fa-solid fa-anchor"></i>
				<span>Mangement</span>
			</a>
			<div class="sidebar-submenu">
				<ul>
					<li>
						<a href="lighthouse_maritime_docks.php">
							<span>The Docks</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_maritime_status.php">
							<span>Sea States</span>
						</a>
					</li>
					<li>
						<a href="lighthouse_maritime_priority.php">
							<span>Priority Levels</span>
						</a>
					</li>';
					
					
						
						if(checkRole('lighthouse_services')){
							echo '<li>
								<a href="lighthouse_maritime_services.php">
									<span>Harbor Services</span>
								</a>
								</li>';
						} else {}	
							if(checkRole('lighthouse_captain')){
								echo '<li>
									<a href="lighthouse_maritime_captain.php">
										<span>Captains Log</span>
									</a>
									</li>';
							} else {}
						 
				echo '</ul>
			</div>
		</li>';
		} else {} 
		?>
		<li id="lh-nav-end" style="display:none !important;"></li>
		
		<li class="header-menu menu-left">
						<span>Applications</span>
					</li>
					<li class="sidebar-dropdown left-search">
						<a href="javascript:void(0);">
							<i class="fab fa-sketch"></i>
							<span>Applications</span>
							<span class="badge rounded-pill bg-danger ms-2"></span>
						</a>
						<div class="sidebar-submenu">
							<ul>
								<li>
									<a href="services.php"><span>Service Groups</span></a>
								</li>
								<li>
									<a href="who_dash.php"><span>5W1H</span></a>
								</li>
								<li>
									<a href="verse.php"><span>Verse</span></a>
								</li>
								<li>
									<a href="my_links.php"><span>My Links</span></a>
								</li>
								<li>
									<a href="luncheon.php"><span>Luncheon</span></a>
								</li>
								
								<?php if(checkRole('luckio_user')){ echo '<li> <a href="luckio.php"><span>Luckio</span></a> </li>'; } else {} ?>
								
								<?php if(checkRole('athena_view')){ echo '<li> <a href="athena.php"><span>Athena</span></a> </li>'; } else {} ?>
								
								<?php
									if(checkRole('poll_view')){
										echo '<li>
												<a href="poll_dashboard.php">
													<span>Polls</span>
													<span class="badge bg-fresh rounded-pill poll_count"></span>
												</a>
										  	  </li>';
									} else {}
								?>
								
							</ul>
						</div>
					</li>
					
					<li class="header-menu menu-left">
						<span>Programs</span>
					</li>
					<li class="sidebar-dropdown left-search">
						<a href="javascript:void(0);">
							<i class="fa-solid fa-bars-progress"></i>
							<span>Programs</span>
							<span class="badge rounded-pill bg-danger ms-2"></span>
						</a>
						<div class="sidebar-submenu">
							<ul>
								<?php if(checkRole('app_cypher')){ echo '<li> <a href="programs_cypher.php"><span>Cypher</span></a> </li>'; } else {} ?>
								
								<?php if(checkRole('app_cypher')){ echo '<li> <a href="programs_genlink_pro.php"><span>Genlink Pro</span></a> </li>'; } else {} ?>
								<li>
									<a href="programs_oui.php"><span>OUI Lookup</span></a>
								</li>
								<li>
									<a href="programs_quick_response.php"><span>QR Generator</span></a>
								</li>
								<li>
									<a href="programs_pass_gen.php"><span>Pass Generator</span></a>
								</li>
								<li>
									<a href="programs_unicoder.php"><span>Unicoder</span></a>
								</li>
								<li>
									<a href="https://switchboardapp.net/dashboard/apps/html_viewer/" target="_blank"><span>HTML Viewer</span></a>
								</li>
								<li>
									<a href="https://switchboardapp.net/dashboard/apps/html_quotes/" target="_blank"><span>HTML Quotes</span></a>
								</li>
								<li>
									<a href="programs_nato.php"><span>NATO Alphabet</span></a>
								</li>
								<li>
									<a href="programs_world_flags.php"><span>World Flags</span></a>
								</li>
								<li>
									<a href="programs_lumonator.php"><span>Lumonator</span></a>
								</li>
							</ul>
						</div>
					</li>
					
					<li class="header-menu menu-left">
						<span>Arcade</span>
					</li>
					<li class="sidebar-dropdown left-search">
						<a href="javascript:void(0);">
							<i class="bi bi-joystick"></i>
							<span>Arcade</span>
							<span class="badge rounded-pill bg-danger ms-2"></span>
						</a>
						<div class="sidebar-submenu">
							<ul>
								<li>
									<a href="https://switchboardapp.net/dashboard/arcade_hexalign.php"><span>Hexalign</span></a>
								</li>
								<li>
									<a href="https://switchboardapp.net/dashboard/arcade_xoexpress.php"><span>XO Express</span></a>
								</li>
								<li>
									<a href="https://switchboardapp.net/dashboard/arcade_knights_tour.php"><span>Knight's Tour</span></a>
								</li>
							</ul>
						</div>
					</li>
					
					<li class="header-menu menu-left">
						<span>Resources</span>
					</li>
					
					<li class="left-search">
						<a href="on_point.php">
							<i class="fas fa-calendar-alt"></i>
							<span>On-Point Schedule</span>
						</a>
					</li>
					<li class="header-menu menu-left">
						<span>User Information</span>
					</li>
					<li class="sidebar-dropdown left-search">
						<a href="javascript:void(0);">
							<i class="fas fa-user-astronaut"></i>
							<span>My Switchboard</span>
							<span class="badge rounded-pill bg-danger ms-2"></span>
						</a>
						<div class="sidebar-submenu">
							<ul>
								<li> 
									<a href="kudos.php">My Kudos
										<span class="badge rounded-pill bg-warning dark-gray kudos_count" id="kudos-drop-notification" style="<?php if($toggle_notify_kudos == 0){echo "display:none";} else {echo "";}?>"></span>
									</a>
								</li>
								<li>
									<a href="profile.php#nav-badge">My Badges
										<span class="badge rounded-pill bg-pink user_badge_count" id="badges-drop-notification" style="<?php if($toggle_notify_badges == 0){echo "display:none";} else {echo "";}?>"></span>
		   							</a>
								</li>
								<li> 
									<a href="profile.php">My Profile</a>
								</li>
							</ul>
						</div>
					</li>
					
					<?php
              			if(checkRole('messages_view')) {	
							echo '<li class="left-search">
									<a href="messages.php">
										<i class="far fa-envelope"></i>
										<span>Messages</span>
										
										<span id="messages-left-notification" style="margin-left: auto !important; '; if($toggle_notify_messages == 0){echo "display:none";} else {echo "";} echo '">
											<span class="badge rounded-pill bg-fresh message_count"></span>
										</span>
									</a>
								  </li>';
						} else {}
					?>
					
					<li class="sidebar-dropdown left-search">
						<a href="javascript:void(0);">
							<i class="fa fa-tachometer-alt"></i>
							<span>Dashboards</span>
						</a>
						<div class="sidebar-submenu">
							<ul>
								
								<?php
			              		if(checkRole('system_stats')) {	
									echo '<li>
										<a href="dashboard.php">Stats Dashboard</a>
									</li>';
								} else {}
								?>
								
								<li><a href="kudos.php#leaderboard/">Kudos Board</a></li>
								<li><a href="badge_board.php">Badge Board</a></li>
								<li><a href="gem_board.php">Gem Board</a></li>
								
								<?php
			              		if(checkRole('user_role')) {	
									echo '<li>
										<a href="user_view.php">User Reports</a>
									</li>';
								} else {}
								?>
								
								<?php
			              	  	if(checkRole('read_sxp')) {	
									echo '<li>
										<a href="sxp_user.php">SXP User</a>
									</li>';
								} else {}
								?>
								
								<?php
			           		 	if(checkRole('admin_sxp')) {	
									echo '<li>
										<a href="sxp_admin.php">SXP Admin</a>
									</li>';
								} else {}
								?>
								
							</ul>
						</div>
					</li>
				</ul>
			</div>

			<?php
			
				if (checkRole('system_admin_tab')){
					echo '<div id="admin-tab" class="tab-pane">
						<ul>';
				}
				
				if (checkRole('user_manage') || checkRole('user_password') || checkRole('user_role')) {
				    echo ' <div id="admin-favorites-view-mode" class="active show">
				        <li class="header-menu menu-left">
				          <span class="favorites-label" data-bs-toggle="collapse" href="#collapseAdminFav" role="button" aria-expanded="false" aria-controls="collapseAdminFav">
				            <i class="fa-solid fa-folder-plus"></i> Favorites
				          </span>
				          <span class="favorites-edit float-end" role="button" onclick="editFavInfo(\'admin\');">
				            Edit
				          </span>
				        </li>
				        <div class="collapse" id="collapseAdminFav">
				            <div id="admin_favorites_view_content">
				            </div>
				        </div>
				    </div>
				    <div id="admin-favorites-edit-mode" style="display: none;">	
				        <li class="header-menu menu-left">
				            <span class="favorites-label"><i class="fa-solid fa-folder-minus"></i> Favorites</span>
				            <span class="favorites-done float-end" role="button" onclick="closeEditFavInfo(\'admin\');">
				                Done
				            </span>
				        </li>
				        <div id="admin_favorites_edit_content"></div>
				        <li class="left-search" style="padding: 10px 20px; text-align: center; font-size: 12px; color: #6c757d;">
				            <i class="fa-solid fa-arrows-alt me-1"></i>
				            Drag favorites to reorder <br> Click star to add <br> Click trash to remove
				        </li>
				    </div>';
				}
				
				
				if (checkRole('user_manage') || checkRole('user_password') || checkRole('user_role')) {
				    echo '<li class="header-menu menu-left">
				            <span>User Admin</span>
				          </li>
				         <li class="sidebar-dropdown left-search" id="">
				            <a href="javascript:void(0);">
				                <i class="fas fa-user-shield"></i>
				                <span>Accounts</span>
				            </a>
				            <div class="sidebar-submenu" id="">
				                <ul>';

				    if (checkRole('user_manage')) {
						echo '<li class="left-search">
				                <a href="admin_dashboard.php">Manage Users</a>
				              </li>';
					}
					
				    if (checkRole('user_role')) {
				        echo '<li class="left-search">
				                <a href="roles.php">Manage Roles</a>
				              </li>';
				    }
					
					if (checkRole('user_manage')) {
						echo '<li class="left-search">
	  				            <a href="admin_filter.php">Filter Users</a>
	  					      </li>';
				        echo '<li class="left-search">
				                <a href="display_titles.php">Display Titles</a>
				              </li>';
				        echo '<li class="left-search">
				                <a href="display_agency.php">Display Agency</a>
				              </li>';
				    }

				    if (checkRole('user_password')) {
				        echo '<li class="left-search">
				                <a href="password_resets.php">
				                    Password Resets
				                    <span id="pass-reset-left-notification" style="margin-left: auto !important; display: inline-block; '; 
				        if ($toggle_notify_pass_resets == 0) {
				            echo "display:none";
				        } 
				        echo '">';
				        if (countPasswordResets() > 0) {
				            echo '<span class="badge rounded-pill bg-plum reset_count reset_count_badge">'; 
				            echo countPasswordResets(); 
				            echo '</span>';
				        }
				        echo '</span></a></li>';
				    }

				    echo '</ul>
				        </div>
				      </li>';
				}
						
				if(checkRole('system_alerts') || checkRole('badge_admin') || checkRole('system_links_admin') ){
					echo '<li class="header-menu menu-left">
							<span>Admin Dashboards</span>
						  </li>';

						  if (checkRole('system_alerts')) {
						      echo '<li class="sidebar-dropdown left-search" id="knowledgebase">
						          <a href="javascript:void(0);">
						              <i class="fa-solid fa-bell"></i>
						              <span>Alerts Admin</span>
						          </a>
						          <div class="sidebar-submenu">
						              <ul>
						                  <li>
						                      <a href="alerts_admin.php">Manage</a>
						                  </li>
						                  <li>
						                      <a href="alerts_reports.php">Reports</a>
						                  </li>
						              </ul>
						          </div>
						      </li>';
						  } else {}
							  
						  if (checkRole('info_admin')) {
							      echo '<li class="sidebar-dropdown left-search" id="knowledgebase">
							          <a href="javascript:void(0);">
							              <i class="fa-solid fa-circle-info"></i>
							              <span>Info Admin</span>
							          </a>
							          <div class="sidebar-submenu">
							              <ul>
							                  <li>
							                      <a href="info_admin.php">Manage</a>
							                  </li>
							                  <li>
							                      <a href="info_reports.php">Reports</a>
							                  </li>
										  </ul>
							          </div>
							      </li>';
						  } else {}
							  
						  if (checkRole('future_admin')) {
								      echo '<li class="sidebar-dropdown left-search" id="knowledgebase">
								          <a href="javascript:void(0);">
								              <i class="bi bi-rocket-fill"></i>
								              <span>Future Admin</span>
								          </a>
								          <div class="sidebar-submenu">
								              <ul>
								                  <li>
								                      <a href="future_admin.php">Manage</a>
								                  </li>
												  <!--
								                  <li>
								                      <a href="future_reports.php">Reports</a>
								                  </li>
												  -->
											  </ul>
								          </div>
								      </li>';
							  } else {}
							  
				if(checkRole('badge_admin')){
					echo '<li class="left-search">
							<a href="badge_admin.php">
							 	<i class="fa-solid fa-award"></i>
								<span>Badge Admin</span>
							</a>
					     </li>'; 
				} else {}
					
				if(checkRole('cert_admin')){
					echo '<li class="left-search">
							<a href="cert_admin.php">
								<i class="fa-solid fa-certificate"></i>
								<span>Cert Admin</span>
							</a>
						 </li>'; 
				} else {}	
				
				if(checkRole('admin_developer')){
					echo '<li class="left-search">
							<a href="ping.php">
								<i class="fab fa-pushed rotate"></i>
								<span>Pingcast</span>
							</a>
						  </li>';
				} else {}

				if(checkRole('version_view')){
					echo '<li class="left-search">
							<a href="versions.php">
								<i class="fas fa-code-branch"></i>
								<span>Versions</span>
							</a>
						 </li>';
				} else {}
								
			} else {}
				
				if(checkRole('timesown_admin') || checkRole('timesown_tenant')){
					echo '<li class="header-menu menu-left">
							<span>Timesown</span>
						 </li>
						 <li class="sidebar-dropdown left-search" id="knowledgebase">
						 	<a href="javascript:void(0);">
								<i class="fa-solid fa-clock-rotate-left"></i>
								<span>Scheduling</span>
							</a>
							<div class="sidebar-submenu" id="kb_sub">
						<ul>';
							
						if(checkRole('timesown_user')){
							echo '<li>
	 								<a href="timesown.php">My Schedule</a>
	 							</li>';
						} else {}
							
						if(checkRole('timesown_admin')){
							echo '<li>
									<a href="timesown_admin.php">Timesown Admin</a>
								 </li>';
						} else {}
							
						if(checkRole('timesown_tenant')){
							echo '<li>
									<a href="timesown_tenant.php">Timesown Tenant</a>
								 </li>';
						} else {}
							echo '</ul>
							</div>
						  </li>';
				    
						} else {}	

			if(checkRole('system_links_admin')){
					echo '<li class="header-menu menu-left">
							<span>Links</span>
						 </li>
						 <li class="sidebar-dropdown left-search" id="">
						 	<a href="javascript:void(0);">
								<i class="fa-solid fa-link"></i>
								<span>Links Admin</span>
							</a>
							<div class="sidebar-submenu" id="">
						<ul>';
							
						echo '<li>
 								<a href="admin_links.php">HD Links</a>
 							  </li>
							  <li>
							  	<a href="admin_sidebar_links.php">Right Sidebar 
									<span class="badge rounded-pill bg-info" 
									style="margin-left: auto !important; display: inline-block;">
									New</span></a>
								</li>';
							
					    echo '</ul>
							</div>
						  </li>';
				    
						} else {}	
				
			if(checkRole('athena_articles')|| checkRole('athena_priority')|| checkRole('athena_categories')){
				echo '<li class="header-menu menu-left">
						<span>Athena</span>
					 </li>
					 <li class="sidebar-dropdown left-search" id="knowledgebase">
					 	<a href="javascript:void(0);">
							<i class="fas fa-atom"></i>
							<span>Athena Admin</span>
						</a>
						<div class="sidebar-submenu" id="kb_sub">
					<ul>';
							
					if(checkRole('athena_articles')){
						echo '<li>
 								<a href="athena_admin.php">Articles</a>
 							</li>';
					} else {}
							
					if(checkRole('athena_priority')){
						echo '<li>
								<a href="athena_priority.php">Priorities</a>
							 </li>';
					} else {}
							
					if(checkRole('athena_categories')){
						echo '<li>
								<a href="athena_category.php">Categories</a>
							 </li>';
					} else {}
						echo '</ul>
						</div>
					  </li>';
				    
					} else {}
						
					if(checkRole('admin_developer')){	
					echo '<li class="header-menu menu-left">
							<span>Incident Response</span>
						</li>
					
						<li class="left-search">
							<a href="incident_response.php">
								<i class="fa-solid fa-list-check"></i>
								<span>Incident Response</span>
							</a>
						</li>';
						
					} else {}
						
					if(checkRole('system_login_log') || checkRole('feedback_admin')){
						echo '<li class="header-menu menu-left">
								<span>Admin Reports</span>
							 </li>';
									 
					if(checkRole('feedback_admin')){
						echo '<li class="left-search">
								<a href="feedback_admin.php">
								<i class="far fa-dot-circle"></i>
								<span>Feedback Admin</span>
								
								<span id="feedback-left-notification" style="margin-left: auto !important; '; if($toggle_notify_feedback == 0){echo "display:none";} else {echo "";} echo '">';
									
								if (countFeedbackOpen() > 0){
									echo '<span class="badge rounded-pill bg-info count-open-feedback" style="margin-left: auto !important; display: inline-block;">'; echo countFeedbackOpen(); echo '</span>';
								} else {}
									echo '</span></a></li>';
								} else {}
							
						if(checkRole('on_point_admin')){
		 					echo '<li class="left-search">
		 							<a href="on_point_admin.php">
		 							<i class="fas fa-calendar-alt"></i>
		 							<span>On-Point Admin</span>
		 							</a>
		 						  </li>';
		 				} else {}
										
						if(checkRole('system_login_log')){
							echo '<li class="left-search">
									<a href="login_log.php">
										<i class="fas fa-clipboard-list"></i>
										<span>Login Log</span>
									</a>
								  </li>';
						} else {}
							
						if(checkRole('audit_trail_view')){
							echo '<li class="left-search">
									<a href="audit_trail.php">
										<i class="fa-solid fa-file-shield"></i>
										<span>Audit Trail</span>
									</a>
								  </li>'; 
						} else {}
													 
					}

					if(checkRole('system_admin_tab')){
						echo '</div> <!-- End Admin Tab -->';
					} else {}; ?>

					<?php
					
					if(checkRole('blog_tab')){
						echo '<div id="blog-admin-tab" class="tab-pane">
								<ul>
						<div id="blog-favorites-view-mode" class="active show">
						        <li class="header-menu menu-left">
						          <span class="favorites-label" data-bs-toggle="collapse" href="#collapseBlogFav" role="button" aria-expanded="false" aria-controls="collapseBlogFav">
						            <i class="fa-solid fa-folder-plus"></i> Favorites
						          </span>
						          <span class="favorites-edit float-end" role="button" onclick="editFavInfo(\'blog\');">
						            Edit
						          </span>
						        </li>
						        <div class="collapse" id="collapseBlogFav">
						            <div id="blog_favorites_view_content"></div>
						        </div>
						    </div>
						    <div id="blog-favorites-edit-mode" style="display: none;">	
						        <li class="header-menu menu-left">
						            <span class="favorites-label"><i class="fa-solid fa-folder-minus"></i> Favorites</span>
						            <span class="favorites-done float-end" role="button" onclick="closeEditFavInfo(\'blog\');">
						                Done
						            </span>
						        </li>
						        <div id="blog_favorites_edit_content"></div>
						        <li class="left-search" style="padding: 10px 20px; text-align: center; font-size: 12px; color: #6c757d;">
						            <i class="fa-solid fa-arrows-alt me-1"></i>
						            Drag favorites to reorder <br> Click star to add <br> Click trash to remove
						        </li>
						    </div>
							<li class="header-menu menu-left">
								<span>Blog</span>
							</li>';
									
						if(checkRole('blog_tab')){
							echo '<li class="sidebar-dropdown left-search">
									<a href="javascript:void(0);">
									<i class="fa-solid fa-leaf"></i>
									<span>Blog</span>
									<span class="badge rounded-pill bg-danger ms-2"></span>
									</a>
									<div class="sidebar-submenu">
										<ul>
											<li><a href="blog_add_post.php"><span>Add Post</span></a></li>
											<li><a href="view_all_posts.php"><span>View All Posts</span></a></li>
											<li><a href="blog_reader_reports.php"><span>Reader Reports</span></a></li>
											<li><a href="blog_categories.php"><span>Categories</span></a></li>
											<li><a href="blog_comments.php"><span>Comments</span></a></li>';
											
						if(checkRole('blog_gallery')){
							echo '<li><a href="media_gallery.php"><span>Header Gallery</span></a></li>';
						} else {}
											
							echo '</ul>
								</div>
							</li>';
										
						} else {}	
									
						if(checkRole('poll_admin')){
						
							echo '<li class="header-menu menu-left">
									<span>Poll Admin</span>
								</li>
								
								<li class="sidebar-dropdown left-search">
									<a href="javascript:void(0);">
										<i class="fa-solid fa-tower-broadcast"></i>
										<span>Polls</span>
										<span class="badge rounded-pill bg-danger ms-2"></span>
									</a>
									<div class="sidebar-submenu">
										<ul>
											<li><a href="poll_dashboard.php"><span>Active</span></a></li>
											<li><a href="poll_station.php"><span>Create</span></a></li>
											<li><a href="poll_manager.php"><span>Manage</span></a></li>';
											
											if(checkRole('poll_reports')){
												echo '<li>
														<a href="poll_reports.php"><span>Reports</span></a>
													  </li>';
											} else {}
											
								  echo '</ul>
									</div>
								</li>';
										
							} else {}
								
								if(checkRole('spotlight_admin')){ 
						
									echo '<li class="header-menu menu-left">
											<span>Spotlight</span>
										  </li>
								
										  <li class="sidebar-dropdown left-search">
											<a href="javascript:void(0);">
												<i class="fa-solid fa-ranking-star"></i>
												<span>Spotlight</span>
												<span class="badge rounded-pill bg-danger ms-2"></span>
											</a>
											<div class="sidebar-submenu"> 
												<ul>
													<li><a href="spotlight.php"><span>Showcase</span></a></li>
													<li><a href="spotlight_dashboard.php"><span>Dashboard</span></a></li>
												</ul>
											</div>
										</li>'; 
										
									} else {}
								
								if(checkRole('spotlight_admin')){ 
						
									echo '<li class="header-menu menu-left">
											<span>Spotlight Admin</span>
										  </li>
								
										  <li class="sidebar-dropdown left-search">
											<a href="javascript:void(0);">
												<i class="fa-solid fa-ranking-star"></i>
												<span>Spotlight Admin</span>
												<span class="badge rounded-pill bg-danger ms-2"></span>
											</a>
											<div class="sidebar-submenu"> 
												<ul>
													<li><a href="spotlight_station.php"><span>Create</span></a></li>
													<li><a href="spotlight_admin.php"><span>Admin</span></a></li>
													<li><a href="spotlight_reports.php"><span>Reports</span></a></li>
												</ul>
											</div>
										</li>'; 
										
									} else {}
										
							if(checkRole('gem_admin')){ 
								echo '<li class="header-menu menu-left">
											<span>Gem Admin</span>
									  </li>
									  <li class="sidebar-dropdown left-search">
										  <a href="javascript:void(0);">
												<i class="fa-solid fa-gem"></i>
												<span>Gems</span>
												<span class="badge rounded-pill bg-danger ms-2"></span>
											</a>
											<div class="sidebar-submenu"> 
												<ul>
													<li>
														<a href="gem_station.php"><span>Gem Station</span></a>
													</li>
													<li>
														<a href="gem_admin_reports.php"><span>Gem Reports</span></a>
													</li>
												</ul>
											</div>
											</li>'; 
										
							} else {}		
							
							if(checkRole('admin_developer')){ 
						
								echo '<li class="header-menu menu-left">
										<span>AI Admin</span>
									  </li>
								
									  <li class="sidebar-dropdown left-search">
										<a href="javascript:void(0);">
											<i class="bi bi-stars"></i>
											<span>AI Admin</span>
											<span class="badge rounded-pill bg-danger ms-2"></span>
										</a>
										<div class="sidebar-submenu"> 
											<ul>
												<li>
													<a href="ai_deep_thought.php"><span>DeepThought</span></a>
												</li>
												<li>
													<a href="who_dash_ai.php"><span>5W1H DeepThought</span></a>
												</li>
												
											</ul>
										</div>
									</li>'; 
										
								} else {}	
								
						echo '</ul>
						</div>';
							
						} else {} ?>
							
						<?php
							
						if(checkRole('my_learning')){
								
							echo '<div id="my-learning-tab" class="tab-pane">';
											
							echo '<ul>
									<li class="header-menu menu-left">
										<span>My Learning</span>
									</li>';
									
									if(checkRole('my_flashcards')){	
									
										echo '<li class="sidebar-dropdown left-search">
											<a href="javascript:void(0);">
												<i class="fa-brands fa-leanpub"></i>
												<span>Study Nook</span>
												<span class="badge rounded-pill bg-danger ms-2"></span>
											</a>
											<div class="sidebar-submenu">
												<ul>
													<li>
														<a href="flashcards.php"><span>Flashcards</span></a>
													</li>
												</ul>
											</div>
										</li>';
									}
									
									if(checkRole('my_accolades')){	
								
										echo '<li class="sidebar-dropdown left-search">
											<a href="javascript:void(0);">
												<i class="fa-solid fa-layer-group"></i>
												<span>Accolades</span>
												<span class="badge rounded-pill bg-danger ms-2"></span>
											</a>
											<div class="sidebar-submenu">
												<ul>
													<li>
														<a href="accolades.php"><span>Accolades</span></a>
													</li>
												</ul>
											</div>
										</li>';
									}

									if(checkRole('learning_admin')){	
										echo '<li class="header-menu menu-left mt-2" style="background-color:#066f85; border-bottom: 1px solid #0687a2; border-top: 1px solid #0687a2;">
												<span class="text-light mb-2"><i class="fa-solid fa-building-columns" style="margin-left:10px; margin-right:18px;"></i> Learning Admin</span>
												</li>
												<!--
													<li class="sidebar-dropdown left-search" style="background-color:#05add1;">
														<a href="javascript:void(0);" class="learning-admin">
															<i class="fa-solid fa-chalkboard text-light" style="background-color:#05add1;"></i>
															<span class="text-light">Courses</span>
															<span class="badge rounded-pill bg-danger ms-2"></span>
														</a>
														<div class="sidebar-submenu">
															<ul>
																<li>
																	<a href="course_admin.php">
																		<span>Create Course 
																			<span class="badge rounded-pill bg-pink" style="margin-left: 70px;">Admin</span>
																		</span>
																	</a>
																</li>
																<li>
																	<a href="view_course_list_admin.php">
																		<span>Course List 
																			<span class="badge rounded-pill bg-pink" style="margin-left: 70px;">Admin</span>
																		</span>
																	</a>
																</li>
															</ul>
														</div>
													</li> 
								
										           <li class="sidebar-dropdown left-search" style="background-color:#05add1;border-top:1px solid #0096b6;">
											  		<a href="javascript:void(0);" class="learning-admin"> 
														<i class="fa-solid fa-bolt text-light" style="background-color:#05add1;"></i>
														<span class="text-light">Skills</span>
														<span class="badge rounded-pill bg-danger ms-2"></span>
													</a>
													<div class="sidebar-submenu">
														<ul>
															<li>
																<a href="skill_create.php"><span>Create Skills</span></a>
															</li>
															<li>
																<a href="skill_categories.php"><span>Skill Categories</span></a>
															</li>
															<li>
																<a href="skill_reports.php"><span>Skill Reports</span></a>
															</li>
														</ul>
													</div>
												</li>
												<li class="sidebar-dropdown left-search" style="background-color:#05add1;border-top:1px solid #0096b6;">
											  		<a href="javascript:void(0);" class="learning-admin"> 
											  			<i class="fa-solid fa-stopwatch text-light" style="background-color:#05add1;"></i>
														<span class="text-light">Exams</span>
														<span class="badge rounded-pill bg-danger ms-2"></span>
													</a>
													<div class="sidebar-submenu">
														<ul>
															<li>
																<a href="exam_admin.php"><span>Create Exam</span></a>
															</li>
															<li>
																<a href="quiz_admin.php"><span>Create Quiz</span></a>
															</li>
															<li>
																<a href="view_all_exams.php"><span>View All Exams</span></a>
															</li>
														</ul>
													</div>
												</li> -->';
			
										  	  echo '<li class="sidebar-dropdown left-search" style="background-color:#05add1;border-top:1px solid #0096b6;">
											  		<a href="javascript:void(0);" class="learning-admin">
										  				<i class="fa-solid fa-bolt-lightning text-light" style="background-color:#05add1;"></i>
														<span class="text-light">Flashcards</span>
														<span class="badge rounded-pill bg-danger ms-2"></span>
													</a>
													<div class="sidebar-submenu">
														<ul>
															<li>
																<a href="flashcard_create.php"><span>Flashcards</span></a>
															</li>
															<li>
																<a href="flashcard_categories.php"><span>Categories</span></a>
															</li>
															<li>
																<a href="flashcard_enroll.php"><span>Enrollments</span></a>
															</li>
															<li>
																<a href="flashcard_reports.php"><span>Reports</span></a>
															</li>
														</ul>
													</div>
												</li>';
												
												echo '<li class="sidebar-dropdown left-search" style="background-color:#05add1;border-top:1px solid #0096b6;">
				  										<a href="javascript:void(0);" class="learning-admin">
				  											<i class="fa-solid fa-medal text-light" style="background-color:#05add1;"></i>
															<span class="text-light">Accolades</span>
				  											<span class="badge rounded-pill bg-danger ms-2"></span>
				  										</a>
				  										<div class="sidebar-submenu">
				  											<ul>
				  												<li>
				  													<a href="admin_accolades.php"><span>Accolades</span></a>
				  												</li>
				  											</ul>
				  										</div>
													</li>';
													
											} else {}
												
											echo '</ul></div>';
									
									  } else {} ?>  
										  
							<div class="read_active_logins mb-2 left-search list-unstyled"></div>
					</div>
				</div>
			</div>
			
            <div class="sidebar-footer">
				<div class="dropdown">
					<a href="javascript:void(0);" class="dropup" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-bell"></i>
						<span id="totals-foot-notification" style="<?php if($toggle_notify_totals == 0){echo "display:none";} else {echo "";}?>">
							<small><span class="badge rounded-pill bg-secondary notification total_alert_count"></span></small>
						</span>
					</a>
                    <div class="dropdown-menu notifications w-100" aria-labelledby="dropdownMenuMessage">
                        <div class="notifications-header dropup">
                            <i class="fa fa-bell"></i>
                            Notifications
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="alerts.php">
                            <div class="notification-content">
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle bg-hot"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Alerts</div>
                                    <div class="notification-time">
                                        <span class="alert_count"></span><span class="zero_alert_count"></span> Unread Alerts
                                    </div>
                                </div>
                            </div>
                        </a>
                        <a class="dropdown-item" href="index.php">
                            <div class="notification-content">
                                <div class="icon">
                                    <i class="fas fa-bell bg-mint"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Posts</div>
                                    <div class="notification-time">
										<span class="unread_count"></span><span class="zero_post_count"></span> Unread Posts
									</div>
                                </div>
                            </div>
                        </a>
                        <a class="dropdown-item" href="messages.php">
                            <div class="notification-content">
                                <div class="icon">
                                    <i class="fa-solid fa-envelope-open bg-cool-ice"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Messages</div>
                                    <div class="notification-time">
                                        <span class="message_count"></span><span class="zero_msg_count"></span> Unread Messages
                                    </div>
                                </div>
                            </div>
                        </a>
                        <a class="dropdown-item" href="kudos.php">
                            <div class="notification-content">
                          	  	<div class="icon">
                                    <i class="fa-solid fa-star bg-edit"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Kudos</div>
									<div class="notification-time">
										<span class="kudos_count"></span><span class="zero_kudos_count"></span> Unread Kudos
									</div>
                              	</div>
                        	</div>
						</a>
						
						<?php
	
							if(checkRole('poll_view')){
								echo '<a class="dropdown-item" href="poll_dashboard.php">
                            			<div class="notification-content">
                                			<div class="icon">
                                    			<i class="fa-solid fa-square-poll-horizontal bg-purple-haze"></i>
                                			</div>
                                			<div class="content">
                                    			<div class="notification-detail">Polls</div>
												<div class="notification-time">
													<span class="poll_count"></span> New Polls
												</div>
                                			</div>
                            			</div>
                        			  </a>';
							} else {}
							
						?>
						
						<?php
						if(checkRole('admin_developer')){
								echo '<a class="dropdown-item" href="gem_board.php">
                            			<div class="notification-content">
                                			<div class="icon">
                                    			<i class="fa-solid fa-gem bg-concrete"></i>
											</div>
                                			<div class="content">
                                    			<div class="notification-detail">Gems</div>
												<div class="notification-time">
													<span class="gem_count"></span> New Gems
												</div>
                                			</div>
                            			</div>
                        			  </a>';
							} else {}
						?>
						
						<div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="notifications.php">View all notifications</a>
                    </div>
				</div>
				
				<div class="dropdown">
                    <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-envelope"></i>
						<span id="messages-foot-notification" style="<?php if($toggle_notify_messages == 0){echo "display:none";} else {echo "";}?>">
							<span class="badge rounded-pill bg-fresh notification message_count">
								<?php
								if (countUnreadMessages() > 0){
									echo countUnreadMessages();
								} else { echo '<style> .bg-fresh {display:none;} </style>';}
								?>
							</span>
						</span>
					</a>
                    <div class="dropdown-menu messages w-100" aria-labelledby="dropdownMenuMessage">
                        <div class="messages-header">
                            <i class="fa fa-envelope"></i>
                            Messages
                        </div>
                        <div class="read_msg_footer"></div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center" href="messages.php">View all messages</a>
                    </div>
                </div>
				
				<div class="dropdown">
					<a href="javascript:void(0);" class="dropup" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-cog"></i>
					</a>
                    <div class="dropdown-menu notifications w-100" aria-labelledby="dropdownMenuMessage">
						<div class="notifications-header dropup shadow-sm rounded" style="background-color: #f57c00; color: white;">
							<i class="fa fa-cog"></i>
							Settings
						</div>
                        
                        <div class="dropdown-divider"></div>
						<a class="dropdown-item" href="profile.php">
                            <div class="notification-content">
                                <div class="icon">
                                    <i class="fa-solid fa-user bg-matrix"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Profile</div>
                                    <div class="notification-time">
                                        <span class="message_count"></span><span class="zero_msg_count"></span> User Profile
                                    </div>
                                </div>
                            </div>
                        </a>
						<a class="dropdown-item" href="feedback.php">
                            <div class="notification-content">
                                <div class="icon">
                                    <i class="far fa-dot-circle bg-matrix"></i>
                                </div>
                                <div class="content">
                                    <div class="notification-detail">Feedback</div>
										<div class="notification-time">
										 Provide Feedback
										</div>
                                	</div>
                            	</div>
                        </a>
						
						<?php
							if($user_phone_code !='0'){
								echo '<a class="dropdown-item" href="javascript:void(0);">
                            		  	 <div class="notification-content">
                                			<div class="icon">
                                    			<i class="fa-brands fa-galactic-republic bg-matrix"></i>
                                			</div>
                                			<div class="content">
                                    			<div class="notification-detail">Phone Code Standard</div>
                                    			<div class="notification-time">
                                        			<span class=""></span> '.$user_phone_code.'
                                    			</div>
                                			</div>
                            			</div>
                        			 </a>';
			
							} else if ($user_phone_code == '0'){}
					
							if($sup_phone_code !='0'){
								echo '<a class="dropdown-item" href="javascript:void(0);">
                            			 <div class="notification-content">
                                			<div class="icon">
                                    			<i class="fa-brands fa-galactic-senate bg-matrix"></i>
                                			</div>
                                			<div class="content">
                                    			<div class="notification-detail">Phone Code Supervisor</div>
                                    			<div class="notification-time">
                                        			<span class=""></span> '.$sup_phone_code.'
                                    			</div>
                                			</div>
                            			</div>
                        			</a>';

							} else if ($sup_phone_code == '0'){}
						?>
						
                    </div>
                </div>
				
				<div class="dropdown">
					<a href="javascript:void(0);" class="dropup toggle-sidebar-right" aria-haspopup="true" aria-expanded="false">
						<i class="fas fa-align-right"></i>
					</a>
				</div>
				<div>
					<a href="javascript:void(0);" data-original-title="Log Out" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="fa fa-power-off"></i>
                    </a>
                </div>
            </div>
        </nav>

<script>
function readExp(){
	$.get("ajax/profile/generate_exp.php", function (data, status) {
    	$(".xp_content").html(data);
  	});
};

function readActiveLogins(){
	$.get("ajax/login/read_active_logins.php", function (data, status) {
    	$(".read_active_logins").html(data);
  	});
};

function readSwitchboardCatLinks(){
	$.get("ajax/switchboard/read_switchboard_cat_links.php", function (data, status) {
	  $(".switchboard_cat_links").html(data);
  	});
};

$(document).ready(function(){
	readExp();
	readActiveLogins();
	readSwitchboardCatLinks();
	
	<?php if(checkRole('lighthouse_harbor') || checkRole('lighthouse_keeper')): ?>
	loadLighthouseCounts();
	<?php endif; ?>
});

function loadLighthouseCounts() {
	$.ajax({
		url: 'ajax/lh_counters/read_signal_counts.php',
		method: 'GET',
		data: { get_counts_only: true },
		dataType: 'json',
		success: function(response) {
			if (response.status === 'success' && response.counts) {
				const counts = response.counts;
				
				// Harbor: My Signals count
				if (counts.my_signals !== undefined) {
                    $('#count-my-signals').text(counts.my_signals || 0);
                }
				
				// Harbor: My Closed Signals count
				if (counts.my_closed_signals !== undefined) {
                    $('#count-my-closed-signals').text(counts.my_closed_signals || 0);
                }
				
				// Keeper Quick Access counts
				if (counts.quick_access) {
					$('#quick-all-signals').text(counts.quick_access.all || 0);
					$('#quick-assigned-signals').text(counts.quick_access.assigned || 0);
					$('#quick-unassigned-signals').text(counts.quick_access.unassigned || 0);
					$('#quick-closed-signals').text(counts.quick_access.closed || 0);
				}
				
				// Reset all dock counts to 0 first, then update with actual counts
				$('[id^="dock-count-"]').text('0');
				$('[id^="dock-viewall-count-"]').text('0');
				
				// Dock counts
				if (counts.docks) {
					Object.keys(counts.docks).forEach(dockId => {
						const dockCount = counts.docks[dockId] || 0;
						$('#dock-count-' + dockId).text(dockCount);
						$('#dock-viewall-count-' + dockId).text(dockCount);
					});
				}
				
				// Reset all dock-state counts to 0 first
				$('[id^="dock-state-count-"]').text('0');
				
				// Dock-State combination counts
				if (counts.dept_status) {
					Object.keys(counts.dept_status).forEach(key => {
						const [dockId, stateId] = key.split('-');
						$('#dock-state-count-' + dockId + '-' + stateId).text(counts.dept_status[key] || 0);
					});
				}
			}
		},
		error: function(xhr, status, error) {
			console.error('Error loading Lighthouse counts:', error);
			console.error('Response:', xhr.responseText);
		}
	});
}

/**
 * Refresh the entire lighthouse sidebar navigation
 * @param {boolean} countsOnly - If true, only refresh counts. If false, refresh full structure + counts.
 */
function refreshLighthouseSidebar(countsOnly) {
    console.log('refreshLighthouseSidebar called with countsOnly:', countsOnly);
    
    // Default to counts only if not specified
    if (countsOnly === undefined || countsOnly === true) {
        console.log('Only refreshing counts');
        loadLighthouseCounts();
        return;
    }
    
    console.log('Refreshing full sidebar structure...');
    
    // Refresh the full sidebar navigation structure
    $.ajax({
        url: 'ajax/lighthouse_sidebar/lighthouse_sidebar_nav.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('AJAX response:', response);
            
            if (response.success && response.html) {
                // Find the marker elements
                var $start = $('#lh-nav-start');
                var $end = $('#lh-nav-end');
                
                console.log('Found markers - start:', $start.length, 'end:', $end.length);
                
                if ($start.length && $end.length) {
                    // Remove all elements between the markers
                    var $elementsToRemove = $start.nextUntil('#lh-nav-end');
                    console.log('Elements to remove:', $elementsToRemove.length);
                    $elementsToRemove.remove();
                    
                    // Insert the new HTML after the start marker
                    $start.after(response.html);
                    console.log('New HTML inserted');
                    
                    // Reinitialize dropdown functionality for new elements
                    initializeLighthouseDropdowns();
                }
                
                // Load the counts after structure is updated
                loadLighthouseCounts();
            } else {
                console.error('Failed to refresh lighthouse sidebar:', response.message || 'Unknown error');
                loadLighthouseCounts();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing lighthouse sidebar:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            loadLighthouseCounts();
        }
    });
}

/**
 * Reinitialize the sidebar dropdown functionality after dynamic content update
 */
function initializeLighthouseDropdowns() {
    // Find all new lighthouse nav items between markers and bind click handlers
    $('#lh-nav-start').nextUntil('#lh-nav-end').filter('.sidebar-dropdown').each(function() {
        $(this).find('> a').off('click').on('click', function(e) {
            e.preventDefault();
            var $parent = $(this).parent();
            var $submenu = $parent.find('.sidebar-submenu');
            
            if ($parent.hasClass('active')) {
                $parent.removeClass('active');
                $submenu.slideUp(200);
            } else {
                // Close other dropdowns
                $('#lh-nav-start').nextUntil('#lh-nav-end').filter('.sidebar-dropdown.active')
                    .removeClass('active').find('.sidebar-submenu').slideUp(200);
                $parent.addClass('active');
                $submenu.slideDown(200);
            }
        });
    });
}
</script>

<script>
$(document).ready(function () {
	var collapseItem = $("#quick-links-header");
	collapseItem.on("hidden.bs.collapse", function() {
	  localStorage.setItem("coll_" + this.id, false);
	});
	collapseItem.on("shown.bs.collapse", function() {
	  localStorage.setItem("coll_" + this.id, true);
	});
	if (localStorage.getItem("coll_" + collapseItem.attr("id")) == "true") {
	    collapseItem.collapse("show");
	}
});
</script>