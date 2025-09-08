<?php
/*
Plugin Name: buddypress docs management versioning
Description: Adds collaborative document management to BuddyPress, with a custom approval workflow and enhanced activity tracking.
Version: 2.2.0
Author: Boone Gorges (Enhanced by <a href="https://msp.web.id">DigiWuz MSP</a>)
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BP_DOCS_VERSION', '2.2.0' );
define( 'BP_DOCS_PLUGIN_SLUG', 'buddypress-docs' );
define( 'BP_DOCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BP_DOCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads BuddyPress Docs.
 *
 * @since 1.0.0
 */
function bp_docs_load() {
    // Required files.
	require BP_DOCS_PLUGIN_DIR . 'includes/functions.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/addon-history.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/addon-folders.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/addon-taxonomy.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/theme-bridge.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/query-builder.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/formatting.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/templatetags.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/templatetags-edit.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/component.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/caps.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/access-query.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/edit-lock.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/attachments.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/attachments-ajax.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/shortcode.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/ajax-validation.php';
	require BP_DOCS_PLUGIN_DIR . 'includes/class-wp-widget-recent-docs.php';

    // ** DigiWuz MSP ENHANCEMENT: Load the new workflow, audit trail, and category files **
    require BP_DOCS_PLUGIN_DIR . 'includes/workflow.php';
    require BP_DOCS_PLUGIN_DIR . 'includes/audit-log.php';
    require BP_DOCS_PLUGIN_DIR . 'includes/categories.php';

	if ( is_admin() ) {
		require BP_DOCS_PLUGIN_DIR . 'includes/admin.php';
	}
}
add_action( 'bp_include', 'bp_docs_load' );

/**
 * Sets up the BP Docs component.
 *
 * @since 1.0.0
 */
function bp_docs_setup_component() {
	buddypress()->bp_docs = new BP_Docs_Component();
}
add_action( 'bp_loaded', 'bp_docs_setup_component' );

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
function bp_docs_activation() {
	// Create the post type.
	buddypress()->bp_docs->create_post_type();

    // ** DigiWuz MSP ENHANCEMENT: Create the custom log table **
    bp_docs_install_log_table();

	// ** DigiWuz MSP ENHANCEMENT: Register category taxonomy to be available on activation **
    bp_docs_register_category_taxonomy();

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bp_docs_activation' );

