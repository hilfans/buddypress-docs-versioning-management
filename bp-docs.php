<?php
/*
Plugin Name: BuddyPress Docs
Plugin URI: https://github.com/boonebgorges/buddypress-docs
Description: Adds collaborative document editing to BuddyPress.
Version: 2.1.2
Author: Boone B Gorges, David Cavins, and CUNY Academic Commons
Author URI: https://github.com/boonebgorges/buddypress-docs
Text Domain: buddypress-docs
Domain Path: /languages/
Licence: GPLv2 or later
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BP_DOCS_VERSION' ) ) {
	define( 'BP_DOCS_VERSION', '2.1.2' );
}

if ( ! defined( 'BP_DOCS_PLUGIN_SLUG' ) ) {
	// This is the slug used by the WordPress.org plugin repository.
	// We may not be in that directory, so we need a constant.
	define( 'BP_DOCS_PLUGIN_SLUG', 'buddypress-docs' );
}

/**
 * Main BuddyPress Docs class.
 *
 * @package BuddyPress_Docs
 */
class BP_Docs {
	/**
	 * Singleton instance.
	 *
	 * @var BP_Docs
	 */
	private static $instance = null;

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	public $post_type_name;

	/**
	 * Slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Directory name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Component ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Path to the plugin directory.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * URL to the plugin directory.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Admin object.
	 *
	 * @var BP_Docs_Admin
	 */
	public $admin;

	/**
	 * Query object.
	 *
	 * @var BP_Docs_Query
	 */
	public $query;

	/**
	 * Is this a single Doc?
	 *
	 * @var bool
	 */
	public $is_single;

	/**
	 * The current Doc object.
	 *
	 * @var WP_Post
	 */
	public $current_doc;

	/**
	 * The current Doc ID.
	 *
	 * @var int
	 */
	public $current_doc_id;

	/**
	 * The current item ID (user or group).
	 *
	 * @var int
	 */
	public $current_item_id;

	/**
	 * Is this a directory?
	 *
	 * @var bool
	 */
	public $is_directory;

	/**
	 * Is this a user's Docs page?
	 *
	 * @var bool
	 */
	public $is_user_docs;

	/**
	 * Static method to get singleton instance.
	 *
	 * @return BP_Docs
	 */
	public static function &instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Don't do anything until BP is loaded.
		add_action( 'bp_include', array( $this, 'init' ) );
	}

	/**
	 * Main initialization method.
	 */
	public function init() {
		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
		$this->register_post_type();
	}

	/**
	 * Set up globals.
	 *
	 * @since 1.9.0
	 */
	private function setup_globals() {
		$this->id           = 'docs';
		$this->post_type_name = apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->slug         = apply_filters( 'bp_docs_slug', 'docs' );
		$this->name         = apply_filters( 'bp_docs_name', __( 'Docs', 'buddypress-docs' ) );
		$this->path         = plugin_dir_path( __FILE__ );
		$this->url          = plugin_dir_url( __FILE__ );
	}

	/**
	 * Includes.
	 *
	 * @since 1.9.0
	 */
	private function includes() {
		// Functions.
		require $this->path . 'includes/functions.php';
		require $this->path . 'includes/formatting.php';
		require $this->path . 'includes/admin.php';
		require $this->path . 'includes/caps.php';
		require $this->path . 'includes/activity.php';
		require $this->path . 'includes/theme-bridge.php';
		require $this->path . 'includes/templatetags.php';
		require $this->path . 'includes/templatetags-edit.php';
		require $this->path . 'includes/ajax-validation.php';
		require $this->path . 'includes/shortcode.php';
		require $this->path . 'includes/edit-lock.php';
		require $this->path . 'includes/access-query.php';
		require $this->path . 'includes/query-builder.php';
		require $this->path . 'includes/upgrade.php';
		require $this->path . 'includes/class-wp-widget-recent-docs.php';

		// Addons.
		require $this->path . 'includes/addon-history.php';
		require $this->path . 'includes/addon-folders.php';
		require $this->path . 'includes/addon-taxonomy.php';
		require $this->path . 'includes/addon-hierarchy.php';
		require $this->path . 'includes/addon-akismet.php';
		require $this->path . 'includes/addon-wikitext.php';
		require $this->path . 'includes/addon-moderation.php';
		require $this->path . 'includes/addon-categories.php';

		// Integrations.
		require $this->path . 'includes/integration-bp.php';

		// Attachments need to load fairly early for UI reasons.
		require $this->path . 'includes/attachments.php';
		require $this->path . 'includes/attachments-ajax.php';
	}

	/**
	 * Set up hooks.
	 *
	 * @since 1.9.0
	 */
	private function setup_hooks() {
		// i18n.
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 7 );

		// Enqueue JS and CSS.
		add_action( 'bp_docs_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register the widget.
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Load the textdomain.
	 */
	public function load_textdomain() {
		$domain = 'buddypress-docs';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue CSS and JavaScript.
	 */
	public function enqueue_scripts() {

		// CSS.
		wp_enqueue_style( 'bp-docs-css', $this->url . 'includes/css/screen.css', array(), BP_DOCS_VERSION );
		wp_enqueue_style( 'bp-docs-font-css', $this->url . 'includes/css/bp-docs.css', array(), BP_DOCS_VERSION );

		if ( is_rtl() ) {
			wp_enqueue_style( 'bp-docs-rtl-css', $this->url . 'includes/css-rtl/screen-rtl.css', array( 'bp-docs-css' ), BP_DOCS_VERSION );
		}

		// JavaScript.
		wp_enqueue_script( 'bp-docs-js', $this->url . 'includes/js/bp-docs.js', array( 'jquery' ), BP_DOCS_VERSION, true );
		wp_enqueue_script( 'bp-docs-idle-js', $this->url . 'includes/js/idle.js', array( 'jquery' ), BP_DOCS_VERSION, true );
		wp_enqueue_script( 'bp-docs-attachments', $this->url . 'includes/js/attachments.js', array( 'jquery' ), BP_DOCS_VERSION, true );
		wp_enqueue_script( 'jquery-color' ); // For animations.

		if ( bp_docs_is_doc_edit() || bp_docs_is_doc_create() ) {
			wp_enqueue_script( 'bp-docs-edit-validation', $this->url . 'includes/js/edit-validation.js', array( 'jquery' ), BP_DOCS_VERSION, true );

			wp_localize_script(
				'bp-docs-js',
				'BP_Docs_Edit_Settings',
				array(
					'lock_ttl'        => bp_docs_get_lock_ttl(),
					'user_can_autosave' => current_user_can( 'bp_docs_edit' ),
					'is_new_doc'      => ! bp_docs_is_existing_doc(),
				)
			);
		}

		wp_localize_script(
			'bp-docs-js',
			'BP_Docs_Strings',
			array(
				'ays_delete_doc'             => __( 'Are you sure you want to delete this Doc?', 'buddypress-docs' ),
				'ays_delete_folder'          => __( 'Are you sure you want to delete this folder? All Docs in the folder will be moved to the root folder.', 'buddypress-docs' ),
				'ays_delete_attachment'      => __( 'Are you sure you want to delete this attachment?', 'buddypress-docs' ),
				'ays_cancel_edit'            => __( 'Are you sure you want to cancel your edits? Any changes you have made will be lost.', 'buddypress-docs' ),
				'validation_error'           => __( 'There was an error with your submission. Please check the form and try again.', 'buddypress-docs' ),
				'doc_saved'                  => __( 'Doc saved.', 'buddypress-docs' ),
				'doc_autosaved'              => __( 'Doc autosaved.', 'buddypress-docs' ),
				'saving'                     => __( 'Saving...', 'buddypress-docs' ),
				'program_error'              => __( 'A program error has occurred. Please try again.', 'buddypress-docs' ),
				'move_to_folder'             => __( 'Move to Folder', 'buddypress-docs' ),
				'show_all_folders'           => __( 'Show all folders', 'buddypress-docs' ),
				'hide_all_folders'           => __( 'Hide all folders', 'buddypress-docs' ),
				'comment_warning'            => __( 'Please enter a comment.', 'buddypress-docs' ),
				'folder_name_warning'        => __( 'Please enter a folder name.', 'buddypress-docs' ),
				'saving_time_warning'        => __( 'The Doc was last saved at', 'buddypress-docs' ),
				'session_length'             => '14400',
				'admin_url'                  => admin_url(),
				'leaving_editor_warning'     => __( 'Leaving this page will delete all unsaved changes.', 'buddypress-docs' ),
				'still_working'              => __( 'Are you still working?', 'buddypress-docs' ),
				'session_expired_title'      => __( 'Session Expired', 'buddypress-docs' ),
				'session_expired_text'       => __( 'Your session has expired. You will be redirected to the login page.', 'buddypress-docs' ),
				'attachment_requires_title'  => __( 'Please give your attachment a title.', 'buddypress-docs' ),
				'attachment_requires_file'   => __( 'Please choose a file to upload.', 'buddypress-docs' ),
				'get_docs_for_item_nonce'    => wp_create_nonce( 'bp-docs-get-docs-for-item' ),
				'toggle_access_setting_nonce' => wp_create_nonce( 'bp-docs-toggle-access-setting' ),
			)
		);
	}

	/**
	 * Register the post type for Docs.
	 */
	public function register_post_type() {

		$bp = buddypress();

		$labels = array(
			'name'               => _x( 'Docs', 'post type general name', 'buddypress-docs' ),
			'singular_name'      => _x( 'Doc', 'post type singular name', 'buddypress-docs' ),
			'add_new'            => __( 'Add New', 'buddypress-docs' ),
			'add_new_item'       => __( 'Add New Doc', 'buddypress-docs' ),
			'edit_item'          => __( 'Edit Doc', 'buddypress-docs' ),
			'new_item'           => __( 'New Doc', 'buddypress-docs' ),
			'all_items'          => __( 'All Docs', 'buddypress-docs' ),
			'view_item'          => __( 'View Doc', 'buddypress-docs' ),
			'search_items'       => __( 'Search Docs', 'buddypress-docs' ),
			'not_found'          => __( 'No docs found', 'buddypress-docs' ),
			'not_found_in_trash' => __( 'No docs found in Trash', 'buddypress-docs' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'BuddyPress Docs', 'buddypress-docs' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => current_user_can( 'bp_moderate' ),
			'show_in_menu'        => 'bp-docs',
			'query_var'           => true,
			'rewrite'             => array(
				'slug'       => $this->slug,
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'author', 'comments', 'revisions' ),
			'show_in_rest'        => true,
			'rest_base'           => $this->post_type_name,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		register_post_type( $this->post_type_name, $args );
	}

	/**
	 * Register the Recent Docs widget.
	 *
	 * @since 1.2
	 */
	public function register_widget() {
		register_widget( 'BP_Docs_Recent_Docs_Widget' );
	}
}

/**
 * Singleton function.
 *
 * @return BP_Docs
 */
function buddypress_docs() {
	return BP_Docs::instance();
}
add_action( 'plugins_loaded', 'buddypress_docs', 9 );
