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
        echo $args['before_title'] . 'Latest Jobs' . $args['after_title'];
        // Query jobs
        $jobs_query = new WP_Query( array(
            'post_type'      => 'jobs',
        ) );
        // Display jobs
        if ( $jobs_query->have_posts() ) {
            echo '<ul>';
            while ( $jobs_query->have_posts() ) {
                $jobs_query->the_post();
                echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
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
?>
