<?php
/**
 * The main component class for BuddyPress Docs.
 *
 * @package BuddyPressDocs\Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BP_Docs_Component extends BP_Component {

	// ** DigiWuz MSP ENHANCEMENT: Add properties for post type and taxonomies **
    public $post_type_name;
	public $associated_item_tax_name;
	public $access_tax_name;
	public $comment_access_tax_name;

	/**
	 * Constructor method.
	 */
	public function __construct() {
		global $bp;

		parent::start(
			'docs',
			__( 'Docs', 'buddypress-docs' ),
			BP_DOCS_PLUGIN_DIR
		);

		$this->includes();

		$bp->active_components[ $this->id ] = '1';

		// ** DigiWuz MSP ENHANCEMENT: Define post type and taxonomy names **
        $this->post_type_name          = apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->associated_item_tax_name = apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' );
		$this->access_tax_name         = apply_filters( 'bp_docs_access_tax_name', 'bp_docs_access' );
		$this->comment_access_tax_name = apply_filters( 'bp_docs_comment_access_tax_name', 'bp_docs_comment_access' );
	}

	/**
	 * Includes files.
	 */
	public function includes( $includes = array() ) {
		// All integrations must be loaded in the includes() method, so that they
		// can be disabled if necessary
		$includes = array(
			'integration-bp.php',
			'activity.php',
		);

		if ( bp_is_active( 'groups' ) ) {
			$includes[] = 'integration-groups.php';
		}

		if ( bp_is_active( 'xprofile' ) ) {
			$includes[] = 'integration-users.php';
		}

		parent::includes( $includes );
	}

	/**
	 * Sets up the global settings.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Set up the $globals array to be passed along to parent::setup_globals()
		$globals = array(
			'slug'          => BP_DOCS_SLUG,
			'root_slug'     => isset( $bp->pages->docs->slug ) ? $bp->pages->docs->slug : BP_DOCS_SLUG,
			'has_directory' => true,
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Sets up the navigation.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {
		$main_nav = array(
			'name'                => __( 'Docs', 'buddypress-docs' ),
			'slug'                => $this->slug,
			'position'            => 81,
			'screen_function'     => array( $this, 'screen_docs_index' ),
			'default_subnav_slug' => 'all',
		);

		$docs_link = trailingslashit( bp_get_root_domain() . '/' . $this->slug );

		$sub_nav[] = array(
			'name'            => __( 'All Docs', 'buddypress-docs' ),
			'slug'            => 'all',
			'parent_slug'     => $this->slug,
			'parent_url'      => $docs_link,
			'screen_function' => array( $this, 'screen_docs_index' ),
		);

		$sub_nav[] = array(
			'name'            => __( 'My Docs', 'buddypress-docs' ),
			'slug'            => 'my-docs',
			'parent_slug'     => $this->slug,
			'parent_url'      => $docs_link,
			'screen_function' => array( $this, 'screen_docs_my_docs' ),
		);

		// ** DigiWuz MSP ENHANCEMENT: Add History Log tab **
		$sub_nav[] = array(
			'name'            => __( 'History Log', 'buddypress-docs' ),
			'slug'            => 'history',
			'parent_slug'     => $this->slug,
			'parent_url'      => $docs_link,
			'screen_function' => array( $this, 'screen_docs_history' ),
			'position'        => 50,
		);

        if ( bp_is_active( 'groups' ) && bp_is_group() ) {
            $group = groups_get_current_group();
            bp_core_new_subnav_item( array(
                'name'            => __( 'History Log', 'buddypress-docs' ),
                'slug'            => 'history',
                'parent_slug'     => $this->slug,
                'parent_url'      => bp_get_group_permalink( $group ) . $this->slug . '/',
                'screen_function' => array( $this, 'screen_docs_history' ),
                'position'        => 51,
                'user_has_access' => $group->is_member,
            ) );
        }


		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Sets up the admin bar.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		$bp = buddypress();

		// Menus for logged in user
		if ( is_user_logged_in() ) {
			// Add 'Docs' parent menu
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Docs', 'buddypress-docs' ),
				'href'   => bp_get_loggedin_user_domain() . $this->slug . '/'
			);

			// Add 'My Docs' link
			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-my-docs',
				'title'  => __( 'My Docs', 'buddypress-docs' ),
				'href'   => trailingslashit( bp_get_loggedin_user_domain() . $this->slug . '/my-docs' )
			);
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	public function screen_docs_index() {
		bp_core_load_template( 'docs/index' );
	}
	public function screen_docs_my_docs() {
		bp_core_load_template( 'docs/index' );
	}
	public function screen_docs_started() {
		bp_core_load_template( 'docs/index' );
	}
	public function screen_docs_edited() {
		bp_core_load_template( 'docs/index' );
	}

	/**
	 * DigiWuz MSP ENHANCEMENT: Screen function for the history log.
	 */
    public function screen_docs_history() {
        add_action( 'bp_template_content', array( $this, 'content_docs_history' ) );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    /**
     * DigiWuz MSP ENHANCEMENT: Content for the history log screen.
     */
    public function content_docs_history() {
        bp_docs_locate_template( 'docs/history-log.php', true );
    }

	/**
	 * DigiWuz MSP ENHANCEMENT: Create the bp_doc post type.
	 *
	 * This method is now part of the component and is called during activation.
	 *
	 * @since 2.2.2
	 */
	public function create_post_type() {
		$bp = buddypress();

		$labels = array(
			'name'               => __( 'BuddyPress Docs', 'buddypress-docs' ),
			'singular_name'      => __( 'Doc', 'buddypress-docs' ),
			'add_new'            => __( 'Add New', 'buddypress-docs' ),
			'add_new_item'       => __( 'Add New Doc', 'buddypress-docs' ),
			'edit_item'          => __( 'Edit Doc', 'buddypress-docs' ),
			'new_item'           => __( 'New Doc', 'buddypress-docs' ),
			'view_item'          => __( 'View Doc', 'buddypress-docs' ),
			'search_items'       => __( 'Search Docs', 'buddypress-docs' ),
			'not_found'          => __( 'No Docs found', 'buddypress-docs' ),
			'not_found_in_trash' => __( 'No Docs found in Trash', 'buddypress-docs' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'BuddyPress Docs', 'buddypress-docs' )
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => 'bp-docs',
			'query_var'           => true,
			'rewrite'             => array(
				'slug'       => $bp->bp_docs->slug,
				'with_front' => false
			),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 100,
			'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions' ),
			'taxonomies'          => array( 'bp_docs_tag' )
		);

		register_post_type( $this->post_type_name, $args );
	}
}

/**
 * Loads the component.
 */
function bp_docs_setup_component() {
	buddypress()->bp_docs = new BP_Docs_Component();
}
add_action( 'bp_loaded', 'bp_docs_setup_component' );

