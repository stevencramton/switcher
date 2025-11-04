<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!checkRole('spotlight_admin')){
    header("Location:../../index.php?msg1");
    exit();
}

if (!isset($_SESSION['id'])) {
    echo "Session not found";
    exit();
}

if (!isset($_POST['inquiry_id'])) {
    echo "No inquiry ID provided";
    exit();
}

$inquiry_id = mysqli_real_escape_string($dbc, $_POST['inquiry_id']);

?>

<style>
.vscomp-ele {
	display: inline-block;
	max-width:100%;
	width: 100%;
}
	
.nominee-item {
    transition: all 0.2s ease;
}

.nominee-item:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nominee-list {
    max-height: 400px;
    overflow-y: auto;
}

.form-check-input:indeterminate {
    background-color: #6c757d;
    border-color: #6c757d;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}

#bulk_delete_btn {
    transition: all 0.2s ease;
}

.shadow-sm:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

#bulk_delete_btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.nominee-item.filtered-out {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}

.nominee-checkbox.filtered-out {
    display: none !important;
}

.nominee-item:not(.filtered-out) {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.nominee-item {
    transition: all 0.3s ease;
    overflow: hidden;
}

#search_results_info {
    z-index: 10;
    position: relative;
}

.search-filter-container {
    position: relative;
}

.search-filter-container .input-group-text {
    background-color: #f8f9fa;
    border-right: none;
    color: #6c757d;
}

.search-filter-container .form-control {
    border-left: none;
    padding-left: 0;
    padding-right: 45px !important;
}

.search-filter-container .form-control:focus {
    border-color: #ced4da;
    box-shadow: none;
    border-left: none;
}

#clear_search {
    position: absolute !important;
    right: 8px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    z-index: 10 !important;
    border: none !important;
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
    padding: 4px 8px !important;
    border-radius: 4px !important;
    min-width: 30px !important;
    height: 30px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    transition: all 0.2s ease !important;
    cursor: pointer !important;
}

#clear_search:hover {
    background: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
}

#clear_search.show-clear {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

#clear_search.hide-clear {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}

.input-group {
    position: relative;
}

#nominee_search {
    padding-right: 45px !important;
}

#search_results_info {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    z-index: 5;
}

.nominee-item.filtered-out {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
}

.nominee-checkbox.filtered-out {
    display: none !important;
}

.nominee-item:not(.filtered-out) {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.nominee-item {
    transition: all 0.3s ease;
    overflow: hidden;
}

.input-group:hover .input-group-text {
    background-color: #e9ecef;
}

.form-control:focus + .btn {
    border-color: #86b7fe;
}

#select_all_nominees:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.nominee-item:not(.filtered-out) {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="row gx-3 mt-3">
    <!-- Add Nominees Section -->
    <div class="col-md-6">
        <div class="mb-3">
            <table class="table mb-2" style="height:48px;">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Add Nominees</th>
                    </tr>
                </thead>
            </table>

            <form id="add_nominee_form">
                <div class="row g-2">
                    <div class="col-12 mb-2"> 
                        <select id="spotlight_nominees" multiple name="spotlight_nominees[]">
                            <?php
                         	$user_query = "SELECT u.user, u.first_name, u.last_name 
                                          FROM users u 
                                          WHERE u.account_delete != '1' 
                                          AND u.user NOT IN (
                                              SELECT sn.assignment_user 
                                              FROM spotlight_nominee sn 
                                              WHERE sn.question_id = '$inquiry_id'
                                          )
                                          ORDER BY u.first_name ASC, u.last_name ASC";
                            
                            if ($user_result = mysqli_query($dbc, $user_query)) {
                                while ($user_row = mysqli_fetch_assoc($user_result)) {
                                    $user = htmlspecialchars($user_row['user']);
                                    $first_name = htmlspecialchars($user_row['first_name']);
                                    $last_name = htmlspecialchars($user_row['last_name']);
                                    
                                    echo '<option value="' . $user . '">' . $first_name . ' ' . $last_name . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" id="add_spotlight_nominee_hidden_id" value="<?php echo $inquiry_id; ?>"> 
                    </div>
                </div>
                
                <div class="row g-2">
                    <div class="col-12">
                        <button type="button" class="btn btn-purple-haze w-100 shadow-sm" onclick="addSpotlightNomineesBulk()" style="height:46px;">
                            <i class="fa-solid fa-user-plus me-2"></i>Add Selected Nominees
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="mb-3">
            <table class="table mb-2">
                <thead class="table-light">
                    <tr>
                        <th scope="col">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Current Nominees</span>
                                <div>
                                	<div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="select_all_nominees" 
                                               onchange="toggleAllNominees()">
                                        <label class="form-check-label" for="select_all_nominees">
                                            Select All
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-hot btn-sm me-2" id="bulk_delete_btn" 
                                            onclick="bulkDeleteNominees()" disabled>
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </th>
                    </tr>
                </thead>
            </table>
			
			<div class="shadow-sm border rounded p-2 bg-white mb-3 search-filter-container">
			    <div class="input-group position-relative">
			        <span class="input-group-text bg-light border-end-0">
			            <i class="fa-solid fa-search text-muted"></i>
			        </span>
			        <input type="text" class="form-control border-start-0" id="nominee_search" 
			               placeholder="Search nominees by name..." 
			               autocomplete="off">
			        <button class="btn" type="button" id="clear_nominee_search" 
			                style="display: none;" title="Clear search">
			            <i class="fa-solid fa-times" style="margin-top: 4px;"></i>
			        </button>
			    </div>
			    <div id="search_results_info" class="small text-muted mt-2" style="display: none;"></div>
			</div>
            
            <div id="current_nominees_list">
                <?php
                $nominee_query = "SELECT sn.assignment_id, sn.assignment_user,
                                         u.first_name, u.last_name, u.profile_pic
                                  FROM spotlight_nominee sn
                                  LEFT JOIN users u ON sn.assignment_user = u.user
                                  WHERE sn.question_id = '$inquiry_id'
                                  ORDER BY u.first_name ASC, u.last_name ASC";
                
                if ($nominee_result = mysqli_query($dbc, $nominee_query)) {
                    if (mysqli_num_rows($nominee_result) > 0) {
                        echo '<div class="nominee-list">';
                        while ($nominee_row = mysqli_fetch_assoc($nominee_result)) {
                            $assignment_id = $nominee_row['assignment_id'];
                            $username = htmlspecialchars($nominee_row['assignment_user']);
                            $first_name = htmlspecialchars($nominee_row['first_name'] ?? $username);
                            $last_name = htmlspecialchars($nominee_row['last_name'] ?? '');
                            $profile_pic = htmlspecialchars($nominee_row['profile_pic'] ?? 'media/links/default_avatar.png');
                            
                            echo '<div class="nominee-item d-flex align-items-center mb-2 p-2 border rounded" id="nominee_' . $assignment_id . '">';
                            
                        	echo '<div class="form-check me-3">';
                            echo '<input class="form-check-input nominee-checkbox" type="checkbox" value="' . $assignment_id . '" id="nominee_check_' . $assignment_id . '" onchange="updateBulkDeleteButton()">';
                            echo '</div>';
                            
                          	echo '<div class="d-flex align-items-center flex-grow-1">';
                            echo '<img src="' . $profile_pic . '" alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">';
                            echo '<div>';
                            echo '<div class="fw-bold">' . $first_name . ' ' . $last_name . '</div>';
                           	echo '</div>';
                            echo '</div>';
                          	echo '<button type="button" class="btn btn-hot btn-sm" onclick="removeNominee(' . $assignment_id . ')" title="Remove Nominee">';
                            echo '<i class="fa-solid fa-trash"></i>';
                            echo '</button>';
                            
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="text-center text-muted py-4">';
                        echo '<i class="fas fa-users fa-3x mb-3"></i>';
                        echo '<p>No nominees added yet</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">Error loading nominees</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function addSpotlightNomineesBulk() {
    var selectedUsers = document.querySelector('#spotlight_nominees').value;
    var inquiryId = document.getElementById('add_spotlight_nominee_hidden_id').value;
    
    if (!selectedUsers || selectedUsers.length === 0) {
        alert('Please select at least one user to nominate');
        return;
    }
    
    $.ajax({
        url: 'ajax/spotlight_admin/add_spotlight_nominee.php',
        method: 'POST',
        data: {
            inquiry_id: inquiryId,
            assignment_user: JSON.stringify(selectedUsers)
        },
        success: function(response) {
            try {
                var data = JSON.parse(response);
                if (data === "success") {
                    alert('Nominees added successfully');
                    readSpotlightDetails(inquiryId);
                } else if (Array.isArray(data)) {
                    alert('Some users were already nominated: ' + data.join(', '));
                    readSpotlightDetails(inquiryId);
                } else {
                    alert('Unexpected response');
                    readSpotlightDetails(inquiryId);
                }
            } catch (e) {
                if (response.includes('success')) {
                    alert('Nominees added successfully');
                    readSpotlightDetails(inquiryId);
                } else {
                    alert('Error: ' + response);
                }
            }
        },
        error: function(xhr, status, error) {
            alert('Network error: ' + xhr.status + ' - ' + error);
        }
    });
}

function removeNominee(assignmentId) {
    if (!confirm('Are you sure you want to remove this nominee?')) {
        return;
    }
    
    var inquiryId = document.getElementById('add_spotlight_nominee_hidden_id').value;
    
    $.post('ajax/spotlight_admin/remove_spotlight_nominee.php', {
        assignment_id: assignmentId
    }, function(response) {
        try {
            var result = JSON.parse(response);
            if (result.status === 'success') {
                alert(result.message);
                readSpotlightDetails(inquiryId);
            } else {
                alert(result.message || 'Error removing nominee');
            }
        } catch (e) {
            alert('Nominee removed successfully');
            readSpotlightDetails(inquiryId);
        }
    }).fail(function() {
        alert('Network error occurred');
    });
}

function toggleAllNominees() {
    var selectAll = document.getElementById('select_all_nominees');
    var visibleCheckboxes = document.querySelectorAll('.nominee-checkbox:not(.filtered-out)');
    
    visibleCheckboxes.forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    var visibleCheckboxes = document.querySelectorAll('.nominee-checkbox:not(.filtered-out)');
    var checkedBoxes = document.querySelectorAll('.nominee-checkbox:not(.filtered-out):checked');
    var bulkDeleteBtn = document.getElementById('bulk_delete_btn');
    var selectAllCheckbox = document.getElementById('select_all_nominees');
    
 	if (checkedBoxes.length > 0) {
        bulkDeleteBtn.disabled = false;
        bulkDeleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i> (' + checkedBoxes.length + ')';
    } else {
        bulkDeleteBtn.disabled = true;
        bulkDeleteBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
    }
    
	if (visibleCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
        selectAllCheckbox.disabled = true;
    } else {
        selectAllCheckbox.disabled = false;
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedBoxes.length === visibleCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }
}

function bulkDeleteNominees() {
    var checkedBoxes = document.querySelectorAll('.nominee-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Please select nominees to delete');
        return;
    }
    
    var count = checkedBoxes.length;
    if (!confirm('Are you sure you want to remove ' + count + ' nominee' + (count > 1 ? 's' : '') + '?')) {
        return;
    }
    
    var assignmentIds = Array.from(checkedBoxes).map(function(cb) { return cb.value; });
    var inquiryId = document.getElementById('add_spotlight_nominee_hidden_id').value;
  	var button = document.getElementById('bulk_delete_btn');
    var originalText = button.innerHTML;
    
 	button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
    button.disabled = true;
    
    $.post('ajax/spotlight_admin/bulk_remove_nominees.php', {
        assignment_ids: assignmentIds
    }, function(response) {
        try {
            var result = JSON.parse(response);
            if (result.status === 'success') {
                alert(result.message);
                readSpotlightDetails(inquiryId);
            } else {
                alert(result.message || 'Error removing nominees');
            }
        } catch (e) {
            alert('Nominees removed successfully');
            readSpotlightDetails(inquiryId);
        }
    }).fail(function() {
        alert('Network error occurred');
    }).always(function() {
    	button.innerHTML = '<i class="fa-solid fa-trash"></i>';
        button.disabled = true;
        
      	var selectAllCheckbox = document.getElementById('select_all_nominees');
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
	});
}

function filterNominees() {
    var searchTerm = document.getElementById('nominee_search').value.toLowerCase().trim();
    var nomineeItems = document.querySelectorAll('.nominee-item');
    var clearButton = document.getElementById('clear_nominee_search');
    var searchInfo = document.getElementById('search_results_info');
    var totalNominees = nomineeItems.length;
    var visibleCount = 0;
    
	if (searchTerm.length > 0) {
        clearButton.style.display = 'flex';
        clearButton.style.visibility = 'visible';
        clearButton.classList.add('show-clear');
        clearButton.classList.remove('hide-clear');
    } else {
        clearButton.style.display = 'none';
        clearButton.style.visibility = 'hidden';
        clearButton.classList.add('hide-clear');
        clearButton.classList.remove('show-clear');
    }
    
	nomineeItems.forEach(function(item, index) {
        var nomineeText = item.textContent.toLowerCase();
        var checkbox = item.querySelector('.nominee-checkbox');
        
        if (searchTerm === '' || nomineeText.includes(searchTerm)) {
          	item.style.display = 'flex';
            item.style.visibility = 'visible';
            item.classList.remove('filtered-out');
            if (checkbox) {
                checkbox.classList.remove('filtered-out');
            }
            visibleCount++;
        } else {
         	item.style.display = 'none';
            item.style.visibility = 'hidden';
            item.classList.add('filtered-out');
            if (checkbox) {
                checkbox.classList.add('filtered-out');
              	if (checkbox.checked) {
                    checkbox.checked = false;
                }
            }
        }
    });
    
  	if (searchTerm.length > 0) {
        searchInfo.style.display = 'block';
        if (visibleCount === 0) {
            searchInfo.innerHTML = '<i class="fa-solid fa-exclamation-triangle text-warning me-1"></i>No nominees found matching "' + 
                                  escapeHtml(searchTerm) + '"';
            searchInfo.className = 'small text-warning mt-2';
        } else {
            searchInfo.innerHTML = '<i class="fa-solid fa-filter text-info me-1"></i>Showing ' + visibleCount + 
                                  ' of ' + totalNominees + ' nominees';
            searchInfo.className = 'small text-info mt-2';
        }
    } else {
        searchInfo.style.display = 'none';
    }
    
	updateBulkDeleteButton();
}

function clearNomineeSearch() {
    var searchInput = document.getElementById('nominee_search');
    var clearButton = document.getElementById('clear_nominee_search');
    var searchInfo = document.getElementById('search_results_info');
    var nomineeItems = document.querySelectorAll('.nominee-item');
    
 	searchInput.value = '';
 	clearButton.style.display = 'none';
    clearButton.style.visibility = 'hidden';
    clearButton.classList.add('hide-clear');
    clearButton.classList.remove('show-clear');
    searchInfo.style.display = 'none';
    
 	nomineeItems.forEach(function(item) {
        item.style.display = 'flex';
        item.style.visibility = 'visible';
        item.classList.remove('filtered-out');
        var checkbox = item.querySelector('.nominee-checkbox');
        if (checkbox) {
            checkbox.classList.remove('filtered-out');
        }
    });
    
 	updateBulkDeleteButton();
	searchInput.focus();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

$(document).ready(function() {
    VirtualSelect.init({  
        ele: '#spotlight_nominees'
    });
    
 	updateBulkDeleteButton();
    
  	var searchInput = document.getElementById('nominee_search');
    var clearButton = document.getElementById('clear_nominee_search');
    
    if (searchInput) {
     	searchInput.addEventListener('input', function() {
            setTimeout(filterNominees, 50);
        });
        
     	searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                clearNomineeSearch();
            }
        });
        
    	searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                filterNominees();
            }
        });
    }
    
    if (clearButton) {
     	clearButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearNomineeSearch();
        });
        
     	clearButton.addEventListener('mouseenter', function() {
            if (searchInput && searchInput.value.trim().length > 0) {
                this.style.display = 'flex';
                this.style.visibility = 'visible';
            }
        });
    }
});
</script>