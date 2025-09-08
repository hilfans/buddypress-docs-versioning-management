<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The BP Docs admin class.
 *
 * @package BuddyPressDocs
 * @since 1.2
 */
class BP_Docs_Admin {
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
		// Replace the Recent Comments dashboard widget with a custom one
		// We do this at priority 9, so that it can be easily removed by other plugins
		// ** DigiWuz MSP ENHANCEMENT: Safely check for constant **
		if ( defined( 'BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET' ) && BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET ) {
			add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ), 9 );
			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ), 10 );
		}

		// ** DigiWuz MSP ENHANCEMENT: Moved to admin_init to avoid loading order issues. **
		add_action( 'admin_init', array( $this, 'setup_edit_screen_hooks' ) );

		// Settings page
		add_action( bp_core_admin_hook(), array( $this, 'admin_menu' ) );

		// Enqueue JS
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX for the associated group autosuggest
		add_action( 'wp_ajax_bp_docs_admin_get_groups', array( $this, 'ajax_get_groups' ) );

		// ** DigiWuz MSP ENHANCEMENT: Hook in the history page **
		add_action( 'admin_menu', array( $this, 'add_history_page' ) );
	}

	/**
	 * DigiWuz MSP ENHANCEMENT: Setup hooks that need to run later in the admin load process.
	 */
	public function setup_edit_screen_hooks() {
		// Edit screen columns
		add_filter( 'manage_edit-' . bp_docs_get_post_type_name() . '_columns',        array( $this, 'edit_screen_columns' ) );
		add_action( 'manage_' . bp_docs_get_post_type_name() . '_posts_custom_column', array( $this, 'edit_screen_column_content' ) );
	}


	/** ACTIONS ***************************************************/

	/**
	 * Removes the dashboard widgets we're going to replace.
	 */
	public function remove_dashboard_widgets() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Adds our custom dashboard widgets.
	 */
	public function add_dashboard_widgets() {
		wp_add_dashboard_widget( 'dashboard_recent_comments_bp_docs', __( 'Recent Comments on Docs', 'buddypress-docs' ), array( $this, 'dashboard_widget_recent_comments' ) );
	}

	/**
	 * Adds the "Docs" item to the BP admin menu.
	 */
	public function admin_menu() {
		// Add our main menu.
		add_menu_page(
			__( 'BuddyPress Docs', 'buddypress-docs' ),
			__( 'Docs', 'buddypress-docs' ),
			'bp_moderate',
			'bp-docs-settings',
			array( $this, 'settings_page' ),
			'div',
			28
		);

		// Add settings submenu.
		add_submenu_page(
			'bp-docs-settings',
			__( 'BuddyPress Docs Settings', 'buddypress-docs' ),
			__( 'Settings', 'buddypress-docs' ),
			'bp_moderate',
			'bp-docs-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @param string $hook The page hook.
	 */
	public function enqueue_scripts( $hook = '' ) {
		if ( 'toplevel_page_bp-docs-settings' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'bp-docs-admin-js', BP_DOCS_PLUGIN_URL . 'includes/js/admin.js', array( 'jquery' ), BP_DOCS_VERSION, true );
		wp_localize_script( 'bp-docs-admin-js', 'BP_Docs_Admin', array(
			'associated_group_nonce' => wp_create_nonce( 'bp-docs-admin-associated-group' ),
		) );
	}

	/**
	 * Handles AJAX requests for group name autosuggest.
	 */
	public function ajax_get_groups() {
		check_ajax_referer( 'bp-docs-admin-associated-group' );

		if ( ! isset( $_REQUEST['term'] ) ) {
			die();
		}

		$search_terms = $_REQUEST['term'];

		$groups_data = array();
		if ( bp_has_groups( array( 'search_terms' => $search_terms ) ) ) {
			while ( bp_groups() ) {
				bp_the_group();
				$groups_data[] = array(
					'id'    => bp_get_group_id(),
					'label' => bp_get_group_name(),
				);
			}
		}

		echo json_encode( $groups_data );
		die();
	}

	/** FILTERS ***************************************************/

	/**
	 * Filters the columns on the Docs edit screen.
	 *
	 * @param array $columns The default columns.
	 * @return array The filtered columns.
	 */
	public function edit_screen_columns( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title', 'buddypress-docs' ),
			'author' => __( 'Author', 'buddypress-docs' ),
			'associated_item' => __( 'Associated Item', 'buddypress-docs' ),
			'comments' => '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>',
			'date' => __( 'Date', 'buddypress-docs' ),
		);

		return $columns;
	}

	/** TEMPLATE **************************************************/

	/**
	 * Fills the content of the custom columns on the Docs edit screen.
	 *
	 * @param string $column The column name.
	 */
	public function edit_screen_column_content( $column ) {
		global $post;

		switch ( $column ) {
			case 'associated_item':
				if ( bp_docs_doc_is_in_group( $post->ID ) ) {
					$group_id = bp_docs_get_associated_group_id( $post->ID );
					$group = groups_get_group( array( 'group_id' => $group_id ) );
					echo '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';
				} else {
					esc_html_e( 'None', 'buddypress-docs' );
				}
			break;
		}
	}

	/**
	 * Renders the BP Docs settings page.
	 */
	public function settings_page() {
		$settings = get_option( 'bp-docs-settings', array() );

		if ( isset( $_POST['bp-docs-settings-submit'] ) ) {
			check_admin_referer( 'bp-docs-settings' );

			$settings['edit_lock'] = ! empty( $_POST['edit-lock-enable'] );

			$associated_group_id = 0;
			if ( ! empty( $_POST['global-doc-associated-group-id'] ) ) {
				$associated_group_id = intval( $_POST['global-doc-associated-group-id'] );
			}
			$settings['global-doc-associated-group-id'] = $associated_group_id;

			update_option( 'bp-docs-settings', $settings );

			echo '<div id="message" class="updated fade"><p>' . __( 'Settings saved.', 'buddypress-docs' ) . '</p></div>';
		}

		$edit_lock_enable = isset( $settings['edit_lock'] ) ? (bool) $settings['edit_lock'] : true;
		$global_doc_associated_group_id = isset( $settings['global-doc-associated-group-id'] ) ? intval( $settings['global-doc-associated-group-id'] ) : 0;
		if ( $global_doc_associated_group_id ) {
			$global_doc_associated_group = groups_get_group( array(
				'group_id' => $global_doc_associated_group_id,
			) );

			if ( $global_doc_associated_group ) {
				$global_doc_associated_group_name = $global_doc_associated_group->name;
			}
		}

		if ( empty( $global_doc_associated_group_name ) ) {
			$global_doc_associated_group_name = '';
		}
	?>
		<div class="wrap">
			<h2><?php esc_html_e( 'BuddyPress Docs Settings', 'buddypress-docs' ) ?></h2>

			<form method="post">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Edit Locking', 'buddypress-docs' ) ?></th>
						<td>
							<label for="edit-lock-enable"><input type="checkbox" name="edit-lock-enable" id="edit-lock-enable" value="1" <?php checked( $edit_lock_enable ) ?> /> <?php esc_html_e( 'Enable edit locking', 'buddypress-docs' ) ?></label>
							<p class="description"><?php esc_html_e( 'When enabled, only one user will be able to edit a given Doc at a time.', 'buddypress-docs' ) ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Directory Association', 'buddypress-docs' ) ?></th>
						<td>
							<label for="global-doc-associated-group"><?php esc_html_e( 'Select a group to be associated with all Docs in the directory:', 'buddypress-docs' ) ?></label>
							<p><input type="text" name="global-doc-associated-group" id="global-doc-associated-group" value="<?php echo esc_attr( $global_doc_associated_group_name ) ?>" /></p>
							<input type="hidden" name="global-doc-associated-group-id" id="global-doc-associated-group-id" value="<?php echo (int) $global_doc_associated_group_id ?>" />
							<p class="description"><?php esc_html_e( 'By default, Docs created in the main directory are not associated with any group. You may select a group to be used for access control for these Docs. Start typing the name of a group to see a list of matches.', 'buddypress-docs' ) ?></p>
						</td>
					</tr>
				</table>

				<?php wp_nonce_field( 'bp-docs-settings' ) ?>
				<p class="submit"><input type="submit" name="bp-docs-settings-submit" value="<?php esc_attr_e( 'Save Settings', 'buddypress-docs' ) ?>"></p>
			</form>
		</div>
	<?php
	}

	/**
	 * Custom dashboard widget, showing recent comments on docs.
	 */
	public function dashboard_widget_recent_comments() {
		global $wpdb;

		// We have to query the posts table directly, because get_comments() requires that
		// a post_type be registered
		$query = $wpdb->prepare( "SELECT c.*, p.post_title FROM {$wpdb->comments} c, {$wpdb->posts} p WHERE p.ID = c.comment_post_ID AND p.post_type = %s AND p.post_status = 'publish' AND c.comment_approved = 1 ORDER BY c.comment_date_gmt DESC LIMIT 5", bp_docs_get_post_type_name() );

		$comments = $wpdb->get_results( $query );

		if ( $comments ) {
			echo '<ul class="recent-comments-list">';
			foreach ( $comments as $comment ) {
				$comment_link = get_comment_link( $comment->comment_ID );

				$author_link = get_comment_author_link( $comment->comment_ID );
				$post_link = '<a href="' . esc_url( $comment_link ) . '">' . esc_html( $comment->post_title ) . '</a>';
				$comment_excerpt = get_comment_excerpt( $comment->comment_ID );

				echo '<li>';
				echo get_avatar( $comment, 50 );

				/* translators: 1: author link, 2: post link, 3: comment excerpt */
				$list_item = sprintf( __( '%1$s on %2$s: "%3$s"', 'buddypress-docs' ), $author_link, $post_link, $comment_excerpt );

				echo '<p class="recent-comment-meta">' . $list_item . '</p>';

				echo '</li>';
			}
			echo '</ul>';

			echo '<ul class="subsubsub"><li><a href="' . esc_url( admin_url( 'edit-comments.php?post_type=' . bp_docs_get_post_type_name() ) ) . '">' . __( 'View all', 'buddypress-docs' ) . '</a></li></ul>';

		} else {
			echo '<p>' . __( 'No comments yet.', 'buddypress-docs' ) . '</p>';
		}
	}

	/**
     * DigiWuz MSP ENHANCEMENT: Adds the history page to the admin menu.
     */
    public function add_history_page() {
        add_submenu_page(
            'edit.php?post_type=' . bp_docs_get_post_type_name(),
            __( 'History Report', 'buddypress-docs' ),
            __( 'History Report', 'buddypress-docs' ),
            'manage_options',
            'bp-docs-history',
            array( $this, 'render_history_page' )
        );
    }

    /**
     * DigiWuz MSP ENHANCEMENT: Renders the history page content.
     */
    public function render_history_page() {
        require_once BP_DOCS_PLUGIN_DIR . 'includes/admin-history-list-table.php';
        $list_table = new BP_Docs_History_List_Table();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Document History Report', 'buddypress-docs' ); ?></h1>
            <form id="bp-docs-history-filter" method="get">
                <input type="hidden" name="post_type" value="<?php echo esc_attr( bp_docs_get_post_type_name() ); ?>" />
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
}
$bp_docs_admin = new BP_Docs_Admin();

