jQuery(document).ready(function($) {
    // Hide the preview container initially
    $('#application-preview').hide();
    // Submit job application via AJAX
    $('.job-application-form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();

        //AJAX call to prevent page reload
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
                    preview += '<p><strong>Name:</strong> ' + response.applicant_name + '</p>'; // Use 'applicant_name' instead of 'applicant_Name'
                    preview += '<p><strong>Email:</strong> ' + response.applicant_email + '</p>'; // Use 'applicant_email' instead of 'applicant_Email'
                    preview += '<p><strong>Message:</strong> ' + response.message + '</p>';
                    preview += '<p class="success" style="color: #28a745;"><strong>Application Submitted Successfully!</strong></p>';
                    $('#application-preview').html(preview);
                    // Clear form fields
                    form.find('input[type="text"], input[type="email"], textarea').val('');
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
