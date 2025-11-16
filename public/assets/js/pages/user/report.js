function exportReport() {
    const startDate = $('input[name="startDate"]').val();
    const endDate = $('input[name="endDate"]').val();
    
    if (!startDate || !endDate) {
        NotifyAlert('error', null, 'Please select both start and end dates!');
        return;
    }

    // Show loading state
    const exportBtn = $('#exportReportBtn');
    const searchBtn = $('#searchReportBtn');
    const originalText = exportBtn.html();
    exportBtn.html('Exporting...').prop('disabled', true);
    searchBtn.prop('disabled', true);
    NotifyAlert('info', null, 'Starting export...');

    // Setup CSRF token
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Make POST request
    $.ajax({
        type: 'POST',
        url: $('#base_url').val() + '/user/report/export',
        data: {
            startDate: startDate,
            endDate: endDate
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(response, status, xhr) {
            // Create download link
            const blob = new Blob([response], { type: xhr.getResponseHeader('Content-Type') });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reports_${startDate}_${endDate}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            NotifyAlert('success', null, 'Report exported successfully!');
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            NotifyAlert('error', null, 'Failed to export report. Please try again.');
        },
        complete: function() {
            // Reset button state
            exportBtn.html(originalText).prop('disabled', false);
            searchBtn.prop('disabled', false);
        }
    });
}

function searchReport() {
    const searchBtn = $('#searchReportBtn');
    const exportBtn = $('#exportReportBtn');
    searchBtn.html('Searching...').prop('disabled', true);
    exportBtn.prop('disabled', true);
}

$(document).on('click', '#exportReportBtn', function () {
    exportReport();
})

$(document).on('click', '#searchReportBtn', function () {
    const startDate = $('input[name="startDate"]').val();
    const endDate = $('input[name="endDate"]').val();
    
    if (!startDate || !endDate) {
        NotifyAlert('error', null, 'Please select both start and end dates!');
    }
})

$(document).on('submit', '#reportForm', function () {
    searchReport();
})
