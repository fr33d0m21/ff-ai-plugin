jQuery(document).ready(function($) {
    window.ffai_run_data_sync = function() {
        $.ajax({
            url: ffai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ffai_run_data_sync',
                nonce: ffai_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while syncing data.');
            }
        });
    };
});