<?php
/**
 * Main plugin class.
 *
 * @package BuddyPressDocs
 */
class BP_Docs {
	/**
	 * Akismet addon object.
	 *
	 * @var BP_Docs_Akismet
	 */
	public $akismet;

	/**
	 * Moderation addon object.
	 *
	 * @var BP_Docs_Moderation
	 */
	public $moderation;

	/**
	 * WikiText addon object.
	 *
	 * @var BP_Docs_Wikitext
	 */
	public $wikitext;

	/**
	 * History addon object.
	 *
	 * @var BP_Docs_History
	 */
	public $history;

	/**
	 * Hierarchy addon object.
	 *
	 * @var BP_Docs_Hierarchy
	 */
	public $hierarchy;

	/**
	 * Taxonomy addon object.
	 *
	 * @var BP_Docs_Taxonomy
	 */
	public $taxonomy;

	var $post_type_name;
	var $associated_item_tax_name;
	var $access_tax_name;
	var $comment_access_tax_name;

	/**
	 * Folders add-on.
	 *
	 * @var BP_Docs_Folders
	 * @since 1.8
	 */
	var $folders;

	/**
	 * PHP 5 constructor
	 *
	 * @since 1.0-beta
	 */
	function __construct() {
		// Define post type and taxonomy names for use in the register functions
		$this->post_type_name 		= apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->associated_item_tax_name = apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' );
		$this->access_tax_name          = apply_filters( 'bp_docs_access_tax_name', 'bp_docs_access' );
		$this->comment_access_tax_name  = apply_filters( 'bp_docs_comment_access_tax_name', 'bp_docs_comment_access' );

		// :'(
		wp_cache_add_non_persistent_groups( array( 'bp_docs_nonpersistent' ) );

		// Let plugins know that BP Docs has started loading
		add_action( 'plugins_loaded',   array( $this, 'load_hook' ), 20 );

		// Load predefined constants first thing
		add_action( 'bp_docs_load', 	array( $this, 'load_constants' ), 2 );

		// Includes necessary files
		add_action( 'bp_docs_load', 	array( $this, 'includes' ), 4 );

		// Load the BP Component extension
		add_action( 'bp_docs_load', 	array( $this, 'do_integration' ), 6 );

		// Load textdomain
		add_action( 'bp_docs_load',     array( $this, 'load_plugin_textdomain' ) );

		// Let other plugins know that BP Docs has finished initializing
		add_action( 'bp_init',          array( $this, 'init_hook' ) );

		// Hooks into the 'init' action to register our WP custom post type and tax
		add_action( 'bp_docs_init',     array( $this, 'register_post_type' ), 2 );
		add_action( 'bp_docs_init',     array( &$this, 'add_rewrite_tags' ), 4 );

		// Set up doc taxonomy, etc
		add_action( 'bp_docs_init',     array( $this, 'load_doc_extras' ), 8 );

		// Register AJAX actions.
		add_action( 'bp_docs_init', array( $this, 'register_ajax_actions' ) );

		// Add rewrite rules
		add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );

		// parse_query
		add_action( 'parse_query', array( $this, 'parse_query' ) );

		// Protect doc access
		add_action( 'template_redirect', array( $this, 'protect_doc_access' ) );

		add_action( 'admin_init', array( $this, 'flush_rewrite_rules' ) );
	}

	/**
	 * PHP 4 constructor
	 *
	 * @since 1.0-beta
	 */
	function bp_docs() {
		$this->__construct();
	}

	/**
	 * Loads the textdomain for the plugin.
	 * Language files are used in this order of preference:
	 *    - WP_LANG_DIR/plugins/buddypress-docs-LOCALE.mo
	 *    - WP_PLUGIN_DIR/buddypress-docs/languages/buddypress-docs-LOCALE.mo
	 *
	 * @since 1.0.2
	 */
	function load_plugin_textdomain() {
		/*
		 * As of WP 4.6, WP has, by this point in the load order, already
		 * automatically added language files in this location:
		 * wp-content/languages/plugins/buddypress-docs-es_ES.mo
		 * load_plugin_textdomain()
