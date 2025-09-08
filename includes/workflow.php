<?php
/**
 * BuddyPress Docs Approval Workflow.
 * Handles document statuses: Draft, Approved, Rejected.
 *
 * @package BuddyPressDocs\Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constants for doc statuses.
 */
define( 'BP_DOCS_STATUS_DRAFT', 'draft' );
define( 'BP_DOCS_STATUS_APPROVED', 'approved' );
define( 'BP_DOCS_STATUS_REJECTED', 'rejected' );


/**
 * Set the initial status of a new Doc to 'Draft' unless created by an admin.
 * This hooks into the post save action.
 *
 * @param int     $post_id The ID of the post being saved.
 * @param WP_Post $post    The post object.
 * @param bool    $update  Whether this is an update to an existing post.
 */
function bp_docs_set_initial_status_on_create( $post_id, $post, $update ) {
	// Only act on the 'bp_doc' post type and only on creation.
	if ( 'bp_doc' !== $post->post_type || $update ) {
		return;
	}

	// If a status is already set (eg, by an import), don't override.
	if ( get_post_meta( $post_id, 'bp_doc_status', true ) ) {
		return;
	}

    $user_id = bp_loggedin_user_id();
    $group_id = bp_docs_get_associated_group_id( $post_id );

    // Admins can auto-approve their own documents.
    if ( current_user_can( 'bp_docs_manage_in_group', $group_id ) ) {
        update_post_meta( $post_id, 'bp_doc_status', BP_DOCS_STATUS_APPROVED );
    } else {
        // Regular members' docs start as Draft.
	    update_post_meta( $post_id, 'bp_doc_status', BP_DOCS_STATUS_DRAFT );

        // Send notification to group admins.
        bp_docs_notify_admins_of_pending_doc( $post_id );
    }
}
add_action( 'save_post', 'bp_docs_set_initial_status_on_create', 10, 3 );


/**
 * Handles the approval/rejection actions from the frontend.
 */
function bp_docs_handle_status_change_action() {
    if ( ! isset( $_POST['bp_docs_status_nonce'] ) || ! wp_verify_nonce( $_POST['bp_docs_status_nonce'], 'bp_docs_status_change' ) ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    $doc_id = isset( $_POST['doc_id'] ) ? intval( $_POST['doc_id'] ) : 0;
    if ( ! $doc_id ) {
        return;
    }

    $group_id = bp_docs_get_associated_group_id( $doc_id );

    // Check permissions.
    if ( ! current_user_can( 'bp_docs_manage_in_group', $group_id ) ) {
        bp_core_add_message( __( 'You do not have permission to moderate documents in this group.', 'buddypress-docs' ), 'error' );
        bp_core_redirect( get_permalink( $doc_id ) );
        return;
    }

    $redirect_url = get_permalink( $doc_id );

    // Handle Approve action.
    if ( isset( $_POST['bp_docs_approve'] ) ) {
        update_post_meta( $doc_id, 'bp_doc_status', BP_DOCS_STATUS_APPROVED );
        update_post_meta( $doc_id, 'bp_doc_rejection_reason', '' ); // Clear rejection reason.

        // Record activity.
        bp_docs_record_activity_on_approve( $doc_id );

        // ** DigiWuz MSP ENHANCEMENT: Fire hook for audit log **
        do_action( 'bp_docs_doc_approved', $doc_id );

        bp_core_add_message( __( 'Document has been approved and is now visible to group members.', 'buddypress-docs' ) );
    }
    // Handle Reject action.
    elseif ( isset( $_POST['bp_docs_reject'] ) ) {
        $reason = isset( $_POST['rejection_reason'] ) ? sanitize_textarea_field( $_POST['rejection_reason'] ) : '';
        update_post_meta( $doc_id, 'bp_doc_status', BP_DOCS_STATUS_REJECTED );
        update_post_meta( $doc_id, 'bp_doc_rejection_reason', $reason );

        // Record activity.
        bp_docs_record_activity_on_reject( $doc_id, $reason );

        // ** DigiWuz MSP ENHANCEMENT: Fire hook for audit log **
        do_action( 'bp_docs_doc_rejected', $doc_id, $reason );

        bp_core_add_message( __( 'Document has been rejected.', 'buddypress-docs' ) );
    }

    bp_core_redirect( $redirect_url );
}
add_action( 'bp_template_redirect', 'bp_docs_handle_status_change_action' );


/**
 * Send a BuddyPress notification to group admins about a new pending doc.
 *
 * @param int $doc_id The ID of the document.
 */
function bp_docs_notify_admins_of_pending_doc( $doc_id ) {
    $group_id = bp_docs_get_associated_group_id( $doc_id );
    if ( ! $group_id || ! function_exists( 'bp_notifications_add_notification' ) ) {
        return;
    }

    $author_id = get_post_field( 'post_author', $doc_id );
    $author_link = bp_core_get_userlink( $author_id );
    $doc_title = get_the_title( $doc_id );
    $doc_link = get_permalink( $doc_id );

    $admins = groups_get_group_admins( $group_id );

    foreach ( $admins as $admin ) {
        if ( $admin->user_id == $author_id ) continue; // Don't notify the author.

        bp_notifications_add_notification( array(
            'user_id'           => $admin->user_id,
            'item_id'           => $doc_id,
            'component_name'    => buddypress()->bp_docs->id,
            'component_action'  => 'new_pending_doc',
            'secondary_item_id' => $author_id,
            'text'              => sprintf( '%s submitted a new document "%s" for review.', $author_link, $doc_title ),
        ) );
    }
}


/**
 * Get the current status of a Doc.
 *
 * @param int $doc_id The ID of the document.
 * @return string The status ('draft', 'approved', 'rejected'). Defaults to 'approved'.
 */
function bp_docs_get_doc_status( $doc_id = 0 ) {
    if ( ! $doc_id ) {
        $doc_id = get_the_ID();
    }
    $status = get_post_meta( $doc_id, 'bp_doc_status', true );

    // For backward compatibility, if no status is set, assume it's approved.
    if ( ! $status ) {
        $status = BP_DOCS_STATUS_APPROVED;
    }

    return $status;
}

