<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main BuddyPress Docs template loop class.
 *
 * @package BuddyPressDocs
 */
class BP_Docs_Template {
	var $current_doc = -1;
	var $doc_count;
	var $docs;
	var $doc;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_doc_count;

	/**
	 * Constructor method.
	 *
	 * @param array $args
	 */
	function __construct( $args ) {
		$this->args = $args;

		// Run the query
		$this->docs = BP_Docs_Query::get_docs( $this->args );

		if ( empty( $this->docs['docs'] ) ) {
			$this->doc_count = 0;
			$this->total_doc_count = 0;
		} else {
			$this->doc_count = count( $this->docs['docs'] );
			$this->total_doc_count = $this->docs['total'];
		}

		if ( (int) $this->total_doc_count && (int) $this->args['per_page'] ) {
			$this->pag_page = $this->args['paged'];

			$this->pag_num = ceil( (int) $this->total_doc_count / (int) $this->args['per_page'] );
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( array( 'dpage' => '%#%', 'num' => $this->args['per_page'], 'sort' => $this->args['order_by'], 'dir' => $this->args['order'] ) ),
				'format' => '',
				'total' => $this->pag_num,
				'current' => $this->pag_page,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size' => 1
			) );
		}
	}

	/**
	 * Whether there are docs available in the loop.
	 *
	 * @return bool
	 */
	function has_docs() {
		if ( $this->doc_count )
			return true;

		return false;
	}

	/**
	 * To be run within the loop.
	 *
	 * @return object The doc object.
	 */
	function the_doc() {
		global $post;

		$this->in_the_loop = true;
		$this->doc = $this->next_doc();

		// loop has just started
		if ( -1 == $this->current_doc )
			do_action('bp_docs_loop_start');

		$post = $this->doc;
		setup_postdata( $post );
	}

	/**
	 * Get the next doc to be processed in the loop.
	 *
	 * @return object The doc object.
	 */
	function next_doc() {
		$this->current_doc++;
		$this->doc = $this->docs['docs'][$this->current_doc];

		return $this->doc;
	}

	/**
	 * Rewind the docs and reset doc index.
	 */
	function rewind_docs() {
		$this->current_doc = -1;
		if ( $this->doc_count > 0 ) {
			$this->doc = $this->docs['docs'][0];
		}
	}

	/**
	 * Whether there are docs left in the loop to output.
	 *
	 * This method is used by {@link bp_docs()} as part of the while loop that
	 * outputs component content.
	 *
	 * @return bool
	 */
	function user_docs() {
		if ( $this->current_doc + 1 < $this->doc_count ) {
			return true;
		} elseif ( $this->current_doc + 1 == $this->doc_count ) {
			do_action('bp_docs_loop_end');
			// Do not call rewind_posts() here as it will cause an infinite loop
		}

		$this->in_the_loop = false;
		return false;
	}
}

/**
 * Start the Docs loop.
 *
 * @param array $args
 * @return object
 */
function bp_docs_has_docs( $args = '' ) {
	global $bp_docs_template, $bp_docs_query_args;

	$defaults = array(
		'user_id'	 => 0,
		'per_page'	 => 10,
		'paged'		 => 1,
		'order_by'	 => 'modified',
		'order'		 => 'DESC',
		'search_terms'	 => '',
		'group_id'	 => false
	);

	if ( isset( $_GET['dpage'] ) ) {
		$defaults['paged'] = intval( $_GET['dpage'] );
	}

	if ( isset( $_GET['s'] ) ) {
		$defaults['search_terms'] = $_GET['s'];
	}

	// This is a gross way to do this, but it's the only way to get the order
	// info to the pagination function without a global
	if ( isset( $_GET['sort'] ) )
		$defaults['order_by'] = $_GET['sort'];
	if ( isset( $_GET['dir'] ) )
		$defaults['order'] = $_GET['dir'];

	// In a group, we must filter by the group
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$defaults['group_id'] = buddypress()->groups->current_group->id;
	}

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$bp_docs_query_args = $r; // Used for pagination

	$bp_docs_template = new BP_Docs_Template( $r );

	return $bp_docs_template->has_docs();
}

/**
 * To be used in the Docs loop.
 *
 * @return bool
 */
function bp_docs_user_docs() {
	global $bp_docs_template;
	return $bp_docs_template->user_docs();
}

/**
 * To be used in the Docs loop.
 */
function bp_docs_the_doc() {
	global $bp_docs_template;
	return $bp_docs_template->the_doc();
}

/**
 * Echoes the doc pagination count.
 */
function bp_docs_pagination_count() {
	echo bp_docs_get_pagination_count();
}
	/**
	 * Returns the doc pagination count.
	 *
	 * @return string
	 */
	function bp_docs_get_pagination_count() {
		global $bp_docs_template;

		$start_num = intval( ( $bp_docs_template->pag_page - 1 ) * $bp_docs_template->args['per_page'] ) + 1;
		$from_num = bp_core_number_format( $start_num );
		$to_num = bp_core_number_format( ( $start_num + ( $bp_docs_template->args['per_page'] - 1 ) > $bp_docs_template->total_doc_count ) ? $bp_docs_template->total_doc_count : $start_num + ( $bp_docs_template->args['per_page'] - 1 ) );
		$total = bp_core_number_format( $bp_docs_template->total_doc_count );

		return sprintf( __( 'Viewing doc %1$s to %2$s (of %3$s total docs)', 'buddypress-docs' ), $from_num, $to_num, $total );
	}

/**
 * Echoes the doc pagination links.
 */
function bp_docs_pagination_links() {
	echo bp_docs_get_pagination_links();
}

	/**
	 * Returns the doc pagination links.
	 *
	 * @return string
	 */
	function bp_docs_get_pagination_links() {
		global $bp_docs_template;

		return $bp_docs_template->pag_links;
	}

/**
 * Get the current order_by value
 *
 * @since 1.0-beta
 * @return str The current sort order
 */
function bp_docs_get_current_order_by() {
	global $bp_docs_query_args;

	return $bp_docs_query_args['order_by'];
}

/**
 * Get the current sort direction
 *
 * @since 1.0-beta
 * @return str The current sort direction
 */
function bp_docs_get_current_sort_direction() {
	global $bp_docs_query_args;

	return $bp_docs_query_args['order'];
}

/**
 * Returns the URL for a doc sort action
 *
 * @since 1.0-beta
 * @param str $sort The sort key
 * @return str $url The sort URL
 */
function bp_docs_get_sort_link( $sort ) {
	global $bp_docs_query_args;

	$sort_dir = $bp_docs_query_args['order'];
	if ( $bp_docs_query_args['order_by'] == $sort ) {
		$sort_dir = ( $bp_docs_query_args['order'] == 'DESC' ) ? 'ASC' : 'DESC';
	}
	$query_args = array_merge( bp_docs_get_query_args(), array( 'sort' => $sort, 'dir' => $sort_dir ) );
	$url = add_query_arg( $query_args );

	return $url;
}

/**
 * Returns true if the user is on any of his/her own doc pages
 *
 * @since 1.0-beta
 * @return bool $is_my_doc True if this is the user's doc, otherwise false
 */
function bp_docs_is_my_doc() {
	global $post;

	$is_my_doc = false;

	if ( is_user_logged_in() && bp_loggedin_user_id() == $post->post_author ) {
		$is_my_doc = true;
	}

	return $is_my_doc;
}
