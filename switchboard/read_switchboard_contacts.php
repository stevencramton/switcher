<?php
session_start();
include '../../mysqli_connect.php';
include '../../templates/functions.php';

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("");
}

if (!checkRole('switchboard_view')) {
    header("Location:../../index.php?msg1");
	exit();
}
?>

<script>
$(document).ready(function(){
	$(".copy_contact_data_btn").on("click", function(){
		var contact_copy_btn = $(this).text();
		copyToClipboard(contact_copy_btn);
		
		var toastContactTrigger = document.getElementsByClassName("copy_contact_data_btn");
		var toastLiveExample = document.getElementById("toast-contact-copy-2")

		if (toastContactTrigger) {
			var toast = new bootstrap.Toast(toastLiveExample);
			toast.show()
	   	}
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

<?php
if(isset($_SESSION['id'])) {
    if(checkRole('switchboard_contacts')) {
        $data = '<script>
            $("#switchboard_table").DataTable({
                "drawCallback": function() {
                    $(".copy_contact_data_btn").on("click", function() {
                        var contact_copy_btn = $(this).text();
                        copyToClipboard(contact_copy_btn);
                        var toastContactTrigger = document.getElementsByClassName("copy_contact_data_btn");
                        var toastLiveExample = document.getElementById("toast-contact-copy-2");
                        if (toastContactTrigger) {
                            var toast = new bootstrap.Toast(toastLiveExample);
                            toast.show();
                        }
                    });
                    $("input:checkbox").click(function(settings) {
                        if ($(this).is(":checked")) {
                            $("#editContactBtn, #deleteContactBtn").prop("disabled", false);
                        } else {
                            if ($(".chk-box-contact-select").filter(":checked").length < 1) {
                                $("#editContactBtn, #deleteContactBtn").attr("disabled", true);
                            }
                        }
                    });
                },
                "autoWidth": false,
                aLengthMenu: [
                    [100, 200, -1],
                    [100, 200, "All"]
                ],
                responsive: true,
                order: [[2, "asc"]],
                columnDefs: [
                    { "orderable": false, "targets": [0, 1] }
                ]
            });
        </script>';
    } else {
        $data = '<script>
            $("#switchboard_table").DataTable({
                order: [[1, "asc"]],
                responsive: true,
                columnDefs: [
                    { "orderable": false, "targets": 0 }
                ]
            });
        </script>';
    }

    $data .= '<div class="card-body p-0">
        <div class="table-responsive p-1">
            <table class="table table-striped table-hover no-wrap" id="switchboard_table">
                <thead>';
    if(checkRole('switchboard_contacts')) {
        $data .= '<th>
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input select-all-contacts" id="select-all-contacts">
                <label class="form-check-label" for="select-all-contacts"></label>
            </div>
        </th>';
    }

    $data .= '<th></th>
        <th>Name</th>
        <th>Extension</th>
        <th>Cell</th>
        <th>Email</th>
        <th>Department</th>
        <th>Location</th>
        <th>Agency</th>
        <th>Notes</th>
    </thead>
    <tbody>';

	if(isset($_GET['cat_id'])) {
	    $cat_id = strip_tags($_GET['cat_id']);
    
	    $query = "SELECT sc.*, uss.search_cell as cell_visibility 
	              FROM switchboard_contacts sc
	              LEFT JOIN user_settings_search uss ON sc.switch_id = uss.user_settings_switch_id
	              WHERE sc.switchboard_cat_id = ?";
	    $stmt = mysqli_prepare($dbc, $query);
	    mysqli_stmt_bind_param($stmt, 'i', $cat_id);
	    mysqli_stmt_execute($stmt);
	    $result = mysqli_stmt_get_result($stmt);
	} else {
	    $query = "SELECT sc.*, uss.search_cell as cell_visibility 
	              FROM switchboard_contacts sc
	              LEFT JOIN user_settings_search uss ON sc.switch_id = uss.user_settings_switch_id";
	    $result = mysqli_query($dbc, $query);
	}

    if($result) {
        while($row = mysqli_fetch_array($result)) {
			$switchboard_id = htmlspecialchars($row['switchboard_id'] ?? '');
			$first_name = htmlspecialchars($row['first_name'] ?? '');
			$last_name = htmlspecialchars($row['last_name'] ?? '');
            
			if(empty($row['first_name'])) {
                $name = htmlspecialchars($row['department']);
            } else {
                $name = $first_name . ' ' . $last_name;
            }

            $data .= '<tr>';
            if(checkRole('switchboard_contacts')) {
                $data .= '<td class="align-middle" style="width:3%">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input chk-box-contact-select" id="switchboard_' . $switchboard_id . '" data-contact-id="' . $switchboard_id . '">
                        <label class="form-check-label" for="switchboard_' . $switchboard_id . '"></label>
                    </div>
                </td>';
            }
            $data .= '<td class="align-middle" style="width:3%">
                <button type="button" class="btn" onclick="readSwitchboardContactCard(' . $switchboard_id . ')"><i class="far fa-address-card"></i></button>
            </td>
            <td class="align-middle copy_contact_data_btn" role="button" style="width:30%">' . $name . '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['extension'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['extension'] . '</span>';
            }
            $data .= '</td>';

			$data .= '<td class="align-middle" style="width:15%;">';
			
			$cell_visibility = $row['cell_visibility'] ?? 1;
			
			if($cell_visibility == 1 && !empty($row['cell'])) {
			    $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . htmlspecialchars($row['cell']) . '</span>';
			} else {
			    $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
			}
			$data .= '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['email'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['email'] . '</span>';
            }
            $data .= '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['department'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['department'] . '</span>';
            }
            $data .= '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['area_location'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['area_location'] . '</span>';
            }
            $data .= '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['area_agency'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['area_agency'] . '</span>';
            }
            $data .= '</td>';

            $data .= '<td class="align-middle">';
            if(empty($row['switchboard_note'])) {
                $data .= '<i class="fa-solid fa-circle-question dark-gray"></i>';
            } else {
                $data .= '<span class="copy_contact_data_btn" role="button" style="width:30%">' . $row['switchboard_note'] . '</span>';
            }
            $data .= '</td></tr>';
        }
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
    }

    $data .= '</tbody>
        </table>
    </div>
</div>';

    echo $data;

    mysqli_close($dbc);
}
?>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 999999 !important;">
	<div id="toast-contact-copy-2" class="toast bg-solid-white" role="alert" aria-live="assertive" aria-atomic="true">
		<div class="toast-header bg-dark text-white">
    		<strong class="me-auto">Success</strong>
    		<small></small>
    		<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
		<div class="toast-body">
			<span class="align-middle dark-gray">Item has been copied to clipboard! <i class="fa-solid fa-circle-check float-end" style="color:#34a853;font-size:20px;"></i></span>
		</div>
	</div>
</div>
	
<script>
$(document).ready(function() {
	$('.select-all-contacts').on('click', function(e) {
		if ($(this).is(':checked',true)) {
   		 	$(".chk-box-contact-select").prop('checked', true);
		} else {
      	  	$(".chk-box-contact-select").prop('checked',false);
		}
	});
	$(".chk-box-contact-select").on('click', function(e) {
   	 	if ($(this).is(':checked',true)) {
			$(".select-all-contacts").prop("checked", false);
		} else {
			$(".select-all-contacts").prop("checked", false);
	 	}
		if ($(".chk-box-contact-select").not(':checked').length == 0) {
			$(".select-all-contacts").prop("checked", true);
		}
	});
	$('#editContactBtn, #deleteContactBtn').prop("disabled", true);
	if ($('.chk-box-contact-select').is(':checked',true)) {
		$('#editContactBtn, #deleteContactBtn').prop("disabled", false);
	} else {
		$('#editContactBtn, #deleteContactBtn').prop("disabled", true);
	}
	$('input:checkbox').click(function() {
		if ($(this).is(':checked')) {
			$('#editContactBtn, #deleteContactBtn').prop("disabled", false);
		} else {
		if ($('.chk-box-contact-select').filter(':checked').length < 1){
			$('#editContactBtn, #deleteContactBtn').attr('disabled',true);}
		}
	});
});
</script>