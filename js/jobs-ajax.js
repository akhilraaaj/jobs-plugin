jQuery(document).ready(function($) {
    // Hide the preview container initially
    $('#application-preview').hide();

    // Submit job application via AJAX
    $('.job-application-form').submit(function(e) {
        e.preventDefault();

        var form = $(this);
        var formData = form.serialize();

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
                    preview += '<p><strong>Name:</strong> ' + response.applicant_Name + '</p>';
                    preview += '<p><strong>Email:</strong> ' + response.applicant_Email + '</p>';
                    preview += '<p><strong>Message:</strong> ' + response.message + '</p>';
                    preview += '<p class="success">Application Submitted Successfully!</p>';
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

    // Delete job application via AJAX
    $(document).on('click', '.application_del_btn', function() {
        var job_id = $(this).data('job-id');
        var application_index = $(this).data('application-index');
        var name = $(this).data('applicant-name');
        var email = $(this).data('applicant-email');

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'delete_job_application',
                job_id: job_id,
                application_index: application_index,
                applicant_Name: name,
                applicant_Email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#job_applications_meta_box').html(response.meta_box_content);
                    alert(response.message);
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
