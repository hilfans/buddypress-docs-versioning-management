<?php
/**
 * BuddyPress Docs Activity Functions.
 *
 * @package BuddyPressDocs\Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records activity when a new Doc is created.
 *
 * @param array $doc_args The arguments for the new Doc.
 */
function bp_docs_record_activity_on_create( $doc_args ) {
	if ( ! function_exists( 'bp_activity_add' ) ) {
		return;
	}

    $doc_id = $doc_args['doc_id'];
    $user_id = $doc_args['user_id'];
    $group_id = ! empty( $doc_args['group_id'] ) ? $doc_args['group_id'] : 0;
    $doc_status = bp_docs_get_doc_status($doc_id);

    $user_link = bp_core_get_userlink( $user_id );
    $doc_link = '<a href="' . get_permalink( $doc_id ) . '">' . get_the_title( $doc_id ) . '</a>';

    // ** DigiWuz MSP ENHANCEMENT: Modify action string based on status **
    if ( $doc_status === BP_DOCS_STATUS_DRAFT ) {
        $action = sprintf( __( '%s uploaded a new Doc, %s, which is awaiting approval.', 'buddypress-docs' ), $user_link, $doc_link );
    } else {
	    $action = sprintf( __( '%s created a new Doc %s', 'buddypress-docs' ), $user_link, $doc_link );
    }

	$activity_args = array(
		'user_id'           => $user_id,
		'action'            => $action,
		'primary_link'      => get_permalink( $doc_id ),
		'component'         => 'docs',
		'type'              => 'bp_doc_created',
		'item_id'           => $doc_id,
		'recorded_time'     => bp_core_current_time(),
	);

	if ( $group_id ) {
		$activity_args['component'] = 'groups';
		$activity_args['item_id']   = $group_id;
		$activity_args['secondary_item_id'] = $doc_id;
	}

	bp_activity_add( $activity_args );
}
add_action( 'bp_docs_doc_created', 'bp_docs_record_activity_on_create' );


// ... (Existing functions like bp_docs_record_activity_on_edit, bp_docs_record_activity_on_comment remain here) ...


/**
 * DigiWuz MSP ENHANCEMENT: Records activity when a Doc is approved.
 *
 * @param int $doc_id The ID of the approved Doc.
 */
function bp_docs_record_activity_on_approve( $doc_id ) {
    if ( ! function_exists( 'bp_activity_add' ) ) {
		return;
	}

    $user_id = bp_loggedin_user_id(); // The admin who approved it.
    $group_id = bp_docs_get_associated_group_id( $doc_id );

    $user_link = bp_core_get_userlink( $user_id );
    $doc_link = '<a href="' . get_permalink( $doc_id ) . '">' . get_the_title( $doc_id ) . '</a>';
    $action = sprintf( __( '%s approved the Doc %s', 'buddypress-docs' ), $user_link, $doc_link );

    $activity_args = array(
		'user_id'           => $user_id,
		'action'            => $action,
		'primary_link'      => get_permalink( $doc_id ),
		'component'         => 'docs',
		'type'              => 'bp_doc_approved',
		'item_id'           => $doc_id,
		'recorded_time'     => bp_core_current_time(),
	);

    if ( $group_id ) {
		$activity_args['component'] = 'groups';
		$activity_args['item_id']   = $group_id;
		$activity_args['secondary_item_id'] = $doc_id;
	}

    bp_activity_add( $activity_args );
}


/**
 * DigiWuz MSP ENHANCEMENT: Records activity when a Doc is rejected.
 *
 * @param int    $doc_id The ID of the rejected Doc.
 * @param string $reason The reason for rejection.
 */
function bp_docs_record_activity_on_reject( $doc_id, $reason = '' ) {
    if ( ! function_exists( 'bp_activity_add' ) ) {
		return;
	}

    $user_id = bp_loggedin_user_id(); // The admin who rejected it.
    $group_id = bp_docs_get_associated_group_id( $doc_id );
    $doc_author_id = get_post_field('post_author', $doc_id);

    $user_link = bp_core_get_userlink( $user_id );
    $doc_title = get_the_title( $doc_id ); // No link as it's not accessible.
    $action = sprintf( __( '%s rejected the Doc "%s"', 'buddypress-docs' ), $user_link, $doc_title );

    if ( $reason ) {
        $action .= ' ' . sprintf( __( 'Reason: %s', 'buddypress-docs' ), '<em>' . esc_html( $reason ) . '</em>' );
    }

    // This activity should only be visible to admins and the doc author.
    // We achieve this by NOT associating it with a group and relying on BuddyPress privacy.
    // However, a simpler approach for now is to just log it. A more complex system
    // would involve custom activity privacy.
    // Let's also notify the author.
    if ( function_exists('bp_notifications_add_notification') ) {
        bp_notifications_add_notification( array(
            'user_id'           => $doc_author_id,
            'item_id'           => $doc_id,
            'component_name'    => buddypress()->bp_docs->id,
            'component_action'  => 'doc_rejected',
            'secondary_item_id' => $user_id,
            'text'              => sprintf( 'Your document "%s" was rejected. Reason: %s', $doc_title, $reason ? esc_html($reason) : 'No reason provided.' ),
        ) );
    }
}

