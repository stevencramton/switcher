<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('system_service_groups')) {
    header("Location:../../index.php?msg1");
	exit();
}
?>

<script>
$(document).ready(function(){
	$('[data-button-svg]').on('click', function () {
		var $this = $(this);
		$this.data("obtn", $this.html());
		var nhtml = "<span class='spinner-grow spinner-grow-sm' role='status' aria-hidden='true'></span> " + this.dataset.buttonSvg;
		$this.html(nhtml);
		$this.attr("disabled", true);

		setTimeout(function () {
			$this.html($this.data("obtn"));
			$this.attr("disabled", false);
		}, 800);
	});
	$(".copy_group_name_btn").on("click", function(){
		var group_name = $(this).closest('.row').find('.group_name').text();
		copyToClipboard(group_name);
	})
});

function copyToClipboard(textToCopy) {
  var textArea;

  function isOS() {
	  return navigator.userAgent.match(/(iPod|iPhone|iPad)/);
  }

  function createTextArea(text) {
    textArea = document.createElement('textArea');
    textArea.readOnly = false;
    textArea.contentEditable = true;
    textArea.value = text;
    document.body.appendChild(textArea);
  }

  function selectText() {
    var range, selection;

    if (isOS()) {
      range = document.createRange();
      range.selectNodeContents(textArea);
      selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      textArea.setSelectionRange(0, 999999);
    } else {
      textArea.select();
    }
  }

  function copyTo() {
    document.execCommand('copy');
    document.body.removeChild(textArea);
  }

  createTextArea(textToCopy);
  selectText();
  copyTo();
}
</script>

<script>
$(document).ready(function(){
	$(".collapse").on('show.bs.collapse', function(){
		$(this).prev(".card-header").find(".fa-dot-circle").removeClass("fa-dot-circle").addClass("fa-circle");
	}).on('hide.bs.collapse', function(){
		$(this).prev(".card-header").find(".fa-circle").removeClass("fa-circle").addClass("fa-dot-circle");
	});
});
</script>

<style>
.product_accordion {
	border-top: 1px solid #dee2e6;
	}

.accordion > .card:not(:first-of-type) {
	border-top-left-radius: 4px !important;
	border-top-right-radius: 4px !important;
	}

.accordion > .card:not(:last-of-type) {
	border-bottom-right-radius: 4px !important;
	border-bottom-left-radius: 4px !important;
	}

.accordion > .card > .card-header {
	border-radius: 0;
	margin-bottom: 0px;
	}

.card-header {
	border-bottom:none!important;
	}
	
.nevo1 {
	display:none;
	}
</style>

<?php

$data = '<div class="accordion context" id="test">';

if (isset($_GET['group_name']) && $_GET['group_name'] !== "") {
    $service_group_name = $_GET['group_name'];
	$query = "SELECT * FROM service_groups WHERE group_id = ? ORDER BY group_display_order ASC";
    $stmt = mysqli_prepare($dbc, $query);
	mysqli_stmt_bind_param($stmt, "s", $service_group_name);
} else {
    $query = "SELECT * FROM service_groups ORDER BY group_display_order ASC";
    $stmt = mysqli_prepare($dbc, $query);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    exit();
}

if (mysqli_num_rows($result) > 0) {
    $number = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $group_id = mysqli_real_escape_string($dbc, strip_tags($row['group_id']));
        $group_icon_1 = mysqli_real_escape_string($dbc, strip_tags($row['group_icon_1']));
        $group_icon_2 = mysqli_real_escape_string($dbc, strip_tags($row['group_icon_2']));
        $group_icon_3 = mysqli_real_escape_string($dbc, strip_tags($row['group_icon_3']));
        $group_color = mysqli_real_escape_string($dbc, strip_tags($row['group_color']));
        $group_custom_color = mysqli_real_escape_string($dbc, strip_tags($row['group_custom_color']));
        $group_name = htmlspecialchars(strip_tags($row['group_name']));
        $group_tags = mysqli_real_escape_string($dbc, strip_tags($row['group_tags']));
        
        $data .= '<script>$("#service_product").prop("disabled", false);</script>
            <div class="card border-0 mb-2">
                <div class="card-header bg-white shadow-sm border" id="headingOne">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">
                                <button type="button" class="btn btn-sm btn-link collapsed text-decoration-none" data-bs-toggle="collapse" data-bs-target="#new'.$group_id.'">
                                    <i class="far fa-dot-circle me-1 '.$group_color.'" style="color:'. $group_custom_color .'"></i>';

                                    if ($group_icon_1 == 1){
                                        $data .= '<span class="bg-white border rounded p-1 me-2 ms-1">
                                                    <i class="fa-solid fa-building-columns '.$group_color.'" style="color:'. $group_custom_color .'"></i> 
                                                </span>';
                                    }
                                    
                                    if ($group_icon_2 == 1){
                                        $data .= '<span class="bg-white border rounded p-1 me-2 ms-1">
                                                    <i class="fa-brands fa-unity '.$group_color.'" style="color:'. $group_custom_color .'"></i> 
                                                </span>';
                                    }
                                    
                                    if ($group_icon_3 == 1){
                                        $data .= '<span class="bg-white border rounded p-1 me-2 ms-1">
                                                    <i class="fa-solid fa-scale-balanced '.$group_color.'" style="color:'. $group_custom_color .'"></i> 
                                                </span>';
                                    }
                                    
                                    $data .= '<span class="group_name fs-6 fw-bold text-decoration-none '.$group_color.'" style="color:'. $group_custom_color .'">'.$group_name.'</span>
                                </button>
                            </h2>
                        <div>
                            <!-- hidden tags used for keywork searching -->
                            <span style="display:none;"><small>'.$group_tags.'</small></span>
                        </div>
                    </div>
                    <div class="col-md-4 float-end">
                        <div class="btn-group float-end opacity-75">
                            <button type="button" class="btn btn-outline-secondary btn-sm copy_group_name_btn float-end" data-button-svg="Copied">
                                <i class="far fa-copy"></i>
                            </button>';
                            if(checkRole('service_groups_manage')){
                                $data .= '<button type="button" class="btn btn-secondary btn-sm float-end" onclick="GetServiceGroupDetails('.$group_id.')">
                                    <i class="bi bi-gear-wide-connected"></i>
                                </button>';
                            }
                            $data .= '</div>
                    </div>
                </div>
            </div>
            <div id="new'.$row['group_id'].'" class="collapse product_accordion" aria-labelledby="headingOne" data-parent="#test">
                <div class="card-body bg-light">
                <!-- Product | Application | Component | Element | Search -->
                <div class="product-search search">
                    <div class="input-group mb-3">
                        <div class="form-floating form-floating-group flex-grow-1">
                            <input type="text" class="form-control shadow-sm product-search-menu" id="products_'.$group_id.'" name="keyword" placeholder="Components..." autocomplete="off">
                            <label for="products_'.$group_id.'">Components...</label>
                        </div>
                        <button type="button" class="btn btn-light-gray shadow-sm input-group-text product-search-reset" style="width:60px;">
                            <i class="fa fa-search" aria-hidden="true" style="font-size:20px;"></i>
                        </button>
                    </div>
                </div>';
				
				$data .='<div class="comp">';
				
             	$query_two = "SELECT * FROM service_products WHERE product_service_group = ? ORDER BY product_name ASC";
                $stmt_two = mysqli_prepare($dbc, $query_two);
				mysqli_stmt_bind_param($stmt_two, "s", $group_id);
            	mysqli_stmt_execute($stmt_two);
           	 	$results = mysqli_stmt_get_result($stmt_two);

                if (!$results) {
                    exit();
                }

                if (mysqli_num_rows($results) > 0) {
                    while ($row = mysqli_fetch_assoc($results)) {
                        $product_id = mysqli_real_escape_string($dbc, strip_tags($row['product_id']));
                        $product_name = htmlspecialchars(strip_tags($row['product_name']));
                        $product_private = mysqli_real_escape_string($dbc, strip_tags($row['product_private']));
						$product_steps = $row['product_steps']; 
                        $product_info = htmlspecialchars($row['product_info']);
                        $product_tags = htmlspecialchars(strip_tags($row['product_tags']));
                        $product_created_by = mysqli_real_escape_string($dbc, strip_tags($row['product_created_by']));
                        $product_date_created = mysqli_real_escape_string($dbc, strip_tags($row['product_date_created']));
						$product_edited_by = mysqli_real_escape_string($dbc, strip_tags($row['product_edited_by'] ?? ''));
                        $product_date_edited = mysqli_real_escape_string($dbc, strip_tags($row['product_date_edited'] ?? ''));
                    	$affiliation_unh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh']));
                        $affiliation_psu = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_psu']));
                        $affiliation_ksc = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_ksc']));
                     	$affiliation_usnh = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_usnh']));
                        $affiliation_unh_manch = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_manch']));
                        $affiliation_unh_law = mysqli_real_escape_string($dbc, strip_tags($row['affiliation_unh_law']));
								
						$data .='<div class="card bg-white border-0 shadow-sm rounded comp-item mb-3">
									<div class="card-header bg-white border-0 shadow-sm">
										<nav>
						        			<div class="nav nav-pills " id="nav-tab-'.$product_id.'" role="tablist">
						            			
												<button class="nav-link active" id="nav-info-tab-'.$product_id.'" data-bs-toggle="tab" 
						                    	data-bs-target="#nav-info-'.$product_id.'" type="button" role="tab" 
						                    	aria-controls="nav-info-'.$product_id.'" aria-selected="true">
						                			<i class="fa-solid fa-circle-info"></i> Info
						            			</button>
						            			
												<button class="nav-link" id="nav-steps-tab-'.$product_id.'" data-bs-toggle="tab" 
						                    	data-bs-target="#nav-steps-'.$product_id.'" type="button" role="tab" 
						                    	aria-controls="nav-steps-'.$product_id.'" aria-selected="false">
						                			<i class="fa-solid fa-list-check"></i> Details
						            			</button>';
												if (checkRole('service_products_manage')) {
													$data .= '<button type="button" class="ms-auto btn btn-atomic opacity-75" onclick="GetServiceProductDetails('.$product_id.')">
														<i class="bi bi-gear-wide-connected"></i>
													</button>';
												}
												
										$data .='</div>
										</nav>
									</div>
							
			                		<div class="card-body p-2">
			                    		<div class="tab-content" id="nav-product-content-'.$product_id.'">
			                        		<div class="tab-pane fade show active" id="nav-info-'.$product_id.'" role="tabpanel" aria-labelledby="nav-info-tab-'.$product_id.'" tabindex="0">
			                            		<div class="bg-white rounded p-3">';
												
												$data .= '<h6 class="fw-bold dark-gray mb-2 d-flex align-items-center"> '.$product_name.' <div class="ms-auto">';
												
												if ($affiliation_unh == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="University of New Hampshire">
												                 <i class="fa-solid fa-building-columns text-primary"></i> UNH
												             </span>';
												} 

												if ($affiliation_psu == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="Plymouth State University">
												                 <i class="fa-solid fa-building-columns text-success"></i> PSU
												             </span>';
												} 

												if ($affiliation_ksc == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="Keene State College">
												                 <i class="fa-solid fa-building-columns text-danger"></i> KSC
												             </span>';
												}

												if ($affiliation_usnh == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="University System of New Hampshire">
												                 <i class="fa-brands fa-unity text-primary"></i> USNH 
												             </span>';
												}

												if ($affiliation_unh_manch == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="University of New Hampshire at Manchester">
												                 <i class="fa-brands fa-unity text-info"></i> UNH Manchester
												             </span>';
												}

												if ($affiliation_unh_law == 1) { 
												    $data .= '<span class="bg-white border rounded p-1 me-2 opacity-75" data-bs-toggle="tooltip" 
												                 data-bs-placement="top" data-bs-title="Franklin Pierce School of Law">
												                 <i class="fa-solid fa-scale-balanced text-warning"></i> UNH Law
												             </span>';
												}

												$data .= '</div>';
												$data .= '</h6>';
												
												$data .='<span class="nevo'.$product_private.'">
															<div class="mt-3 shadow-sm border rounded bg-light-blue-border p-3">
																<p class="mb-0 opacity-75">'. nl2br($product_info) .'</p>
															</div>
												 	   </span>
												</div>
											</div>

			                        		<div class="tab-pane fade" id="nav-steps-'.$product_id.'" role="tabpanel" aria-labelledby="nav-steps-tab-'.$product_id.'" tabindex="0">';

											if (!empty($product_steps)) {
    											$data .= '<div class="bg-light shadow-sm rounded p-3 mb-2">
        											<div>'.$product_steps.'</div>
    											</div>';
											} else { 

		$data .='<div class="bg-light shadow-sm rounded p-3 mb-2">
			<svg version="1.1" class="svgcheck mt-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
</svg>
<p class="one success">Details empty!</p>
<p class="complete">Details not found!</p></div>';
    }

$data .= '</div>
	</div>
	<div class="card-footer bg-white border-0">
		<div>
			<span class="text-muted small me-1"><i class="fas fa-tag"></i></span>';
					   
			$tags = htmlspecialchars(strip_tags($row['product_tags']));
							
			if($tags !== ""){
				$tag = explode(',', $tags);
				asort($tag);
				foreach ($tag as $key => $name) {
					$data .='<span class="badge bg-primary me-1">'.$name.'</span>';
				}
			} else {}
						  
$data .='</div>';
						   
	if (!$product_edited_by == ''){
		$data .='<div class="mt-2">
			<small class="text-secondary me-2"><i class="fa-solid fa-circle-user"></i> Created by: '.$product_created_by.'</small>
			<small class="text-secondary me-2"><i class="fa-regular fa-clock"></i> '.$product_date_created.'</small>
			<small class="text-secondary me-2"><i class="fa-solid fa-user-pen"></i> Updated by: '.$product_edited_by.'</small>
			<small class="text-secondary me-2"><i class="fa-regular fa-clock"></i> '.$product_date_edited.'</small>
			</div>';
		} else if (!$product_created_by == ''){
			$data .='<div class="mt-2">
				<small class="text-secondary me-2"><i class="fa-solid fa-circle-user"></i> Created by: '.$product_created_by.'</small>
 				<small class="text-secondary me-2"><i class="fa-regular fa-clock"></i> '.$product_date_created.'</small>
 				</div>';
		}
									
		$data .='</div>
			</div>
		</div>';
	}
						
} else {
	$data .='<div class="pt-3">
				<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2" style="margin: 0px auto 0 !important;">
  	  				<circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  					<polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
			 	</svg>
			 	<p class="one success">Records empty!</p>
			 	<p class="complete">Service Components not found!</p>
		     </div>';
	}
	
	mysqli_stmt_close($stmt_two);

	$data .='</div></div></div></div>';
	$number++;

	}

	$data .='</div>';

    } else { 

		$data .='<svg version="1.1" class="svgcheck" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
  <circle class="path circle" fill="none" stroke="rgba(165, 220, 134, 0.2" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
  <polyline class="path check" fill="none" stroke="#a5dc86" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
</svg>
<p class="one success">Records empty!</p>
<p class="complete">Service Groups not found!</p>';
    }

echo $data;

mysqli_stmt_close($stmt);
mysqli_close($dbc);
?>

<script>
    $(document).ready(function(){
        $(".product-search-menu").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            var parentCard = $(this).closest('.card');
            var listGroup = parentCard.find('.comp');
            var searchIcon = parentCard.find('.input-group-text.product-search-reset .fa');
            
            if (value === ''){
                searchIcon.removeClass('fa-circle-xmark').addClass("fa-search");
            } else {
                searchIcon.removeClass('fa-search').addClass("fa-circle-xmark");
            }
            
            listGroup.find("div.comp-item").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        $(".product-search-reset").click(function() {
            var parentCard = $(this).closest('.card');
            var listGroup = parentCard.find('.comp');
            var searchIcon = parentCard.find('.input-group-text.product-search-reset .fa');
            
            parentCard.find('.input-group .product-search-menu').val('').focus();
            searchIcon.removeClass('fa-circle-xmark').addClass("fa-search");
            listGroup.find("div.comp-item").show();
        });
    });
</script>

<script>
$(document).ready(function() {
	$('[data-bs-toggle="tooltip"]').tooltip();
	$('[data-bs-toggle="popover"]').popover();
});
</script>