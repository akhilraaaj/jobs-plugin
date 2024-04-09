jQuery(document).ready(function($) {
    // Hide the preview container initially
    $('#application-preview').hide();

    // Submit job application via AJAX
    $('.job-application-form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();

        // AJAX call to prevent page reload
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#application-preview').show();
                    // Show preview of submitted data
                    var preview = '<h3>Preview of Your Application:</h3>';
                    if (response.already_applied) {
                        preview += '<p class="already-applied" style="color: #ff0000;"><strong>You have already applied for this position!</strong></p>';
                    } else {
                        preview += '<p><strong>Name:</strong> ' + response.applicant_name + '</p>'; 
                        preview += '<p><strong>Email:</strong> ' + response.applicant_email + '</p>'; 
                        preview += '<p><strong>Message:</strong> ' + response.message + '</p>';
                        preview += '<p class="success" style="color: #28a745;"><strong>Application Submitted Successfully!</strong></p>';
                        // Clear form fields
                        form.find('input[type="text"], input[type="email"], textarea').val('');
                    }
                    $('#application-preview').html(preview);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });        
    });
});
