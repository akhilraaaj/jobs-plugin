<?php

// Register applicants type for jobs
// Register applicants type for jobs
function applicants_child_post_type() {
    global $wpdb;

    // Create applicants table if not exists
    $table_name = $wpdb->prefix . 'applicants';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            applicant_name varchar(255) NOT NULL,
            applicant_email varchar(255) NOT NULL,
            job_id mediumint(9) NOT NULL,
            job_name varchar(255) NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Register post type for applicants
    $labels = array(
        'name'               => 'Applicants',
        'singular_name'      => 'Applicant',
        'menu_name'          => 'Applicants',
        'name_admin_bar'     => 'Applicant',
        'add_new'            => 'Add New Applicant',  
        'add_new_item'       => 'Add New Applicant',
        'new_item'           => 'New Applicant',
        'edit_item'          => 'Edit Applicant',
        'view_item'          => 'View Application',
        'all_items'          => 'All Applicants',
        'search_items'       => 'Search Applicant',
        'parent_item_colon'  => 'Parent Applicant:',
        'not_found'          => 'No applicants found.',
        'not_found_in_trash' => 'No applicants found in Trash.'
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=jobs',
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'applicants' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
    );
    register_post_type( 'applicants', $args );

    // Add meta box to display job name
    add_action('add_meta_boxes', 'add_applicant_job_meta_box');
}

add_action( 'init', 'applicants_child_post_type' );



// Function to add meta box for job name
function add_applicant_job_meta_box() {
    add_meta_box(
        'applicant_job_meta_box',
        'Job Applied For',
        'display_applicant_job_meta_box',
        'applicants',
        'normal',
        'high'
    );

    add_meta_box(
        'applicant_status_meta_box',
        'Application Status',
        'display_applicant_status_meta_box',
        'applicants',
        'side',
        'default'
    );
}

// Function to display job name in meta box
function display_applicant_job_meta_box($post) {
    $job_id = get_post_meta($post->ID, 'job_id', true);
    if (!empty($job_id)) {
        $job_title = get_the_title($job_id);
        echo '<p><strong>Job Name:</strong> ' . $job_title . '</p>';
    } else {
        echo '<p>No job information found for this applicant.</p>';
    }
}

// Function to display status dropdown in meta box
function display_applicant_status_meta_box($post) {
    $status = get_post_meta($post->ID,'status', true);
    ?>
    <div style="padding: 8px;">
        <label for="applicant_status"><strong>Status:</strong></label>
        <select id="applicant_status" name="applicant_status" style="margin-top: 10px; width: 100%">
            <option value="pending" <?php selected( $status, 'pending' ); ?>>Pending</option>
            <option value="rejected" <?php selected( $status, 'rejected' ); ?>>Rejected</option>
            <option value="selected" <?php selected( $status, 'selected' ); ?>>Selected</option>
            <option value="reviewed" <?php selected( $status, 'reviewed' ); ?>>Reviewed</option>
            <option value="shortlisted" <?php selected( $status, 'shortlisted' ); ?>>Shortlisted</option>
        </select>
    </div>
    <?php
}

// Save the selected status when the post is updated
function save_applicant_status_meta_box($post_id) {
    if (isset($_POST['applicant_status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['applicant_status']));
    }
}
add_action('save_post', 'save_applicant_status_meta_box');


// Add custom column to display job name in All Applicants section
function add_applicant_custom_columns($columns) {
    $columns['title'] = 'Name';
    $columns['date'] = 'Date Applied';
    $columns['job_name'] = 'Position';
    $columns['applicant_email'] = 'Email';
    $columns['status'] = 'Status';
    return $columns;
}
add_filter('manage_applicants_posts_columns', 'add_applicant_custom_columns');

// Populate custom column with job name data
function populate_applicant_custom_columns($column, $post_id) {
    if ($column == 'job_name') {
        $job_id = get_post_meta($post_id, 'job_id', true);
        if (!empty($job_id)) {
            $job_title = get_the_title($job_id);
            echo $job_title;
        } else {
            echo 'N/A';
        }
    } elseif ($column == 'applicant_email') {
        $applicant_email = get_post_meta($post_id, 'applicant_email', true);
        if (!empty($applicant_email)) {
            echo $applicant_email;
        } else {
            echo 'N/A';
        }
    } elseif ($column == 'status') {
        $status = get_post_meta($post_id,'status', true);
        if (!empty($status)) {
            echo $status;
        } else {
            echo 'N/A';
        }
    }
}
add_action('manage_applicants_posts_custom_column', 'populate_applicant_custom_columns', 10, 2);



