<?php
/*
Plugin Name: Jobs Plugin
Description: A plugin to add jobs and display on frontend as page and widget.
Version: 1.0
Author: Akhil
*/

require_once plugin_dir_path(__FILE__) . 'applicants_child_post_type.php';

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
        'has_archive'        => true,
        'hierarchical'       => true,
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
                <input type="submit" value="Submit Application" class="submit-button">
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

// Function to save applicant data into the 'applicants' table
function save_applicant_data_to_database($applicant_name, $applicant_email, $job_id, $message, $status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'applicants';
    $result = $wpdb->insert(
        $table_name,
        array(
            'applicant_name' => $applicant_name,
            'applicant_email' => $applicant_email,
            'job_id' => $job_id,
            'message' => $message,
            'status' => $status
        ),
        array('%s', '%s', '%d', '%s', '%s')
    );

    // Check for errors
    if ($result === false) {
        $wpdb_error = $wpdb->last_error;
        error_log("Error inserting data into applicants table: $wpdb_error");
        return false;
    }

    return true;
}

// Modify the submit_job_application function to also save data to the 'applicants' table
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

    // Save applicant data to 'applicants' table
    $saved_to_database = save_applicant_data_to_database($name, $email, $jobId, $message, 'pending');

    if ($saved_to_database) {
        // Create new applicant post
        $applicant_post = array(
            'post_title' => $name, // Set applicant name as post title
            'post_type' => 'applicants', // Set post type to applicants
            'post_status' => 'publish', // Set post status to publish
        );

        // Insert the post into the database
        $applicant_post_id = wp_insert_post($applicant_post);

        // Update applicant post meta with submitted data
        if (!is_wp_error($applicant_post_id)) {
            update_post_meta($applicant_post_id, 'applicant_email', $email);
            update_post_meta($applicant_post_id, 'job_id', $jobId);
            update_post_meta($applicant_post_id, 'message', $message);
            update_post_meta($applicant_post_id, 'status', 'pending');

            // Return success response
            echo json_encode(
                array(
                    "status" => "success",
                    "applicant_Name" => $name,
                    "applicant_Email" => $email,
                    "message" => $message,
                    "applicant_post_id" => $applicant_post_id,
                    "job_id" => $jobId 
                )
            );
        } else {
            // Return error response if post creation fails
            echo json_encode(array('status' => 'error', 'message' => 'Failed to submit application'));
        }
    } else {
        // Return error response if saving to database fails
        echo json_encode(array('status' => 'error', 'message' => 'Failed to save applicant data to database'));
    }

    wp_die();
}


add_action('wp_ajax_submit_job_application', 'submit_job_application');
add_action('wp_ajax_nopriv_submit_job_application', 'submit_job_application');

