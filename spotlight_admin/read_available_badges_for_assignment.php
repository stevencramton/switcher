<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
    echo "Unauthorized access";
    exit();
}

if (!isset($_SESSION['id'])) {
    echo "Session not found";
    exit();
}

$inquiry_id = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;
$current_badge_id = isset($_POST['current_badge_id']) ? (int)$_POST['current_badge_id'] : null;

$query = "SELECT 
    id, 
    name, 
    description,
    badge_image,
    gradient_1,
    gradient_2,
    color,
    icon,
    badge_cat_display,
    badge_border,
    badge_border_color,
    icon_size_range,
    icon_position,
    circle_position,
    circle_size,
    circle_bg,
    circle_border_color,
    circle_border,
    ribbon_vertical,
    ribbon_horizontal,
    ribbon_width,
    ribbon_height,
    ribbon_view,
    ribbon_size,
    ribbon_border,
    ribbon_text_color,
    ribbon_bg1,
    ribbon_bg2,
    ribbon_border_color
FROM badges 
ORDER BY badge_cat_display, name";

$result = mysqli_query($dbc, $query);
$badges = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $badges[] = $row;
    }
}

$grouped_badges = [];
foreach ($badges as $badge) {
    $category = $badge['badge_cat_display'] ?: 'General';
    if (!isset($grouped_badges[$category])) {
        $grouped_badges[$category] = [];
    }
    $grouped_badges[$category][] = $badge;
}
?>

<div class="mb-3">
    <div class="input-group mb-3">
        <div class="form-floating form-floating-group flex-grow-1">
            <input type="text" class="form-control shadow-sm" id="spotlight-badge-search" placeholder="Search badges...">
            <label for="spotlight-badge-search">Search badges...</label>
        </div>
        <button type="button" class="btn btn-light-gray shadow-sm input-group-text" style="width:46px; border-top-right-radius: 6px; border-bottom-right-radius: 6px;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
    
    <?php if ($current_badge_id): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Currently selected badge ID: <strong><?php echo $current_badge_id; ?></strong>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="clearBadgeSelection()">
                <i class="fas fa-times me-1"></i>Clear Selection
            </button>
        </div>
    <?php endif; ?>
</div>

<div id="badge-selection-grid">
    <?php foreach ($grouped_badges as $category => $category_badges): ?>
        <div class="badge-category-section mb-4">
            <h6 class="fw-bold text-muted mb-3">
                <i class="fas fa-tags me-2"></i><?php echo htmlspecialchars($category); ?>
                <span class="badge bg-secondary ms-2"><?php echo count($category_badges); ?></span>
            </h6>
            
            <div class="row gx-3">
                <?php foreach ($category_badges as $badge): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-3 badge-item" data-badge-name="<?php echo htmlspecialchars($badge['name']); ?>" data-category="<?php echo htmlspecialchars($category); ?>">
                        <div class="card h-100 badge-selection-card <?php echo ($current_badge_id == $badge['id']) ? 'selected' : ''; ?>" 
                             onclick="selectBadgeForAssignment(<?php echo $badge['id']; ?>, '<?php echo htmlspecialchars($badge['name']); ?>')"
                             style="cursor: pointer;">
                            <div class="card-body text-center position-relative">
                                
                             	<div class="badge-visual-container mb-3" style="height: 80px; position: relative;">
                                    <?php
                                  	$gradient_style = '';
                                    if ($badge['gradient_1'] && $badge['gradient_2']) {
                                        $gradient_style = "background: linear-gradient(45deg, {$badge['gradient_1']}, {$badge['gradient_2']});";
                                    } elseif ($badge['color']) {
                                        $gradient_style = "background-color: {$badge['color']};";
                                    } else {
                                        $gradient_style = "background-color: #f8f9fa;";
                                    }
                                    
                                    $border_style = '';
                                    if ($badge['badge_border']) {
                                        $border_color = $badge['badge_border_color'] ?: '#ededed';
                                        $border_style = "border: {$badge['badge_border']}px solid {$border_color};";
                                    }
                                    
                                    $circle_style = '';
                                    $circle_size = $badge['circle_size'] ?: 60;
                                    $circle_bg = $badge['circle_bg'] ?: '#ffffff';
                                    $circle_position = $badge['circle_position'] ?: 0;
                                    
                                    if ($badge['circle_border']) {
                                        $circle_border_color = $badge['circle_border_color'] ?: '#000000';
                                        $circle_style .= "border: {$badge['circle_border']}px solid {$circle_border_color};";
                                    }
                                    
                                    $icon_size = $badge['icon_size_range'] ?: 34;
                                    $icon_position = $badge['icon_position'] ?: 8;
                                    ?>
                                    
                                    <button type="button" class="btn badge-visual-btn position-relative" 
                                            style="width: 70px; height: 70px; <?php echo $gradient_style . $border_style; ?> border-radius: 50%;">
                                        
                                       <div class="badge-circle position-absolute" 
                                             style="width: <?php echo $circle_size; ?>px; 
                                                    height: <?php echo $circle_size; ?>px; 
                                                    background-color: <?php echo $circle_bg; ?>; 
                                                    border-radius: 50%; 
                                                    top: 50%; 
                                                    left: 50%; 
                                                    transform: translate(-50%, -50%); 
                                                    <?php echo $circle_style; ?>">
                                        </div>
                                        
                                      	<?php if ($badge['icon']): ?>
                                            <i class="<?php echo htmlspecialchars($badge['icon']); ?> position-absolute" 
                                               style="font-size: <?php echo $icon_size; ?>px; 
                                                      top: 50%; 
                                                      left: 50%; 
                                                      transform: translate(-50%, -50%); 
                                                      z-index: 2;"></i>
                                        <?php endif; ?>
                                        
                                     	<?php if ($badge['ribbon_view']): ?>
                                            <div class="ribbon position-absolute" 
                                                 style="bottom: <?php echo $badge['ribbon_vertical'] ?: -6; ?>px;
                                                        left: <?php echo $badge['ribbon_horizontal'] ?: 0; ?>px;
                                                        width: <?php echo $badge['ribbon_width'] ?: 100; ?>%;
                                                        height: <?php echo $badge['ribbon_height'] ?: 23; ?>px;
                                                        background: linear-gradient(to bottom, <?php echo $badge['ribbon_bg1'] ?: '#525252'; ?>, <?php echo $badge['ribbon_bg2'] ?: '#373737'; ?>);
                                                        color: <?php echo $badge['ribbon_text_color'] ?: '#ffffff'; ?>;
                                                        font-size: <?php echo $badge['ribbon_size'] ?: 12; ?>px;
                                                        display: flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        z-index: 3;">
                                                <?php echo htmlspecialchars($badge['name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </button>
                                </div>
                                
                              	<h6 class="card-title mb-2"><?php echo htmlspecialchars($badge['name']); ?></h6>
                           	 	
								<?php if ($current_badge_id == $badge['id']): ?>
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <i class="fas fa-check-circle text-success" style="font-size: 1.2em;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
$(document).ready(function(){
    $("#spotlight-badge-search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".badge-item").filter(function() {
            var badgeName = $(this).data('badge-name').toLowerCase();
            var category = $(this).data('category').toLowerCase();
            $(this).toggle(badgeName.indexOf(value) > -1 || category.indexOf(value) > -1);
        });
        
     	$(".badge-category-section").each(function() {
            var hasVisibleBadges = $(this).find(".badge-item:visible").length > 0;
            $(this).toggle(hasVisibleBadges);
        });
    });
});

function selectBadgeForAssignment(badgeId, badgeName) {
 	$('.badge-selection-card').removeClass('selected');
    $('.badge-selection-card .fas.fa-check-circle').remove();
    
 	$('[onclick*="' + badgeId + '"]').closest('.badge-selection-card').addClass('selected');
    $('[onclick*="' + badgeId + '"]').closest('.card-body').append('<div class="position-absolute top-0 end-0 p-2"><i class="fas fa-check-circle text-success" style="font-size: 1.2em;"></i></div>');
    
	$('#badge_id').val(badgeId);
 	showToast('Badge "' + badgeName + '" selected for spotlight award', 'success');
 	updateAwardSettings();
}

function clearBadgeSelection() {
    $('.badge-selection-card').removeClass('selected');
    $('.badge-selection-card .fas.fa-check-circle').remove();
    $('#badge_id').val('');
    showToast('Badge selection cleared', 'info');
}
</script>

<style>
.badge-selection-card {
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.badge-selection-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.badge-selection-card.selected {
    border-color: #28a745;
    background-color: #f8fff9;
}

.badge-visual-btn {
    border: none !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge-visual-btn:hover {
    transform: scale(1.05);
}

.ribbon {
    border-radius: 3px;
    font-weight: bold;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
}

.badge-category-section {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.badge-category-section:last-child {
    border-bottom: none;
}
</style>