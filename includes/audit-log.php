<?php
/**
 * BuddyPress Docs Audit Log.
 * Handles logging and display of all document-related activities.
 *
 * @package BuddyPressDocs\Includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the name of the log table.
 *
 * @return string The log table name.
 */
function bp_docs_get_log_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'bp_docs_log';
}

/**
 * Create the log table on plugin activation.
 */
function bp_docs_install_log_table() {
	global $wpdb;
	$table_name = bp_docs_get_log_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		log_id BIGINT(20) NOT NULL AUTO_INCREMENT,
		doc_id BIGINT(20) NOT NULL,
		user_id BIGINT(20) NOT NULL,
		group_id BIGINT(20) DEFAULT 0 NOT NULL,
		action VARCHAR(50) NOT NULL,
		details LONGTEXT,
		log_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (log_id),
        KEY doc_id (doc_id),
        KEY user_id (user_id),
        KEY group_id (group_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Central function to add a new entry to the audit log.
 *
 * @param array $args Array of log data.
 * @return int|false The new log ID on success, false on failure.
 */
function bp_docs_add_log_entry( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'doc_id'   => 0,
		'user_id'  => bp_loggedin_user_id(),
		'group_id' => 0,
		'action'   => '',
		'details'  => '',
		'log_date' => current_time( 'mysql' ),
	);

	$args = wp_parse_args( $args, $defaults );

	// Ensure required fields are set.
	if ( empty( $args['doc_id'] ) || empty( $args['action'] ) ) {
		return false;
	}

	// If group ID is not provided, try to get it from the doc.
	if ( empty( $args['group_id'] ) ) {
		$args['group_id'] = bp_docs_get_associated_group_id( $args['doc_id'] );
	}

	$result = $wpdb->insert(
		bp_docs_get_log_table_name(),
		array(
			'doc_id'   => $args['doc_id'],
			'user_id'  => $args['user_id'],
			'group_id' => $args['group_id'],
			'action'   => $args['action'],
			'details'  => $args['details'],
			'log_date' => $args['log_date'],
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s' )
	);

    return $result ? $wpdb->insert_id : false;
}

// -- HOOKS FOR LOGGING ACTIONS -- //

/**
 * Log when a document is first created.
 *
 * @param array $doc_args Doc arguments from creation.
 */
function bp_docs_log_on_create( $doc_args ) {
	bp_docs_add_log_entry( array(
		'doc_id'   => $doc_args['doc_id'],
		'user_id'  => $doc_args['user_id'],
		'group_id' => ! empty( $doc_args['group_id'] ) ? $doc_args['group_id'] : 0,
		'action'   => 'doc_created',
	) );
}
add_action( 'bp_docs_doc_created', 'bp_docs_log_on_create' );

/**
 * Log when a document is edited.
 *
 * @param array $edit_args Doc edit arguments.
 */
function bp_docs_log_on_edit( $edit_args ) {
	bp_docs_add_log_entry( array(
		'doc_id'  => $edit_args['doc_id'],
		'user_id' => $edit_args['user_id'],
		'action'  => 'doc_edited',
        'details' => ! empty( $edit_args['log_message'] ) ? $edit_args['log_message'] : '',
	) );
}
add_action( 'bp_docs_doc_edited', 'bp_docs_log_on_edit' );


/**
 * Log when a document is approved.
 *
 * @param int $doc_id The document ID.
 */
function bp_docs_log_on_approve( $doc_id ) {
    bp_docs_add_log_entry( array(
        'doc_id' => $doc_id,
        'action' => 'doc_approved',
    ) );
}
add_action( 'bp_docs_doc_approved', 'bp_docs_log_on_approve' );


/**
 * Log when a document is rejected.
 *
 * @param int    $doc_id The document ID.
 * @param string $reason The rejection reason.
 */
function bp_docs_log_on_reject( $doc_id, $reason ) {
    bp_docs_add_log_entry( array(
        'doc_id'  => $doc_id,
        'action'  => 'doc_rejected',
        'details' => $reason,
    ) );
}
add_action( 'bp_docs_doc_rejected', 'bp_docs_log_on_reject', 10, 2 );

/**
 * Log when a document is deleted (moved to trash).
 *
 * @param int $post_id The post ID.
 */
function bp_docs_log_on_trash( $post_id ) {
    if ( 'bp_doc' === get_post_type( $post_id ) ) {
        bp_docs_add_log_entry( array(
            'doc_id'  => $post_id,
            'action'  => 'doc_deleted',
        ) );
    }
}
add_action( 'wp_trash_post', 'bp_docs_log_on_trash' );


// -- BACKEND HISTORY REPORT -- //
if ( is_admin() ) {
    require_once BP_DOCS_PLUGIN_DIR . 'includes/admin-history-list-table.php';

    /**
     * Add the admin menu page for the history report.
     */
    function bp_docs_admin_history_menu() {
        add_submenu_page(
            'bp-docs',
            __( 'History Report', 'buddypress-docs' ),
            __( 'History Report', 'buddypress-docs' ),
            'manage_options', // Or a more specific capability
            'bp-docs-history',
            'bp_docs_admin_history_page_render'
        );
    }
    add_action( 'admin_menu', 'bp_docs_admin_history_menu' );

    /**
     * Render the admin history page content.
     */
    function bp_docs_admin_history_page_render() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'BuddyPress Docs History Report', 'buddypress-docs' ); ?></h1>
            <p><?php _e( 'This report shows a complete audit trail of all document-related activities.', 'buddypress-docs' ); ?></p>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
                <?php
                $history_list_table = new BP_Docs_History_List_Table();
                $history_list_table->prepare_items();
                $history_list_table->display();
                ?>
            </form>
        </div>
        <?php
    }
} // end is_admin()

