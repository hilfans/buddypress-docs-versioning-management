<?php
/**
 * BuddyPress Docs Categories
 *
 * Adds category taxonomy for BuddyPress Docs and functionality to display document count per category.
 *
 * @package BuddyPress_Docs_Versioning_Management
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom taxonomy for document categories
 */
function bp_docs_register_category_taxonomy() {
    $labels = array(
        'name'              => _x('Document Categories', 'taxonomy general name', 'buddypress-docs-versioning-management'),
        'singular_name'     => _x('Document Category', 'taxonomy singular name', 'buddypress-docs-versioning-management'),
        'search_items'      => __('Search Document Categories', 'buddypress-docs-versioning-management'),
        'all_items'         => __('All Document Categories', 'buddypress-docs-versioning-management'),
        'parent_item'       => __('Parent Document Category', 'buddypress-docs-versioning-management'),
        'parent_item_colon' => __('Parent Document Category:', 'buddypress-docs-versioning-management'),
        'edit_item'         => __('Edit Document Category', 'buddypress-docs-versioning-management'),
        'update_item'       => __('Update Document Category', 'buddypress-docs-versioning-management'),
        'add_new_item'      => __('Add New Document Category', 'buddypress-docs-versioning-management'),
        'new_item_name'     => __('New Document Category Name', 'buddypress-docs-versioning-management'),
        'menu_name'         => __('Document Categories', 'buddypress-docs-versioning-management'),
    );

    $args = array(
        'hierarchical'      => true, // Like post categories, allows parent-child relationships
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'doc-category'),
        'show_in_rest'      => true, // Enable REST API support for Gutenberg compatibility
    );

    register_taxonomy('bp_doc_category', array('bp_doc'), $args);
}
add_action('init', 'bp_docs_register_category_taxonomy');

/**
 * Display document count per category in the frontend
 */
function bp_docs_display_category_counts() {
    $terms = get_terms(array(
        'taxonomy'   => 'bp_doc_category',
        'hide_empty' => true,
    ));

    if (!empty($terms) && !is_wp_error($terms)) {
        echo '<div class="bp-docs-category-counts">';
        echo '<h3>' . __('Document Categories', 'buddypress-docs-versioning-management') . '</h3>';
        echo '<ul>';
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            $doc_count = $term->count;
            echo '<li><a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a> (' . esc_html($doc_count) . ')</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}
add_action('bp_docs_before_docs_loop', 'bp_docs_display_category_counts');

/**
 * Add basic styling for category counts display
 */
function bp_docs_enqueue_category_styles() {
    wp_enqueue_style('bp-docs-categories', plugins_url('css/bp-docs-categories.css', __DIR__));
}
add_action('wp_enqueue_scripts', 'bp_docs_enqueue_category_styles');
?>
