<?php
/**
 * BuddyPress Docs Category Functions.
 * Handles the hierarchical category taxonomy for documents.
 *
 * @package BuddyPressDocs\Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BP_DOCS_CATEGORY_TAXONOMY', 'bp_docs_category' );

/**
 * Register the hierarchical taxonomy for Doc Categories.
 */
function bp_docs_register_category_taxonomy() {
	$labels = array(
		'name'              => _x( 'Document Categories', 'taxonomy general name', 'buddypress-docs' ),
		'singular_name'     => _x( 'Category', 'taxonomy singular name', 'buddypress-docs' ),
		'search_items'      => __( 'Search Categories', 'buddypress-docs' ),
		'all_items'         => __( 'All Categories', 'buddypress-docs' ),
		'parent_item'       => __( 'Parent Category', 'buddypress-docs' ),
		'parent_item_colon' => __( 'Parent Category:', 'buddypress-docs' ),
		'edit_item'         => __( 'Edit Category', 'buddypress-docs' ),
		'update_item'       => __( 'Update Category', 'buddypress-docs' ),
		'add_new_item'      => __( 'Add New Category', 'buddypress-docs' ),
		'new_item_name'     => __( 'New Category Name', 'buddypress-docs' ),
		'menu_name'         => __( 'Categories', 'buddypress-docs' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'docs-category' ),
	);

	register_taxonomy( BP_DOCS_CATEGORY_TAXONOMY, array( 'bp_doc' ), $args );
}
add_action( 'init', 'bp_docs_register_category_taxonomy', 0 );

/**
 * Log when a document's categories are changed.
 *
 * @param int    $object_id  Object ID.
 * @param array  $terms      An array of object terms.
 * @param array  $tt_ids     An array of term taxonomy IDs.
 * @param string $taxonomy   Taxonomy slug.
 * @param bool   $append     Whether to append new terms to the old terms.
 * @param array  $old_tt_ids Old array of term taxonomy IDs.
 */
function bp_docs_log_category_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
    // Only log for our custom taxonomy and post type
    if ( BP_DOCS_CATEGORY_TAXONOMY !== $taxonomy || 'bp_doc' !== get_post_type( $object_id ) ) {
        return;
    }

    // Don't log on initial creation (the 'doc_created' log covers this).
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['post_status'] ) && 'auto-draft' === $_POST['post_status'] ) {
        return;
    }

    // Prevent logging twice on the same request
    static $logged_posts = array();
    if ( in_array( $object_id, $logged_posts ) ) {
        return;
    }

    $new_term_names = array();
    foreach ( $tt_ids as $tt_id ) {
        $term = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );
        if ( $term ) {
            $new_term_names[] = $term->name;
        }
    }

    bp_docs_add_log_entry( array(
        'doc_id'  => $object_id,
        'action'  => 'category_updated',
        'details' => __( 'Categories set to:', 'buddypress-docs' ) . ' ' . implode( ', ', $new_term_names ),
    ) );

    $logged_posts[] = $object_id;
}
add_action( 'set_object_terms', 'bp_docs_log_category_change', 10, 6 );

