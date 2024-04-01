<?php
/*
Plugin Name: Jobs Plugin
Description: A plugin to add jobs and display on frontend as page and widget.
Version: 1.0
Author: Akhil
*/

// Register custom post type for jobs
function custom_jobs_post_type() {
    $labels = array(
        'name'               => 'Jobs',
        'singular_name'      => 'Job',
        'menu_name'          => 'Jobs',
        'name_admin_bar'     => 'Job',
        'add_new'            => 'Add New Job',  
        'add_new_item'       => 'Add New Job',
        'new_item'           => 'New Job',
        'edit_item'          => 'Edit Job',
        'view_item'          => 'View Job',
        'all_items'          => 'All Jobs',
        'search_items'       => 'Search Jobs',
        'parent_item_colon'  => 'Parent Jobs:',
        'not_found'          => 'No jobs found.',
        'not_found_in_trash' => 'No jobs found in Trash.'
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'jobs' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
        'menu_icon'          => 'dashicons-portfolio'
    );
    register_post_type( 'jobs', $args );
}
add_action( 'init', 'custom_jobs_post_type' );

class Jobs_Widget extends WP_Widget {
    // Widget constructor
    public function __construct() {
        parent::__construct(
            'jobs_widget',
            'Jobs Widget',
            array( 'description' => 'Display jobs on the frontend' )
        );
    }

    // Widget output
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        echo $args['before_title'] . '<h2>Latest Jobs</h2>' . $args['after_title'];

        // Query jobs
        $jobs_query = new WP_Query( array(
            'post_type'      => 'jobs',
        ) );

        // Display jobs
        if ( $jobs_query->have_posts() ) {
            echo '<ul>';
            while ( $jobs_query->have_posts() ) {
                $jobs_query->the_post();
                echo '<li>';
                echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                echo '</li>';
            }
            echo '</ul>';
            // Restore original post data
            wp_reset_postdata();
        } else {
            echo 'No jobs found.';
        }
        echo $args['after_widget'];
    }
}

function register_jobs_widget() {
    register_widget( 'Jobs_Widget' );
}
add_action( 'widgets_init', 'register_jobs_widget' );

function job_application_form($content) {
    global $post;
    if ($post->post_type === 'jobs') {
        $job_id = $post->ID;
        $form_html = '
        <div class="job_application_form">
            <form id="job_application_forms' . $job_id . '" class="job-application-form" data-job-id="' . $job_id . '">
                <label for="applicant_names' . $job_id . '">Name:</label>
                <input type="text" required name="applicant_name" id="applicant_names' . $job_id . '" value=""><br>
                <label for="applicant_emails' . $job_id . '">Email:</label>
                <input type="email" required name="applicant_email" id="applicant_emails' . $job_id . '" value=""><br>
                <label for="messages' . $job_id . '">Message:</label><br>
                <textarea name="message" required id="message_' . $job_id . '" cols="30" rows="5"></textarea><br>
                <input type="hidden" name="job_id" value="' . $job_id . '">
                <input type="hidden" name="action" value="submit_job_application">
                <input type="submit" value="Submit Application">
            </form>
            <div id="application-preview"> </div>
        </div>
        ';
        $content = $content . $form_html;
    }
    return $content;
}
add_filter('the_content', 'job_application_form');

//CSS
function styles_enqueuer() {
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('style', $plugin_url . "/css/jobs-plugin-style.css");
}
add_action('init', 'styles_enqueuer');

//AJAX
function script_enqueuer() {
    wp_register_script("job-application", plugin_dir_url(__FILE__) . '/js/jobs-ajax.js', array('jquery'));
    wp_localize_script('job-application', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_script('jquery');
    wp_enqueue_script('job-application');
}
add_action('init', 'script_enqueuer');

function submit_job_application() {
    $name = isset($_POST['applicant_name']) ? sanitize_text_field($_POST['applicant_name']) : '';
    $email = isset($_POST['applicant_email']) ? sanitize_email($_POST['applicant_email']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    $jobId = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;

    // Validate form data
    if (empty($name) || empty($email) || empty($message) || empty($jobId)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid data'));
        wp_die();
    }

    // get all  applications
    $job_applications = get_post_meta($jobId, 'job_applications', true);
    if (!is_array($job_applications)) {
        $job_applications = array();
    }

    // Create a new application array
    $new_job_application = array(
        "applicant_Name" => $name,
        "applicant_Email" => $email,
        "message" => $message,
        "ID" => $jobId
    );

    $job_applications[] = $new_job_application;
    update_post_meta($jobId, "job_applications", $job_applications);

    echo json_encode(
        array(
            "status" => "success",
            "message" => $message,
            "applicant_Name" => $name,
            "applicant_Email" => $email
        )
    );
    wp_die();
}
add_action('wp_ajax_submit_job_application', 'submit_job_application');
add_action('wp_ajax_nopriv_submit_job_application', 'submit_job_application');

function add_job_application_meta_box() {
    add_meta_box(
        'job_application_meta_box',
        'Job Applications',
        'display_job_application_meta_box',
        'jobs',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_job_application_meta_box');

function display_job_application_meta_box($post) {
    // get job applications
    $job_applications = get_post_meta($post->ID, 'job_applications', true);
    ?>
    <div class="job-applications-container">
        <?php if (!empty($job_applications)): ?>
            <ul>
                <?php foreach ($job_applications as $index => $application): ?>
                    <li>
                        <?php if (is_array($application) && isset($application['applicant_Name']) && isset($application['applicant_Email'])): ?>
                            <strong>Name:</strong>
                            <?php echo esc_html($application['applicant_Name']); ?><br>
                            <strong>Email:</strong>
                            <?php echo esc_html($application['applicant_Email']); ?><br>
                            <?php if (isset($application['message'])): ?>
                                <strong>Message:</strong>
                                <?php echo esc_html($application['message']); ?><br>
                            <?php endif; ?>
                            <input type="button" class="application_del_btn" data-job-id="<?php echo esc_html($post->ID); ?>"
                                data-application-index="<?php echo esc_html($index); ?>"
                                data-applicant-name="<?php echo esc_html($application['applicant_Name']); ?>"
                                data-applicant-email="<?php echo esc_html($application['applicant_Email']); ?>" value="Delete">
                        <?php else: ?>
                            <p>Error: Invalid application data. Index: <?php echo esc_html($index); ?></p>
                            <?php 
                                error_log('Invalid application data at  ' . $index . ': ' . print_r($application, true));
                            ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No applications submitted for this job yet.</p>
        <?php endif; ?>
    </div>
    <?php
}

function delete_job_application() {
    // get form data
    $name = isset($_POST['applicant_Name']) ? sanitize_text_field($_POST['applicant_Name']) : '';
    $email = isset($_POST['applicant_Email']) ? sanitize_email($_POST['applicant_Email']) : '';
    $jobId = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $application_index = isset($_POST['application_index']) ? absint($_POST['application_index']) : 0;

    // Validation
    if (empty($name) || empty($email) || empty($jobId) || $application_index < 0) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid data'));
        wp_die();
    }

    // Get all  applications
    $job_applications = get_post_meta($jobId, 'job_applications', true);

    // Check if the application exists or not
    if (isset($job_applications[$application_index])) {
        if ($job_applications[$application_index]['applicant_Name'] === $name && $job_applications[$application_index]['applicant_Email'] === $email) {
            unset($job_applications[$application_index]);
            update_post_meta($jobId, 'job_applications', $job_applications);
            echo json_encode(array('status' => 'success', 'message' => 'Application deleted successfully'));
            wp_die();
        }
    }

    // If application deletion fails, return error response
    echo json_encode(array('status' => 'error', 'message' => 'Failed to delete application'));
    wp_die();
}
add_action('wp_ajax_delete_job_application', 'delete_job_application');
add_action('wp_ajax_nopriv_delete_job_application', 'delete_job_application');

