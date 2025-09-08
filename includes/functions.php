<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ** DigiWuz MSP ENHANCEMENT: Helper function to get the post type name. **
 *
 * This function was created to restore functionality needed by other files
 * after the main plugin class was refactored.
 *
 * @since 2.2.1
 * @return string The post type name for BuddyPress Docs.
 */
function bp_docs_get_post_type_name() {
    // Ensure the BuddyPress object and the bp_docs component are available.
    if ( isset( buddypress()->bp_docs->post_type_name ) ) {
        return buddypress()->bp_docs->post_type_name;
    }
    // Fallback just in case, though it should be set by the component.
    return 'bp_doc';
}

/**
 * Helper function to determine whether the current page is part of BP Docs
 *
 * @since 1.0-beta
 *
 * @return bool True if the current page is part of BP Docs, otherwise false
 */
function bp_docs_is_bp_docs_page() {
	$is_bp_docs_page = ( bp_is_current_component( BP_DOCS_SLUG ) || bp_is_post_type_archive( bp_docs_get_post_type_name() ) || is_singular( bp_docs_get_post_type_name() ) ) ? true : false;

	return apply_filters( 'bp_docs_is_bp_docs_page', $is_bp_docs_page );
}

/**
 * Get the ID of the default directory page
 *
 * @since 1.0-beta
 *
 * @return int $page_id The ID of the BP Docs directory page, if it exists
 */
function bp_docs_get_page_id() {
	$page_id = isset( buddypress()->pages->{'docs'}->id ) ? buddypress()->pages->{'docs'}->id : 0;
	return $page_id;
}


/**
 * Get the proper URL for a given item in the BP Docs component
 *
 * @since 1.0-beta
 * @param string $item The component item. Either 'directory' or 'slug'
 * @param array $args Miscellaneous arguments to be passed
 * @return str $url The URL of the component item
 */
function bp_docs_get_link( $item, $args = array() ) {

	switch ( $item ) {
		case 'directory' :
			$url = trailingslashit( bp_get_root_domain() . '/' . BP_DOCS_SLUG );
			break;

		case 'slug' :
			$url = trailingslashit( bp_get_root_domain() . '/' . BP_DOCS_SLUG );
			break;
	}

	return apply_filters( 'bp_docs_get_link', $url );
}

/**
 * Returns the URL for a single doc
 *
 * This function handles the logic of figuring out whether the doc is associated with a group,
 * and if so, builds the URL with the group slug in it.
 *
 * @since 1.0-beta
 *
 * @param int $doc_id The id of the doc
 * @return str $link The URL of the doc
 */
function bp_docs_get_doc_link( $doc_id = false ) {
	global $bp;

	if ( !$doc_id ) {
		$doc_id = get_the_ID();
	}

	$doc = get_post( $doc_id );

	if ( empty( $doc->ID ) ) {
		return;
	}

	$permalink = get_permalink( $doc->ID );

	// BP-Default theme needs this to work correctly
	if ( function_exists( 'bp_is_group' ) && bp_is_group() && !empty( $bp->groups->current_group ) ) {
		$group_slug = $bp->groups->current_group->slug;
		$permalink = trailingslashit( bp_get_group_permalink( $bp->groups->current_group ) . BP_DOCS_SLUG . '/' . $doc->post_name );
	}

	return $permalink;
}

/**
 * When a doc is associated with a group, WP doesn't know about it. So we have to build
 * the URLs manually.
 *
 * @since 1.0
 */
function bp_docs_get_doc_slug_in_group_context( $doc_id, $group_id = 0 ) {
	$bp = buddypress();

	if ( ! $group_id ) {
		$group_id = ! empty( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : 0;
	}

	if ( ! $group_id ) {
		return '';
	}

	$group = groups_get_group( array(
		'group_id' => $group_id,
	) );

	$doc = get_post( $doc_id );

	return trailingslashit( bp_get_group_permalink( $group ) . bp_docs_get_slug() . '/' . $doc->post_name );
}

/**
 * Get the current query args for pagination purposes.
 *
 * @since 1.0-beta
 * @return array $query_args
 */
function bp_docs_get_query_args() {
	$query_args = array();

	// Search terms
	if ( isset( $_GET['s'] ) && !empty( $_GET['s'] ) )
		$query_args['s'] = $_GET['s'];

	return $query_args;
}


/**
 * Is the current user allowed to create a new Doc?
 *
 * @since 1.0-beta
 *
 * @param array $args
 * - group_id Find out for a given group. Optional. Defaults to current group
 * @return bool
 */
function bp_docs_current_user_can_create( $args = array() ) {
	$can_create = false;

	if ( is_user_logged_in() ) {
		$can_create = true;
	}

	// In the context of a group, check group permissions as well
	if ( bp_is_active( 'groups' ) ) {
		$group = ! empty( $args['group_id'] ) ? groups_get_group( array( 'group_id' => $args['group_id'] ) ) : groups_get_current_group();

		if ( ! empty( $group->id ) ) {
			// Backwards compatibility for pre-1.2 group settings
			if ( isset( $group->enable_docs ) && !$group->enable_docs ) {
				$can_create = false;

			// Check the new-style group settings
			} else if ( ! bp_docs_is_docs_enabled_for_group( $group->id ) ) {
				$can_create = false;
			}
		}
	}

	return apply_filters( 'bp_docs_current_user_can_create', $can_create );
}


/**
 * Get the slug of the Docs component.
 *
 * @since 1.0-beta
 */
function bp_docs_get_slug() {
	return BP_DOCS_SLUG;
}


/**
 * Is a given user a member of the current group?
 *
 * @since 1.0-beta
 *
 * @param int $user_id The id of the user being checked
 * @return bool $is_member True if the user is a member, otherwise false
 */
function bp_docs_is_existing_member( $user_id ) {
	global $bp;

	if ( empty( $user_id ) )
		return false;

	return groups_is_user_member( $user_id, $bp->groups->current_group->id );
}

/**
 * Echoes the Doc title
 *
 * @since 1.0-beta
 * @uses bp_docs_get_doc_title()
 */
function bp_docs_doc_title() {
	echo bp_docs_get_doc_title();
}
	/**
	 * Returns the Doc title
	 *
	 * @since 1.0-beta
	 */
	function bp_docs_get_doc_title() {
		return apply_filters( 'bp_docs_get_doc_title', get_the_title() );
	}

/**
 * Echoes the Doc permalink
 *
 * @since 1.0-beta
 * @uses bp_docs_get_doc_permalink()
 */
function bp_docs_doc_permalink() {
	echo bp_docs_get_doc_permalink();
}
	/**
	 * Returns the Doc permalink
	 *
	 * @since 1.0-beta
	 */
	function bp_docs_get_doc_permalink() {
		return apply_filters( 'bp_docs_get_doc_permalink', get_permalink() );
	}

/**
 * Echoes the Doc content
 *
 * @since 1.0-beta
 * @uses bp_docs_get_doc_content()
 */
function bp_docs_doc_content() {
	echo bp_docs_get_doc_content();
}
	/**
	 * Returns the Doc content
	 *
	 * @since 1.0-beta
	 */
	function bp_docs_get_doc_content() {
		global $post;
		return apply_filters( 'the_content', $post->post_content );
	}

/**
 * Echoes the Doc edit link
 *
 * @since 1.1
 * @uses bp_docs_get_doc_edit_link()
 */
function bp_docs_doc_edit_link() {
	echo bp_docs_get_doc_edit_link();
}
	/**
	 * Returns the Doc edit link
	 *
	 * @since 1.1
	 */
	function bp_docs_get_doc_edit_link( $doc_id = false ) {
		if ( !$doc_id ) {
			$doc_id = get_the_ID();
		}

		$doc_link = bp_docs_get_doc_link( $doc_id );

		return apply_filters( 'bp_docs_get_doc_edit_link', trailingslashit( $doc_link ) . BP_DOCS_EDIT_SLUG );
	}


/**
 * Get a list of the user's groups
 *
 * This is a wrapper for bp_has_groups(), which fixes a problem with the way that function handles
 * pagination, and also simplifies the output. Used on the Create and Edit pages.
 *
 * @since 1.0-beta
 *
 * @param array $args
 * @return array $user_groups An array of the user's groups
 */
function bp_docs_get_user_groups( $args = array() ) {
	global $bp;

	// Respect the can_create_in_any_group setting
	if ( ! current_user_can( 'bp_docs_create_in_any_group' ) ) {
		$user_id = bp_loggedin_user_id();
	} else {
		$user_id = false;
	}

	$defaults = array(
		'user_id'         => $user_id,
		'per_page'        => 999,
		'max'             => 999,
		'populate_extras' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$groups = array();
	if ( bp_has_groups( $r ) ) {
		while ( bp_groups() ) {
			bp_the_group();
			if ( bp_docs_is_docs_enabled_for_group() ) {
				$groups[] = clone( $bp->groups->current_group );
			}
		}
	}

	return apply_filters( 'bp_docs_get_user_groups', $groups );
}


/**
 * Is the current page the Create page?
 *
 * @since 1.0-beta
 *
 * @return bool $is_create_page
 */
function bp_docs_is_doc_create() {
	global $bp;

	$is_create_page = false;

	if ( bp_is_current_action( BP_DOCS_CREATE_SLUG ) ) {
		$is_create_page = true;
	}

	return $is_create_page;
}

/**
 * Get the proper title for the current Docs page.
 *
 * @since 1.0-beta
 *
 * @return str $title The title for the page
 */
function bp_docs_get_page_title() {
	global $bp;

	$title = '';

	if ( bp_is_current_action( BP_DOCS_CREATE_SLUG ) ) {
		$title = __( 'Create New Doc', 'buddypress-docs' );
	} else if ( bp_is_current_action( 'edit' ) ) {
		$title = __( 'Edit Doc', 'buddypress-docs' );
	}

	return apply_filters( 'bp_docs_get_page_title', $title );
}

/**
 * Returns a list of a Doc's associated groups (usually just one)
 *
 * @since 1.0-beta
 *
 * @param int $doc_id The ID of the Doc
 * @return array $groups The group or groups the Doc is associated with
 */
function bp_docs_get_associated_groups( $doc_id ) {
	$bp = buddypress();

	if ( empty( $doc_id ) ) {
		return false;
	}

	$group_ids = wp_get_object_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

	$groups = array();
	foreach ( $group_ids as $group_id ) {
		$groups[] = groups_get_group( array( 'group_id' => $group_id ) );
	}

	return apply_filters( 'bp_docs_get_associated_groups', $groups );
}

/**
 * Is this doc associated with the current group?
 *
 * @since 1.0-beta
 *
 * @param int $doc_id The ID of the Doc being checked. Defaults to the current Doc in the loop
 * @return bool $is_associated True if the doc is associated with the group, false otherwise
 */
function bp_docs_is_doc_associated_with_current_group( $doc_id = false ) {
	$bp = buddypress();

	if ( !$doc_id ) {
		$doc_id = get_the_ID();
	}

	$is_associated = false;

	if ( !empty( $bp->groups->current_group ) ) {
		$is_associated = has_term( $bp->groups->current_group->id, $bp->bp_docs->associated_item_tax_name, $doc_id );
	}

	return apply_filters( 'bp_docs_is_doc_associated_with_current_group', $is_associated, $doc_id );
}

/**
 * Checks whether a doc has an associated group
 *
 * @since 1.1.2
 *
 * @param int $doc_id The ID of the Doc being checked. Defaults to the current Doc in the loop
 * @return bool Returns true if the doc is associated with a group, otherwise false
 */
function bp_docs_doc_is_in_group( $doc_id = false ) {
	$bp = buddypress();

	if ( !$doc_id ) {
		$doc_id = get_the_ID();
	}

	$terms = wp_get_post_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

	return apply_filters( 'bp_docs_doc_is_in_group', !empty( $terms ), $doc_id, $terms );
}


/**
 * Get the ID of the group associated with a given doc
 *
 * We are assuming for now that there will only be one group per doc
 *
 * @since 1.0-beta
 *
 * @param int $doc_id The ID of the doc
 * @return int $group_id The ID of the group
 */
function bp_docs_get_associated_group_id( $doc_id ) {
	$bp = buddypress();

	$group_terms = wp_get_object_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

	if ( !empty( $group_terms ) && !is_wp_error( $group_terms ) ) {
		$group_id = intval( $group_terms[0]->name );
	} else {
		$group_id = 0;
	}

	return apply_filters( 'bp_docs_get_associated_group_id', $group_id );
}

/**
 * Catches the doc creation form submission, creates the doc, and redirects.
 *
 * @since 1.0-beta
 */
function bp_docs_handle_doc_creation() {
	if ( !isset( $_POST['bp_docs_submitted'] ) )
		return;

	// Check nonce
	check_admin_referer( 'bp_docs_create' );

	global $bp;

	$doc_id = isset( $_POST['doc_id'] ) ? $_POST['doc_id'] : 0;

	// Check to see that the title and content are not empty
	if ( empty( $_POST['doc_title'] ) ) {
		bp_core_add_message( __( 'Your Doc could not be saved. Please give it a title.', 'buddypress-docs' ), 'error' );
		bp_core_redirect( bp_get_root_domain() );
	}

	if ( empty( $_POST['doc_content'] ) ) {
		bp_core_add_message( __( 'Your Doc could not be saved. Please add some content.', 'buddypress-docs' ), 'error' );
		bp_core_redirect( bp_get_root_domain() );
	}

	// Not checking for doc_id, as we may be creating a new doc from a previous one
	$new_doc_args = array(
		'post_title'	=> $_POST['doc_title'],
		'post_content'	=> $_POST['doc_content'],
		'post_type'	=> bp_docs_get_post_type_name()
	);

	// If this is an existing post, set the ID
	if ( $doc_id ) {
		$new_doc_args['ID'] = $doc_id;
	} else {
		$new_doc_args['post_author'] = bp_loggedin_user_id();
	}

	$new_doc_id = wp_insert_post( $new_doc_args );

	if ( is_wp_error( $new_doc_id ) ) {
		bp_core_add_message( __( 'There was an error when saving your Doc.', 'buddypress-docs' ), 'error' );
		bp_core_redirect( bp_get_root_domain() );
	}

	// Now that the post is saved, add the taxonomy terms
	bp_docs_update_doc_tax( $new_doc_id, $_POST );

	// And add the access setting meta
	bp_docs_update_doc_access_setting( $new_doc_id, $_POST );

	// Finally, add a post meta to keep track of the associated group, if any.
	// This helps with template-level stuff where it is expensive to check the taxonomy
	if ( isset( $_POST['associated_group_id'] ) ) {
		update_post_meta( $new_doc_id, 'bp_docs_associated_group_id', (int)$_POST['associated_group_id'] );
	} else {
		delete_post_meta( $new_doc_id, 'bp_docs_associated_group_id' );
	}

	do_action( 'bp_docs_doc_saved', $new_doc_id );

	if ( $doc_id ) {
		bp_core_add_message( __( 'Your Doc was saved successfully.', 'buddypress-docs' ) );
	} else {
		bp_core_add_message( __( 'Your Doc was created successfully.', 'buddypress-docs' ) );
	}

	bp_core_redirect( bp_docs_get_doc_link( $new_doc_id ) );
}
add_action( 'bp_actions', 'bp_docs_handle_doc_creation' );

/**
 * Convenience function for grabbing doc settings.
 */
function bp_docs_get_doc_settings( $doc_id ) {
	return array(
		'read'    => get_post_meta( $doc_id, 'bp_docs_settings_read', true ),
		'edit'    => get_post_meta( $doc_id, 'bp_docs_settings_edit', true ),
		'post_comments'    => get_post_meta( $doc_id, 'bp_docs_settings_post_comments', true ),
		'view_comments'    => get_post_meta( $doc_id, 'bp_docs_settings_view_comments', true ),
	);
}

/**
 * Set the taxonomy terms for a given Doc.
 *
 * @since 1.0-beta
 *
 * @param int $doc_id The ID of the doc
 * @param array $posted_data The $_POST data from the submission form
 */
function bp_docs_update_doc_tax( $doc_id, $posted_data ) {
	global $bp;

	// Doc tags
	if ( ! empty( $posted_data['doc_tags'] ) ) {
		// Third argument must be false, or else the terms will be added to, not replaced
		wp_set_object_terms( $doc_id, $posted_data['doc_tags'], 'bp_docs_tag', false );
	} else {
		// No tags have been entered, so let's clear the slate
		wp_set_object_terms( $doc_id, false, 'bp_docs_tag', false );
	}

	// Associated group ID
	if ( isset( $posted_data['associated_group_id'] ) && $posted_data['associated_group_id'] != 'false' ) {
		wp_set_object_terms( $doc_id, (int)$posted_data['associated_group_id'], $bp->bp_docs->associated_item_tax_name, false );
	} else {
		// No group has been selected, so remove all group associations
		wp_set_object_terms( $doc_id, false, $bp->bp_docs->associated_item_tax_name, false );
	}
}

/**
 * Updates the post meta that store a doc's access settings.
 *
 * @since 1.2
 */
function bp_docs_update_doc_access_setting( $doc_id, $posted_data ) {
	$settings = array( 'read', 'edit', 'post_comments', 'view_comments' );
	foreach ( $settings as $setting ) {
		if ( isset( $posted_data['settings'][ $setting ] ) ) {
			update_post_meta( $doc_id, 'bp_docs_settings_' . $setting, $posted_data['settings'][ $setting ] );
		}
	}
}

/**
 * Inserts necessary JS and CSS.
 *
 * It would be better to use wp_enqueue_scripts, but at that point, the query has already been
 * parsed, so our is_bp_docs_page() functions do not work. Thus we will use the less efficient
 * method of checking on bp_actions and then printing in the header. Hey, it's what BP does.
 *
 * @since 1.0-beta
 */
function bp_docs_enqueue_scripts() {
	global $bp;

	if ( bp_docs_is_bp_docs_page() ) {
		wp_enqueue_script( 'bp-docs-js', BP_DOCS_PLUGIN_URL . 'includes/js/bp-docs.js', array( 'jquery' ), BP_DOCS_VERSION );
		wp_localize_script( 'bp-docs-js', 'bp_docs', array(
			'please_wait' => __( 'Please wait...', 'buddypress-docs' ),
			'confirm_delete' => __( 'Are you sure you want to delete this Doc?', 'buddypress-docs' )
		) );

		wp_enqueue_style( 'bp-docs-css', BP_DOCS_PLUGIN_URL . 'includes/css/screen.css', array(), BP_DOCS_VERSION );

		if ( is_rtl() ) {
			wp_enqueue_style( 'bp-docs-rtl-css', BP_DOCS_PLUGIN_URL . 'includes/css-rtl/screen-rtl.css', array(), BP_DOCS_VERSION );
		}
	}

	if ( bp_docs_is_doc_create() || ( is_singular( bp_docs_get_post_type_name() ) && bp_is_current_action( 'edit' ) ) ) {
		wp_enqueue_script( 'bp-docs-edit-js', BP_DOCS_PLUGIN_URL . 'includes/js/edit-validation.js', array( 'jquery', 'bp-docs-js' ), BP_DOCS_VERSION );

		wp_enqueue_style( 'bp-docs-edit-css', BP_DOCS_PLUGIN_URL . 'includes/css/edit.css', array(), BP_DOCS_VERSION );

		if ( is_rtl() ) {
			wp_enqueue_style( 'bp-docs-edit-rtl-css', BP_DOCS_PLUGIN_URL . 'includes/css-rtl/edit-rtl.css', array(), BP_DOCS_VERSION );
		}

		wp_enqueue_script( 'jquery-colorbox', BP_DOCS_PLUGIN_URL . 'lib/js/colorbox/jquery.colorbox-min.js', array( 'jquery' ), '1.3.19' );
		wp_enqueue_script( 'jquery-chosen', BP_DOCS_PLUGIN_URL . 'lib/js/chosen/chosen.jquery.min.js', array( 'jquery' ), '0.9.1' );
		wp_enqueue_style( 'jquery-chosen-css', BP_DOCS_PLUGIN_URL . 'lib/css/chosen/chosen.min.css', array(), '0.9.1' );

		// Load the idle timer only when edit lock is enabled
		if ( bp_docs_is_edit_lock_enabled() ) {
			wp_enqueue_script( 'bp-docs-idle-js', BP_DOCS_PLUGIN_URL . 'includes/js/idle.js', array( 'jquery' ), BP_DOCS_VERSION );
			wp_localize_script( 'bp-docs-idle-js', 'BP_Docs_Idle_Timers', array(
				'doc_id' => get_the_ID(),
				'idle_max' => absint( apply_filters( 'bp_docs_idle_max', 300 ) ), // 5 minutes
				'idle_away' => absint( apply_filters( 'bp_docs_idle_away', 180 ) ), // 3 minutes
				'timer_actions' => array( 'focus', 'load', 'mousemove', 'mousedown', 'keypress', 'scroll' ),
				'wp_interval' => absint( apply_filters( 'heartbeat_settings', array() )['interval'] ),
			) );
		}
	}
}
add_action( 'bp_actions', 'bp_docs_enqueue_scripts' );


/**
 * Is this a directory?
 *
 * @since 1.0
 *
 * @return bool
 */
function bp_docs_is_directory() {
	return bp_is_directory() && bp_is_current_component( BP_DOCS_SLUG );
}

/**
 * Takes a list of group IDs and returns a list of docs belonging to those groups.
 *
 * @since 1.0
 *
 * @param array $group_ids The list of group IDs
 * @param int $user_id The current user's ID
 * @return array $docs The doc IDs
 */
function bp_docs_get_docs_for_groups( $group_ids, $user_id ) {

	// Right now, this is just a wrapper for a function that does the same thing
	return BP_Docs_Query::get_docs_for_groups( $group_ids, $user_id );
}

/**
 * Is the a standalone (non-group) doc?
 *
 * @since 1.0
 *
 * @param int $doc_id The doc ID
 * @return bool $is_standalone
 */
function bp_docs_is_standalone_doc( $doc_id ) {
	$bp = buddypress();

	$terms = wp_get_object_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

	return apply_filters( 'bp_docs_is_standalone_doc', empty( $terms ) );
}

/**
 * Returns a list of the user's docs that are not associated with any groups.
 *
 * @since 1.0
 *
 * @param array $args
 * @return array A list of doc objects
 */
function bp_docs_get_standalone_docs( $args = array() ) {
	$bp = buddypress();

	$defaults = array(
		'author' => bp_loggedin_user_id()
	);
	$r = wp_parse_args( $args, $defaults );

	$q = new WP_Query( array(
		'post_type' => bp_docs_get_post_type_name(),
		'author' => $r['author'],
		'posts_per_page' => -1,
	) );

	// Have to loop and check taxonomy. Bummer
	$standalone_docs = array();
	if ( !empty( $q->posts ) ) {
		foreach( $q->posts as $doc ) {
			if ( bp_docs_is_standalone_doc( $doc->ID ) ) {
				$standalone_docs[] = $doc;
			}
		}
	}

	return $standalone_docs;
}

/**
 * When a user is deleted, reassign their docs.
 *
 * @since 1.0
 */
function bp_docs_reassign_docs_on_user_delete( $user_id, $reassign_user_id ) {
	global $wpdb;

	$wpdb->update( $wpdb->posts, array( 'post_author' => $reassign_user_id ), array( 'post_author' => $user_id, 'post_type' => bp_docs_get_post_type_name() ) );
}
add_action( 'delete_user', 'bp_docs_reassign_docs_on_user_delete', 10, 2 );

/**
 * Does the current group have docs enabled?
 *
 * This function handles all the logic for checking the group settings. As of BP Docs 1.2, docs
 * are enabled for groups by default. Whether docs can be *created* depends on a separate setting,
 * managed in {@link bp_docs_get_group_doc_access_setting()}.
 *
 * @since 1.1.2
 *
 * @param int $group_id The group id. Defaults to the current group.
 * @return bool $is_enabled True if docs are enabled for the group.
 */
function bp_docs_is_docs_enabled_for_group( $group_id = false ) {
	if ( !$group_id && function_exists( 'bp_get_current_group_id' ) ) {
		$group_id = bp_get_current_group_id();
	}

	if ( !$group_id ) {
		return false;
	}

	$is_enabled = groups_get_groupmeta( $group_id, 'bp-docs-enabled' );

	if ( '' === $is_enabled ) {
		$is_enabled = 1;
	}

	// For legacy groups, look for the 'enable_docs' property on the group object
	if ( !$is_enabled ) {
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( !empty( $group->enable_docs ) ) {
			$is_enabled = 1;
		}
	}

	return apply_filters( 'bp_docs_is_docs_enabled_for_group', (bool)$is_enabled, $group_id );
}

/**
 * Get group doc settings.
 *
 * @since 1.2
 *
 * @param int $group_id The group id. Defaults to the current group.
 * @return array Key/value pairs of settings
 */
function bp_docs_get_group_doc_settings( $group_id = false ) {
	if ( !$group_id && function_exists( 'bp_get_current_group_id' ) ) {
		$group_id = bp_get_current_group_id();
	}

	if ( !$group_id ) {
		return false;
	}

	$settings = groups_get_groupmeta( $group_id, 'bp-docs-settings' );

	if ( empty( $settings ) ) {
		// Get the default doc settings
		$settings = bp_docs_get_default_access_options();
	}

	return apply_filters( 'bp_docs_get_group_doc_settings', $settings, $group_id );
}

/**
 * Returns the default doc access options.
 *
 * @since 1.2
 *
 * @return array Key/value pairs of default options
 */
function bp_docs_get_default_access_options() {
	$defaults = array(
		'read'          => 'members',
		'edit'          => 'members',
		'post_comments' => 'members',
		'view_comments' => 'members',
		'create'        => 'members',
	);
	return apply_filters( 'bp_docs_get_default_access_options', $defaults );
}

/**
 * Locate a template file.
 *
 * @since 1.1.5
 */
function bp_docs_locate_template( $template_name, $load = false, $require_once = true ) {
	$template = locate_template( array( 'docs/' . $template_name ), false );

	if ( !$template ) {
		$template = BP_DOCS_PLUGIN_DIR . 'includes/templates/docs/' . $template_name;
	}

	if ( $load ) {
		require $template;
	} else {
		return $template;
	}
}

/**
 * When attachments are uploaded, they are by default "unattached". We need to attach them to the
 * doc post.
 *
 * @since 1.1.5
 *
 * @param int $attachment_id
 */
function bp_docs_add_attachment_parent( $attachment_id ) {

	if ( isset( $_POST['doc_id'] ) ) {
		$doc_id = intval( $_POST['doc_id'] );

		if ( !empty( $doc_id ) ) {
			$doc = get_post( $doc_id );
			if ( !empty( $doc->post_type ) && bp_docs_get_post_type_name() == $doc->post_type ) {
				wp_update_post( array(
					'ID'		=> $attachment_id,
					'post_parent'	=> $doc_id
				) );
			}
		}
	}
}
add_action( 'add_attachment', 'bp_docs_add_attachment_parent' );

/**
 * When a doc is being edited, we may need to trash some attachments.
 *
 * @since 1.1.5
 */
function bp_docs_trash_attachment() {
	if ( !isset( $_POST['attachment_id'] ) )
		return;

	check_admin_referer( 'bp_docs_remove_attachment' );

	$attachment_id = intval( $_POST['attachment_id'] );

	if ( $attachment_id ) {
		// A little extra security
		if ( !current_user_can( 'delete_post', $attachment_id ) )
			return;

		$trashed = wp_delete_attachment( $attachment_id );

		if ( $trashed ) {
			bp_core_add_message( __( 'Attachment deleted successfully.', 'buddypress-docs' ) );
		} else {
			bp_core_add_message( __( 'There was a problem deleting the attachment.', 'buddypress-docs' ), 'error' );
		}
	}

	if ( isset( $_POST['redirect_to'] ) ) {
		$redirect = $_POST['redirect_to'];
	} else {
		$redirect = wp_get_referer();
	}

	bp_core_redirect( $redirect );
}
add_action( 'bp_init', 'bp_docs_trash_attachment' );

/**
 * Is the currently viewed page the history page for a single doc?
 *
 * @since 1.2
 * @return bool
 */
function bp_docs_is_doc_history() {
	return is_singular( bp_docs_get_post_type_name() ) && bp_is_current_action( BP_DOCS_HISTORY_SLUG );
}

/**
 * Is the currently viewed page the edit page for a single doc?
 *
 * @since 1.2
 * @return bool
 */
function bp_docs_is_doc_edit() {
	return is_singular( bp_docs_get_post_type_name() ) && bp_is_current_action( BP_DOCS_EDIT_SLUG );
}

/**
 * Adds an 'all' parameter to the bp_has_activities() query string when on a single doc page
 *
 * The All tab on single item pages doesn't pick up custom post type activity items. Let's fix
 * that.
 *
 * @since 1.2
 *
 * @param str $qs The query string passed to bp_has_activities()
 * @return str $qs
 */
function bp_docs_filter_activity_querystring( $qs ) {
	if ( is_singular( bp_docs_get_post_type_name() ) ) {
		$qs .= '&show_hidden=1';
	}
	return $qs;
}
add_filter( 'bp_dtheme_ajax_querystring', 'bp_docs_filter_activity_querystring' );
add_filter( 'bp_legacy_theme_ajax_querystring', 'bp_docs_filter_activity_querystring' );

/**
 * Are edit locks enabled?
 *
 * @since 1.2.2
 * @return bool
 */
function bp_docs_is_edit_lock_enabled() {
	$settings = get_option( 'bp-docs-settings' );
	$is_enabled = isset( $settings['edit_lock'] ) ? (bool)$settings['edit_lock'] : true;
	return apply_filters( 'bp_docs_is_edit_lock_enabled', $is_enabled );
}

/**
 * Registers widgets.
 *
 * @since 1.3
 */
function bp_docs_register_widgets() {
	// ** DigiWuz MSP ENHANCEMENT: Ensure the widget class file is loaded before registering. **
	require_once BP_DOCS_PLUGIN_DIR . 'includes/class-wp-widget-recent-docs.php';
	register_widget( 'WP_Widget_Recent_Docs' );
}
add_action( 'widgets_init', 'bp_docs_register_widgets' );

/**
 * Adds body classes to BP Docs pages.
 *
 * @since 1.5
 * @param array $classes
 * @return array
 */
function bp_docs_body_class( $classes ) {
	$bp = buddypress();

	if ( is_singular( bp_docs_get_post_type_name() ) ) {
		$classes[] = 'bp-docs-single-item';
	} else if ( bp_docs_is_directory() ) {
		$classes[] = 'bp-docs-directory';
	}

	return $classes;
}
add_filter( 'body_class', 'bp_docs_body_class' );

/**
 * Sets up the BP Docs TinyMCE buttons.
 *
 * @since 1.8
 */
function bp_docs_setup_tinymce() {
	// Filter for plugins.
	add_filter( 'mce_external_plugins', 'bp_docs_tinymce_plugins' );

	// Filter for buttons.
	add_filter( 'mce_buttons', 'bp_docs_tinymce_buttons' );
}

/**
 * Load the TinyMCE Doc Link plugin.
 *
 * @since 1.8
 *
 * @param array $plugins
 * @return array
 */
function bp_docs_tinymce_plugins( $plugins ) {
	$plugins['bp_docs_doc_link'] = BP_DOCS_PLUGIN_URL . 'lib/js/tinymce/plugins/doclink/editor_plugin.js';
	return $plugins;
}

/**
 * Add the TinyMCE Doc Link button.
 *
 * @since 1.8
 *
 * @param array $buttons
 * @return array
 */
function bp_docs_tinymce_buttons( $buttons ) {
	array_push( $buttons, 'separator', 'bp_docs_doc_link' );
	return $buttons;
}

/**
 * Is this a screen where we should load the editor buttons?
 *
 * We load on the post edit/add new screens, on the BP Docs edit screen,
 * and on the front-end group forum topic edit screen.
 *
 * @since 1.8
 *
 * @return bool
 */
function bp_docs_is_load_editor_buttons() {
	$retval = false;

	if ( is_admin() && ( in_array( get_current_screen()->base, array( 'post', 'widgets' ) ) ) ) {
		$retval = true;
	} else if ( bp_docs_is_doc_create() || bp_docs_is_doc_edit() ) {
		$retval = true;
	} else if ( bp_is_group_forum_topic_edit() ) {
		$retval = true;
	}

	return apply_filters( 'bp_docs_is_load_editor_buttons', $retval );
}

/**
 * Check whether to load the TinyMCE button for inserting Doc links.
 *
 * @since 1.8
 */
function bp_docs_check_load_tinymce_buttons() {
	if ( bp_docs_is_load_editor_buttons() ) {
		bp_docs_setup_tinymce();
	}
}
add_action( 'init', 'bp_docs_check_load_tinymce_buttons' );

/**
 * Get a user's displayname, linked to his profile.
 *
 * @since 1.8
 *
 * @param int $user_id
 * @return string
 */
function bp_docs_get_user_link( $user_id ) {
	return '<a href="' . bp_core_get_user_domain( $user_id ) . '">' . bp_core_get_user_displayname( $user_id ) . '</a>';
}

/**
 * DigiWuz MSP ENHANCEMENT: Get the display label for a doc status.
 *
 * @param string $status The status slug (eg, 'draft', 'approved').
 * @return string The display label.
 */
function bp_docs_get_status_label( $status ) {
    $statuses = array(
        'draft'    => __( 'Draft', 'buddypress-docs' ),
        'approved' => __( 'Approved', 'buddypress-docs' ),
        'rejected' => __( 'Rejected', 'buddypress-docs' ),
    );

    return isset( $statuses[ $status ] ) ? $statuses[ $status ] : '';
}

/**
 * DigiWuz MSP ENHANCEMENT: Get and format the history for a given Doc for display.
 *
 * @param array $args Arguments for the query.
 * @return string HTML output of the history list.
 */
function bp_docs_get_doc_history_for_display( $args = '' ) {
    global $wpdb;

    $defaults = array(
        'doc_id' => get_the_ID(),
    );
    $r = wp_parse_args( $args, $defaults );

    $doc_id = (int) $r['doc_id'];

    if ( empty( $doc_id ) ) {
        return '';
    }

    $log_table = $wpdb->prefix . 'bp_docs_log';
    $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$log_table} WHERE doc_id = %d ORDER BY date_recorded DESC", $doc_id ) );

    $output = '';

    if ( $items ) {
        $output = '<ul class="bp-docs-history-list">';

        foreach ( $items as $item ) {
            $user_link = bp_docs_get_user_link( $item->user_id );
            $time_since = sprintf( __( '%s ago', 'buddypress-docs' ), bp_core_time_since( strtotime( $item->date_recorded ) ) );
            $entry = '';

            switch ( $item->action_type ) {
                case 'created':
                    $entry = sprintf( __( '%s created this document %s.', 'buddypress-docs' ), $user_link, $time_since );
                    break;
                case 'edited':
                    $entry = sprintf( __( '%s edited this document %s.', 'buddypress-docs' ), $user_link, $time_since );
                    break;
                case 'approved':
                    $entry = sprintf( __( '%s approved this document %s.', 'buddypress-docs' ), $user_link, $time_since );
                    break;
                case 'rejected':
                    $rejection_note = ! empty( $item->extra_data ) ? ' <span class="rejection-note">(' . esc_html( $item->extra_data ) . ')</span>' : '';
                    $entry = sprintf( __( '%s rejected this document %s.%s', 'buddypress-docs' ), $user_link, $time_since, $rejection_note );
                    break;
                case 'deleted':
                    $entry = sprintf( __( '%s deleted this document %s.', 'buddypress-docs' ), $user_link, $time_since );
                    break;
            }

            if ( ! empty( $entry ) ) {
                $output .= '<li>' . $entry . '</li>';
            }
        }

        $output .= '</ul>';
    } else {
        $output = '<p>' . __( 'No history found for this document.', 'buddypress-docs' ) . '</p>';
    }

    return $output;
}

/**
 * DigiWuz MSP ENHANCEMENT: Generates a dropdown of Doc Categories.
 *
 * @param array $args Arguments for the dropdown.
 * @return string HTML output for the dropdown.
 */
function bp_docs_get_doc_category_dropdown( $args = array() ) {
	$defaults = array(
		'taxonomy'        => 'bp_docs_category',
		'name'            => 'bp_docs_category',
		'show_option_none' => __( 'Select a category', 'buddypress-docs' ),
		'hierarchical'    => 1,
		'echo'            => 0,
		'selected'        => 0,
	);

	$r = wp_parse_args( $args, $defaults );

	return wp_dropdown_categories( $r );
}

