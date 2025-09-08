<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Docs theme bridge.
 *
 * Functions for making BP Docs templates work with a number of popular themes.
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

class BP_Docs_Theme_Bridge {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Sets up hooks.
	 */
	public function setup_hooks() {
		add_action( 'bp_init', array( $this, 'load_bridge_file' ) );

		add_action( 'bp_docs_enqueued_styles', array( $this, 'enqueue_styles_for_theme' ) );
	}

	/**
	 * Loads the necessary bridge file for the current theme.
	 */
	public function load_bridge_file() {
		$theme = wp_get_theme();
		$template = $theme->get_template();

		$template_bridge = BP_DOCS_PLUGIN_DIR . 'includes/theme-bridge/' . $template . '.php';

		if ( file_exists( $template_bridge ) ) {
			include( $template_bridge );
		}
	}

	/**
	 * Enqueues theme-specific styles.
	 */
	public function enqueue_styles_for_theme() {
		$theme = wp_get_theme();
		$template = $theme->get_template();

		$stylesheet_path = BP_DOCS_PLUGIN_DIR . 'includes/theme-bridge/' . $template . '.css';
		$stylesheet_url = BP_DOCS_PLUGIN_URL . 'includes/theme-bridge/' . $template . '.css';

		if ( file_exists( $stylesheet_path ) ) {
			wp_enqueue_style( 'bp-docs-' . $template, $stylesheet_url );
		}
	}
}
new BP_Docs_Theme_Bridge();

/** Backwards compatibility ******************************************/

/**
 * The following functions are for themes that do not have their own bp-docs support,
 * and need a little help to get the templates looking right.
 */

/**
 * Enqueue the required assets for Community-theme based themes
 *
 * @since 1.0-beta
 */
function bp_docs_enqueue_community_assets() {
	if ( function_exists( 'bp_get_theme_package_id' ) && bp_get_theme_package_id() == 'legacy' ) {
		return;
	}

	wp_enqueue_style( 'bp-docs-community-css', BP_DOCS_PLUGIN_URL . 'includes/css/theme-bridge/community.css', array(), BP_DOCS_VERSION );

	if ( is_rtl() ) {
		wp_enqueue_style( 'bp-docs-community-rtl-css', BP_DOCS_PLUGIN_URL . 'includes/css/theme-bridge/community-rtl.css', array(), BP_DOCS_VERSION );
	}

	do_action( 'bp_docs_enqueued_styles' );
}
// Don't use bp_enqueue_scripts because of BP_INSTALL_DIR
add_action( 'wp_enqueue_scripts', 'bp_docs_enqueue_community_assets', 100 );
add_action( 'bp_enqueue_scripts', 'bp_docs_enqueue_community_assets', 100 );

/**
 * Start a new template block, for BP-Default themes
 *
 * @since 1.0-beta
 */
function bp_docs_theme_begin_content() {
	// Support for bp-default themes
	if ( function_exists( 'bp_get_theme_package_id' ) && bp_get_theme_package_id() == 'legacy' ) {
		// This is a dirty, dirty hack, and I'm not proud of it
		if ( bp_is_group() && ( bp_is_current_action( BP_DOCS_SLUG ) || is_singular( buddypress()->bp_docs->post_type_name ) ) ) {
			locate_template( array( 'groups/single/home.php' ), true );
		}
	}

	/**
	 * This is a filter for manually adding a function that will be executed at the beginning
	 * of the content area, before any BP Docs content is displayed. This is useful for themes
	 * that need to manually open a div or two.
	 *
	 * Note that I am calling the function directly, instead of using do_action(). This is
	 * because there are a few places where I need to pass a parameter to the function. So,
	 * to use this filter, you must return the name of a function, not an anonymous function.
	 * I know, I'm sorry.
	 */
	$begin_content_function = apply_filters( 'bp_docs_theme_begin_content_function', 'bp_docs_theme_begin_content_default' );

	if ( function_exists( $begin_content_function ) ) {
		call_user_func( $begin_content_function );
	}
}

/**
 * The default theme bridge function for starting the content block.
 *
 * The logic here says: if the current page is not a single doc, and it's not a doc category,
 * then this must be the directory, so we should show the directory title. This should be made
 * more robust in the future.
 */
function bp_docs_theme_begin_content_default() {
	// ** DigiWuz MSP ENHANCEMENT: Use correct function name **
	if ( ! is_singular( bp_docs_get_post_type_name() ) && ! is_tax( 'bp_docs_tag' ) && bp_docs_is_bp_docs_page() ) {
		// Need to do this so that the sidebar works
		if ( !defined( 'BP_DEFAULT_COMPONENT' ) ) {
			define( 'BP_DEFAULT_COMPONENT', BP_DOCS_SLUG );
		}

		?>
		<div class="buddypress-docs-content-wrapper">
		<?php
	}
}

/**
 * Closes the template block opened by bp_docs_theme_begin_content(), for BP-Default themes
 *
 * @since 1.0-beta
 */
function bp_docs_theme_end_content() {

	/** This is a filter for manually adding a function that will be executed at the end of the
	 * content area, before any BP Docs content is displayed. This is useful for themes that
	 * need to manually close a div or two that was opened by the corresponding function in
	 * the bp_docs_theme_begin_content_function filter.
	 */
	$end_content_function = apply_filters( 'bp_docs_theme_end_content_function', 'bp_docs_theme_end_content_default' );

	if ( function_exists( $end_content_function ) ) {
		call_user_func( $end_content_function );
	}
}

function bp_docs_theme_end_content_default() {
	if ( ! is_singular( bp_docs_get_post_type_name() ) && ! is_tax( 'bp_docs_tag' ) && bp_docs_is_bp_docs_page() ) {
		?>
		</div><!-- .buddypress-docs-content-wrapper -->
		<?php
	}
}
