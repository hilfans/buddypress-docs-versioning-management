<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map caps.
 *
 * @since 1.2
 */
function bp_docs_map_meta_caps( $caps, $cap, $user_id, $args ) {

	switch ( $cap ) {
		case 'bp_docs_read' :
		case 'bp_docs_edit' :
		case 'bp_docs_manage' :
		case 'bp_docs_post_comments' :
		case 'bp_docs_view_comments' :
			// Check to see if this is a group doc
			// In some cases (like from the admin), $args will be empty
			if ( empty( $args[0] ) ) {
				$doc_id = get_the_ID();
			} else {
				$doc_id = $args[0];
			}

			if ( empty( $doc_id ) ) {
				break;
			}

			if ( bp_docs_get_post_type_name() !== get_post_type( $doc_id ) ) {
				break;
			}

			// Super admins can do anything
			if ( is_super_admin( $user_id ) ) {
				$caps = array( 'exist' );
				break;
			}

			$doc_settings = bp_docs_get_doc_settings( $doc_id );

			// Determine which setting to fall back on
			switch ( $cap ) {
				case 'bp_docs_read' :
					$setting_key = 'read';
					break;

				case 'bp_docs_edit' :
					$setting_key = 'edit';
					break;

				case 'bp_docs_manage' :
					// The 'manage' cap is a special case. It is not
					// user-configurable, and is determined by a combination of
					// 'edit' access and group leadership
					$setting_key = 'edit';
					break;

				case 'bp_docs_post_comments' :
					$setting_key = 'post_comments';
					break;

				case 'bp_docs_view_comments' :
					$setting_key = 'view_comments';
					break;
			}

			if ( isset( $doc_settings[ $setting_key ] ) ) {
				$setting = $doc_settings[ $setting_key ];
			} else {
				$setting = 'anyone';
			}

			// What group is this doc associated with?
			$group_id = bp_docs_get_associated_group_id( $doc_id );

			// If no group is associated with the doc, anyone with a WP account can do
			// anything to it. This should be made more fine-grained in the future
			if ( ! $group_id ) {
				$global_group_id = bp_docs_get_global_doc_group();
				if ( ! empty( $global_group_id ) ) {
					$group_id = $global_group_id;
				} else {
					$caps = array( 'exist' );
					break;
				}
			}

			$user = new WP_User( $user_id );

			// We assume that all users have the 'exist' cap
			$caps = array();

			// What's the user's status in the group?
			$is_member  = groups_is_user_member( $user_id, $group_id );
			$is_mod     = groups_is_user_mod( $user_id, $group_id );
			$is_admin   = groups_is_user_admin( $user_id, $group_id );
			$is_creator = get_post( $doc_id )->post_author == $user_id;

			switch ( $setting ) {
				case 'creator' :
					if ( $is_creator || $is_admin ) {
						$caps[] = 'exist';
					}

					break;

				case 'group-members' :
					if ( $is_member ) {
						$caps[] = 'exist';
					}

					break;

				case 'group-mods' :
					if ( $is_mod || $is_admin ) {
						$caps[] = 'exist';
					}

					break;

				case 'admins-only' :
					if ( $is_admin ) {
						$caps[] = 'exist';
					}

					break;
			}

			// Special case: 'manage'. Only group admins/mods can 'manage'
			if ( 'bp_docs_manage' === $cap ) {
				if ( ! $is_mod && ! $is_admin ) {
					$caps = array( 'do_not_allow' );
				}
			}

			break;

		case 'bp_docs_create' :
			if ( is_super_admin() ) {
				$caps = array( 'exist' );
				break;
			}

			$group_id = 0;
			if ( ! empty( $args[0] ) ) {
				$group_id = $args[0];
			}

			if ( ! $group_id ) {
				if ( bp_is_active( 'groups' ) ) {
					$group_id = bp_get_current_group_id();
				}
			}

			if ( ! $group_id ) {
				$global_group_id = bp_docs_get_global_doc_group();
				if ( $global_group_id ) {
					$group_id = $global_group_id;
				}
			}

			if ( $group_id ) {
				$group_settings = bp_docs_get_group_doc_settings( $group_id );

				if ( isset( $group_settings['create'] ) ) {
					$create_setting = $group_settings['create'];
				} else {
					$create_setting = 'members';
				}

				// What's the user's status in the group?
				$is_member  = groups_is_user_member( $user_id, $group_id );
				$is_mod     = groups_is_user_mod( $user_id, $group_id );
				$is_admin   = groups_is_user_admin( $user_id, $group_id );

				switch ( $create_setting ) {
					case 'members' :
						if ( $is_member ) {
							$caps = array( 'exist' );
						}
						break;
					case 'mods' :
						if ( $is_mod || $is_admin ) {
							$caps = array( 'exist' );
						}
						break;
					case 'admins' :
						if ( $is_admin ) {
							$caps = array( 'exist' );
						}
						break;
				}
			} else {
				$caps = array( 'exist' );
			}

			break;

		case 'bp_docs_create_in_any_group' :
			if ( is_super_admin() ) {
				$caps = array( 'exist' );
			}
			break;
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'bp_docs_map_meta_caps', 10, 4 );

/**
 * Get the group that has been designated as the "global" doc group.
 *
 * @since 1.9.0
 *
 * @return int ID of the group, or 0 for none.
 */
function bp_docs_get_global_doc_group() {
	$settings = get_option( 'bp-docs-settings', array() );
	return isset( $settings['global-doc-associated-group-id'] ) ? intval( $settings['global-doc-associated-group-id'] ) : 0;
}
