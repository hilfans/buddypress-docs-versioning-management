<?php
/**
 * Deprecated.  Use single/edit.php instead.
 *
 * @package BuddyPress_Docs
 */

_deprecated_file( esc_html( wp_basename( __FILE__ ) ), '1.2', esc_html( BP_DOCS_INCLUDES_PATH_ABS . 'templates/docs/single/edit.php' ) );
require_once ( BP_DOCS_INCLUDES_PATH . 'templates/docs/single/edit.php');
?>
<div id="bp-docs-edit-form-options">
    <h4><?php _e( 'Settings', 'buddypress-docs' );?></h4>

    <div class="form-option">
        <label for="bp_docs_cat"><?php _e( 'Category', 'buddypress-docs' ); ?></label>
        <?php
        // ** DigiWuz MSP ENHANCEMENT: Add category dropdown **
        $doc_id = get_the_ID();
        wp_dropdown_categories( array(
            'taxonomy'         => BP_DOCS_CATEGORY_TAXONOMY,
            'name'             => 'bp_docs_cat',
            'id'               => 'bp_docs_cat',
            'show_option_none' => __( 'Select a category', 'buddypress-docs' ),
            'hierarchical'     => true,
            'selected'         => $doc_id ? wp_get_object_terms( $doc_id, BP_DOCS_CATEGORY_TAXONOMY, array( 'fields' => 'ids', 'orderby' => 'term_id' ) ) : 0,
        ) );
        ?>
    </div>

    <?php if ( bp_docs_current_user_can_associate_with_group() ) : ?>
        <div class="form-option">
            <label for="bp-docs-associated-group"><?php _e( 'Associated Group', 'buddypress-docs' ); ?></label>
            <?php bp_docs_groups_dropdown(); ?>
        </div>
    <?php endif; ?>
