<?php
/**
 * WP_List_Table class for the BuddyPress Docs History Report.
 *
 * @package BuddyPressDocs\Includes
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BP_Docs_History_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'log',
				'plural'   => 'logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the column definitions.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'doc_id'   => __( 'Document', 'buddypress-docs' ),
			'user_id'  => __( 'User', 'buddypress-docs' ),
			'action'   => __( 'Action', 'buddypress-docs' ),
			'details'  => __( 'Details', 'buddypress-docs' ),
			'group_id' => __( 'Group', 'buddypress-docs' ),
			'log_date' => __( 'Date', 'buddypress-docs' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'log_date' => array( 'log_date', false ),
		);
	}

	/**
	 * Default column handler.
	 *
	 * @param array  $item The item being rendered.
	 * @param string $column_name The name of the column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * Handler for the 'doc_id' column.
	 *
	 * @param array $item The item being rendered.
	 * @return string
	 */
	protected function column_doc_id( $item ) {
		$title = get_the_title( $item['doc_id'] );
		$url   = get_edit_post_link( $item['doc_id'] );
		return sprintf( '<a href="%s">%s</a> (ID: %d)', esc_url( $url ), esc_html( $title ), $item['doc_id'] );
	}

	/**
	 * Handler for the 'user_id' column.
	 *
	 * @param array $item The item being rendered.
	 * @return string
	 */
	protected function column_user_id( $item ) {
		$user = get_userdata( $item['user_id'] );
		if ( ! $user ) {
			return __( 'Unknown User', 'buddypress-docs' );
		}
		$url = get_edit_user_link( $user->ID );
		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $user->display_name ) );
	}

	/**
	 * Handler for the 'group_id' column.
	 *
	 * @param array $item The item being rendered.
	 * @return string
	 */
	protected function column_group_id( $item ) {
		if ( empty( $item['group_id'] ) || ! function_exists( 'bp_get_group_name' ) ) {
			return '&#8212;';
		}
		return esc_html( bp_get_group_name( groups_get_group( array( 'group_id' => $item['group_id'] ) ) ) );
	}

	/**
	 * Prepare the items for display.
	 */
	public function prepare_items() {
		global $wpdb;

		$table = bp_docs_get_log_table_name();
		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$paged = $this->get_pagenum();

		$orderby = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'log_date';
		$order = ( ! empty( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ) ) ) ? $_GET['order'] : 'DESC';

		$query = "SELECT * FROM {$table}";

		$query .= $wpdb->prepare( " ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, ( $paged - 1 ) * $per_page );

		$this->items = $wpdb->get_results( $query, ARRAY_A );

		$total_items = $wpdb->get_var( "SELECT COUNT(log_id) FROM {$table}" );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

