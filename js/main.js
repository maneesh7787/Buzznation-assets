// Main JavaScript file for Asset Management System

$(document).ready(function() {
    // Initialize DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search..."
            }
        });
    }

    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        var form = $(this);
        if (form.hasClass('needs-validation')) {
            if (!form[0].checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.addClass('was-validated');
        }
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').fadeOut('slow');
    }, 5000);

    // Show loading spinner on form submit
    $('form').on('submit', function() {
        if ($(this).hasClass('no-spinner')) {
            return;
        }
        showSpinner();
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Password confirmation validation
    $('#confirm_password').on('keyup', function() {
        var password = $('#new_password').val();
        var confirmPassword = $(this).val();
        
        if (password !== confirmPassword) {
            $('#password-match-message').show();
            $(this).addClass('is-invalid');
        } else {
            $('#password-match-message').hide();
            $(this).removeClass('is-invalid');
        }
    });
});

// Show loading spinner
function showSpinner() {
    $('body').append('<div class="spinner-overlay"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

// Hide loading spinner
function hideSpinner() {
    $('.spinner-overlay').remove();
}

// Dynamic asset form fields for employee asset submission
var assetCounter = 1;

function addAssetField() {
    assetCounter++;
    var html = `
        <div class="asset-item" id="asset-${assetCounter}">
            <div class="asset-item-header">
                <h6 class="mb-0">Asset #${assetCounter}</h6>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeAssetField(${assetCounter})">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Asset Type *</label>
                    <select class="form-select" name="asset_type[]" required>
                        <option value="">Select Asset Type</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" class="form-control" name="brand[]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control" name="serial_number[]">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Condition *</label>
                    <select class="form-select" name="condition[]" required>
                        <option value="excellent">Excellent</option>
                        <option value="good" selected>Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
            </div>
        </div>
    `;
    $('#assets-container').append(html);
    
    // Load categories for the new field
    loadCategoriesForField(assetCounter);
}

function removeAssetField(id) {
    if (confirm('Remove this asset?')) {
        $('#asset-' + id).remove();
    }
}

function loadCategoriesForField(fieldId) {
    $.ajax({
        url: '/api/get-categories.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var select = $('#asset-' + fieldId + ' select[name="asset_type[]"]');
                select.empty().append('<option value="">Select Asset Type</option>');
                response.categories.forEach(function(cat) {
                    select.append('<option value="' + cat.id + '">' + cat.category_name + '</option>');
                });
            }
        }
    });
}

// Export table to CSV
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length - 1; j++) { // Exclude last column (actions)
            var cellText = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + cellText + '"');
        }
        
        csv.push(row.join(","));
    }
    
    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    csvFile = new Blob([csv], {type: "text/csv"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
